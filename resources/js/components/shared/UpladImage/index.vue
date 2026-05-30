<template>
    <div>
        <div
            class="relative cursor-pointer rounded-xl border-2 border-dashed border-gray-300 p-6 text-center transition-all duration-300 hover:border-blue-400 hover:bg-blue-50"
            @click="triggerFile"
            @dragover.prevent
            @drop.prevent="handleDrop"
        >
            <input
                ref="fileInput"
                type="file"
                :accept="acceptString"
                class="hidden"
                @change="handleFile"
            />

            <p v-if="!preview" class="text-gray-400">
                Kéo ảnh vào đây hoặc click để chọn
            </p>

            <div v-if="preview" class="relative">
                <img
                    :src="preview"
                    class="mx-auto max-h-40 rounded-lg object-contain transition duration-300"
                    :class="{ 'scale-95 blur-sm': loading }"
                />

                <div
                    v-if="loading"
                    class="absolute inset-0 flex flex-col items-center justify-center rounded-lg bg-black/40"
                >
                    <div
                        class="mb-2 h-8 w-8 animate-spin rounded-full border-4 border-white border-t-transparent"
                    ></div>
                    <p class="text-xs text-white">Đang upload...</p>

                    <div class="mt-2 h-1 w-3/4 overflow-hidden rounded bg-gray-300">
                        <div
                            class="h-full bg-green-400 transition-all duration-300"
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="preview" class="mt-3 flex items-center justify-center">
            <input
                v-model="nameImage"
                type="text"
                class="w-full max-w-[70%] rounded-l-sm border border-gray-300 px-2 py-1 text-[11px]"
                placeholder="Nhập tên ảnh (không bắt buộc)"
            />
            <button
                class="rounded-r-sm bg-green-500 px-3 py-1 text-[12px] text-white transition hover:bg-green-600 disabled:opacity-50"
                :disabled="!file || loading"
                @click="upload"
            >
                {{ loading ? "Đang upload..." : "Tải ảnh lên" }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import Swal from "sweetalert2";

const props = defineProps<{
    accept?: string[];
    compress?: boolean;
    imageSrc?: string | null;
    nameImage?: string;
}>();

const emit = defineEmits<{
    (e: "uploaded", url: string): void;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const preview = ref<string | null>(null);
const file = ref<File | null>(null);
const loading = ref(false);
const nameImage = ref("");
const progress = ref(0);
let progressInterval: ReturnType<typeof setInterval> | null = null;

watch(
    [() => props.imageSrc, () => props.nameImage],
    ([newSrc, newName]) => {
        if (!file.value) {
            preview.value = newSrc ?? null;
        }

        nameImage.value = newName ?? "";
    },
    { immediate: true },
);

const acceptString = computed(() => {
    return props.accept?.join(",") || "image/*";
});

const triggerFile = (): void => {
    fileInput.value?.click();
};

const handleFile = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    const selected = target.files?.[0];

    if (selected) {
        processFile(selected);
    }
};

const handleDrop = (event: DragEvent): void => {
    const dropped = event.dataTransfer?.files?.[0];

    if (dropped) {
        processFile(dropped);
    }
};

const processFile = (selectedFile: File): void => {
    if (!selectedFile.type.startsWith("image/")) {
        Swal.fire("Không hợp lệ", "Chỉ được chọn file ảnh.", "warning");
        return;
    }

    file.value = selectedFile;
    preview.value = URL.createObjectURL(selectedFile);
};

const compressImage = (selectedFile: File): Promise<File> => {
    return new Promise((resolve) => {
        const img = new Image();
        const reader = new FileReader();

        reader.onload = (event) => {
            img.src = event.target?.result as string;
        };

        img.onload = () => {
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");

            if (!ctx) {
                resolve(selectedFile);
                return;
            }

            const scale = 0.7;
            canvas.width = img.width * scale;
            canvas.height = img.height * scale;

            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        resolve(selectedFile);
                        return;
                    }

                    resolve(
                        new File([blob], selectedFile.name, {
                            type: "image/jpeg",
                        }),
                    );
                },
                "image/jpeg",
                0.8,
            );
        };

        reader.readAsDataURL(selectedFile);
    });
};

const upload = async (): Promise<void> => {
    if (!file.value) {
        return;
    }

    loading.value = true;
    progress.value = 0;

    progressInterval = setInterval(() => {
        if (progress.value < 90) {
            progress.value += Math.random() * 10;
        }
    }, 200);

    try {
        let uploadFile = file.value;

        if (props.compress) {
            uploadFile = await compressImage(file.value);
        }

        const formData = new FormData();
        formData.append("image", uploadFile);
        formData.append("name", nameImage.value);

        const response = await fetch("https://api.congcuauto.com/api/uploads/image", {
            method: "POST",
            body: formData,
        });

        const data = await response.json();
        const imageUrl = data?.url || data?.data?.url;

        if (!imageUrl) {
            throw new Error("Upload fail");
        }

        progress.value = 100;
        Swal.fire("Thành công", "Tải ảnh lên thành công.", "success");
        emit("uploaded", imageUrl);
        file.value = null;
    } catch (error) {
        console.error(error);
        Swal.fire("Thất bại", "Có lỗi xảy ra khi upload ảnh.", "error");
    } finally {
        if (progressInterval) {
            clearInterval(progressInterval);
        }

        setTimeout(() => {
            loading.value = false;
            progress.value = 0;
        }, 500);
    }
};
</script>
