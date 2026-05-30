<template>
  <div>
    <div ref="editorContainer"></div>
  </div>
</template>

<script>
import Swal from "sweetalert2";
import { ref, onMounted, onBeforeUnmount, watch } from "vue";

export default {
  name: "TinyMceEditor",

  props: {
    // JSON block từ DB – CHỈ DÙNG ĐỂ INIT
    value: {
      type: Array,
      default: () => [],
    },

    // debounce autosave (ms)
    debounce: {
      type: Number,
      default: 800,
    },
  },

  emits: ["update:value"],

  setup(props, { emit }) {
    const editorContainer = ref(null);
    let editorInstance = null;
    let isApplyingExternalValue = false;

    /* =========================
     * AUTOSAVE DEBOUNCE
     * ========================= */
    let saveTimer = null;

    const emitDebounced = (json) => {
      clearTimeout(saveTimer);
      saveTimer = setTimeout(() => {
        emit("update:value", json);
      }, props.debounce);
    };

    const normalizeValue = (value) => (Array.isArray(value) ? value : []);
    const serializeValue = (value) => JSON.stringify(normalizeValue(value));

    /* =========================
     * HTML -> JSON
     * ========================= */
    function htmlToJson(html) {
      const root = document.createElement("div");
      root.innerHTML = html;

      return parseNodes(root);
    }

    function parseNodes(parent) {
      const nodes = [];

      parent.childNodes.forEach((node) => {
        if (node.nodeType !== Node.ELEMENT_NODE) return;

        const tag = node.tagName.toLowerCase();

        /* ===== IMAGE ===== */
        if (tag === "img") {
          nodes.push({
            type: "image",
            src: node.getAttribute("src"),
            alt: node.getAttribute("alt") || "",
            width: node.getAttribute("width")
              ? Number(node.getAttribute("width"))
              : null,
            height: node.getAttribute("height")
              ? Number(node.getAttribute("height"))
              : null,
          });
          return;
        }

        /* ===== CONTAINER ===== */
        if (["article", "div", "section"].includes(tag)) {
          nodes.push({
            type: "container",
            tag,
            children: parseNodes(node),
          });
          return;
        }

        /* ===== BLOCK KHÁC ===== */
        parseBlock(node, nodes);
      });

      return nodes;
    }

    /* ================= BLOCK ================= */

    function parseBlock(node, blocks) {
      if (node.nodeType !== Node.ELEMENT_NODE) return;

      const tag = node.tagName.toLowerCase();

      /* ===== PARAGRAPH ===== */
      if (tag === "p") {
        const images = Array.from(node.children).filter(
          (el) => el.tagName.toLowerCase() === "img"
        );

        // ❗ p chỉ bọc img → KHÔNG tạo paragraph
        if (images.length && node.textContent.trim() === "") {
          images.forEach((img) => {
            blocks.push({
              type: "image",
              src: img.getAttribute("src"),
              alt: img.getAttribute("alt") || "",
              width: img.getAttribute("width")
                ? Number(img.getAttribute("width"))
                : null,
              height: img.getAttribute("height")
                ? Number(img.getAttribute("height"))
                : null,
            });
          });
          return;
        }

        // p có text → paragraph bình thường
        blocks.push({
          type: "paragraph",
          children: parseInline(node),
        });
        return;
      }

      /* ===== HEADING ===== */
      if (/h[1-6]/.test(tag)) {
        blocks.push({
          type: "heading",
          level: Number(tag[1]),
          children: parseInline(node),
        });
        return;
      }

      /* ===== IMAGE ĐỘC LẬP ===== */
      if (tag === "img") {
        blocks.push({
          type: "image",
          src: node.getAttribute("src"),
          alt: node.getAttribute("alt") || "",
          width: node.getAttribute("width")
            ? Number(node.getAttribute("width"))
            : null,
          height: node.getAttribute("height")
            ? Number(node.getAttribute("height"))
            : null,
        });
        return;
      }

      /* ===== LIST ===== */
      if (tag === "ul" || tag === "ol") {
        blocks.push({
          type: "list",
          ordered: tag === "ol",
          items: Array.from(node.children)
            .filter((li) => li.tagName.toLowerCase() === "li")
            .map((li) => parseInline(li)),
        });
        return;
      }

      /* ===== WRAPPER ===== */
      node.childNodes.forEach((child) => parseBlock(child, blocks));
    }

    /* ================= INLINE ================= */

    function parseInline(node, style = {}) {
      // text node
      if (node.nodeType === Node.TEXT_NODE) {
        if (!node.textContent?.trim()) return [];
        return [{ text: node.textContent, ...style }];
      }

      if (node.nodeType !== Node.ELEMENT_NODE) return [];

      const tag = node.tagName.toLowerCase();
      const next = { ...style };

      if (tag === "strong" || tag === "b") next.bold = true;
      if (tag === "em" || tag === "i") next.italic = true;
      if (tag === "u") next.underline = true;
      if (tag === "s" || tag === "strike") next.strike = true;

      if (node.style?.color) next.color = node.style.color;
      if (node.style?.backgroundColor)
        next.background = node.style.backgroundColor;

      if (tag === "br") {
        return [{ text: "\n", ...next }];
      }

      return Array.from(node.childNodes).flatMap((child) =>
        parseInline(child, next)
      );
    }

    /* =========================
     * JSON -> HTML (INIT ONLY)
     * ========================= */
    function jsonToHtml(nodes) {
      if (!Array.isArray(nodes)) {
        Swal.fire(
          "",
          "JsonToHtml không phải json, không tải được bài viết",
          "error"
        );
        return;
      }

      return nodes.map(renderNode).join("");
    }

    function applyEditorValue(value) {
      if (!editorInstance) return;

      const nextValue = normalizeValue(value);
      const nextSerialized = serializeValue(nextValue);
      const currentSerialized = serializeValue(htmlToJson(editorInstance.getContent()));

      if (nextSerialized === currentSerialized) {
        return;
      }

      isApplyingExternalValue = true;
      editorInstance.setContent(nextValue.length ? jsonToHtml(nextValue) : "");

      queueMicrotask(() => {
        isApplyingExternalValue = false;
      });
    }

    function renderNode(node) {
      if (node.type === "container") {
        const children = Array.isArray(node.children) ? node.children : [];
        return `<${node.tag}>${children.map(renderNode).join("")}</${
          node.tag
        }>`;
      }

      return renderBlock(node);
    }

    function renderBlock(block) {
      switch (block.type) {
        case "heading":
          return `<h${block.level}>${renderInline(block.children)}</h${
            block.level
          }>`;

        case "paragraph":
          return `<p>${renderInline(block.children)}</p>`;

        case "image":
          return `<img src="${block.src}" alt="${block.alt || ""}" />`;

        case "list": {
          const tag = block.ordered ? "ol" : "ul";
          return `<${tag}>${block.items
            .map((li) => `<li>${renderInline(li)}</li>`)
            .join("")}</${tag}>`;
        }

        default:
          return "";
      }
    }

    function renderInline(children = []) {
      return children
        .map((t) => {
          let text = t.text || "";

          if (t.bold) text = `<strong>${text}</strong>`;
          if (t.italic) text = `<em>${text}</em>`;
          if (t.underline) text = `<u>${text}</u>`;
          if (t.strike) text = `<s>${text}</s>`;

          let style = "";
          if (t.color) style += `color:${t.color};`;
          if (t.background) style += `background-color:${t.background};`;

          return style ? `<span style="${style}">${text}</span>` : text;
        })
        .join("");
    }

    /* =========================
     * INIT TINYMCE (GIỮ CONFIG CŨ)
     * ========================= */
    onMounted(() => {
      window.tinymce.init({
        target: editorContainer.value,

        // ===== GIỮ NGUYÊN CONFIG CỦA BẠN =====
        language: "vi",
        language_url: "/assets/libs/tinymce/langs/vi.js",
        height: 500,
        menubar: true,
        plugins: [
          "advlist autolink lists link image charmap print preview anchor",
          "searchreplace visualblocks code fullscreen",
          "insertdatetime media table paste code help wordcount",
          "emoticons hr pagebreak nonbreaking toc",
          "save autosave directionality textcolor",
        ],
        toolbar: [
          "undo redo | formatselect | fontselect fontsizeselect | bold italic underline strikethrough | forecolor backcolor",
          "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image emoticons | table | code fullscreen preview | removeformat",
        ],

        setup(editor) {
          editorInstance = editor;

          editor.on("change keyup undo redo", () => {
            if (isApplyingExternalValue) {
              return;
            }

            const html = editor.getContent();
            const json = htmlToJson(html);
            emitDebounced(json);
          });
        },

        init_instance_callback(editor) {
          if (props.value?.length) {
            editor.setContent(jsonToHtml(props.value));
          }
        },
      });
    });

    async function handlePasteImage(file, editor) {
      console.log("handle Image: "+file+editor);
      // 1. validate
      if (!file || !file.type.startsWith("image/")) return;

      // 2. nén ảnh (optional)
      // compressImage(file)

      // 3. upload
      const form = new FormData();
      form.append("file", file);

      const res = await fetch("/api/upload-image", {
        method: "POST",
        body: form,
      });

      const { url } = await res.json();

      // 4. insert lại ảnh sạch
      editor.insertContent(`<img src="${url}" />`);
    }

    /* =========================
     * CLEANUP
     * ========================= */
    onBeforeUnmount(() => {
      clearTimeout(saveTimer);

      if (editorInstance) {
        window.tinymce.remove(editorInstance);
        editorInstance = null;
      }
    });

    watch(
      () => props.value,
      (value) => {
        applyEditorValue(value);
      },
      { deep: true },
    );

    return {
      editorContainer,
    };
  },
};
</script>

<style>
span#mceu_56 {
  display: none;
}
</style>
