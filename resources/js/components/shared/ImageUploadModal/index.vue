<script setup lang="ts">
import Modal from '@/components/shared/Modal/index.vue';
import api from '@/config/axios';
import { ClipboardPaste, ImagePlus, LoaderCircle, UploadCloud } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        title?: string;
        currentUrl?: string | null;
        nameImage?: string;
    }>(),
    {
        title: 'Thay đổi ảnh',
        currentUrl: null,
        nameImage: 'seo-image',
    },
);

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void;
    (event: 'uploaded', value: string): void;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const dropZone = ref<HTMLElement | null>(null);
const optimizedFile = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const sourceSize = ref(0);
const dragging = ref(false);
const processing = ref(false);
const uploading = ref(false);
let objectUrl: string | null = null;

const busy = computed(() => processing.value || uploading.value);
const sourceSizeLabel = computed(() => formatFileSize(sourceSize.value));
const optimizedSizeLabel = computed(() => formatFileSize(optimizedFile.value?.size ?? 0));

const clearObjectUrl = (): void => {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
};

const reset = (): void => {
    clearObjectUrl();
    optimizedFile.value = null;
    previewUrl.value = props.currentUrl ?? null;
    sourceSize.value = 0;
    dragging.value = false;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const close = (): void => {
    if (!busy.value) {
        emit('update:modelValue', false);
    }
};

const triggerFileSelect = (): void => {
    if (!busy.value) {
        fileInput.value?.click();
    }
};

const handleFileInput = async (event: Event): Promise<void> => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file) {
        await prepareFile(file);
    }
};

const handleDrop = async (event: DragEvent): Promise<void> => {
    dragging.value = false;
    const file = event.dataTransfer?.files?.[0];

    if (file) {
        await prepareFile(file);
    }
};

const handlePaste = async (event: ClipboardEvent): Promise<void> => {
    if (!props.modelValue || busy.value) {
        return;
    }

    const imageItem = Array.from(event.clipboardData?.items ?? []).find((item) => item.type.startsWith('image/'));
    const imageFile = imageItem?.getAsFile();

    if (!imageFile) {
        return;
    }

    event.preventDefault();
    await prepareFile(imageFile);
};

const prepareFile = async (file: File): Promise<void> => {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        await Swal.fire({ icon: 'warning', title: 'Ảnh không hợp lệ', text: 'Chỉ hỗ trợ ảnh JPG, PNG hoặc WebP.' });
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        await Swal.fire({ icon: 'warning', title: 'Ảnh quá lớn', text: 'Dung lượng ảnh tối đa là 10 MB.' });
        return;
    }

    processing.value = true;

    try {
        const convertedFile = await convertToWebp(file);
        clearObjectUrl();
        objectUrl = URL.createObjectURL(convertedFile);
        sourceSize.value = file.size;
        optimizedFile.value = convertedFile;
        previewUrl.value = objectUrl;
    } catch {
        await Swal.fire({ icon: 'error', title: 'Không thể xử lý ảnh', text: 'Vui lòng thử lại bằng ảnh JPG, PNG hoặc WebP khác.' });
    } finally {
        processing.value = false;
    }
};

const upload = async (): Promise<void> => {
    if (!optimizedFile.value || uploading.value) {
        return;
    }

    uploading.value = true;

    try {
        const formData = new FormData();
        formData.append('image', optimizedFile.value);
        formData.append('name', props.nameImage.slice(0, 120));

        const response = await api.post('/api/uploads/image', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        const imageUrl = response.data?.data?.url ?? response.data?.url;

        if (typeof imageUrl !== 'string' || imageUrl === '') {
            throw new Error('Upload response does not contain an image URL.');
        }

        emit('uploaded', imageUrl);
        emit('update:modelValue', false);
        void Swal.fire({
            icon: 'success',
            title: 'Đã tối ưu ảnh',
            text: 'Ảnh WebP đã được tải lên và cập nhật vào bài viết.',
            timer: 2200,
            showConfirmButton: false,
        });
    } catch (error) {
        const message = (error as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Không thể tải ảnh lên.';
        await Swal.fire({ icon: 'error', title: 'Tải ảnh thất bại', text: message });
    } finally {
        uploading.value = false;
    }
};

const convertToWebp = async (file: File): Promise<File> => {
    const image = await loadImage(file);
    const maximumDimension = 1800;
    const ratio = Math.min(maximumDimension / image.naturalWidth, maximumDimension / image.naturalHeight, 1);
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(image.naturalWidth * ratio));
    canvas.height = Math.max(1, Math.round(image.naturalHeight * ratio));

    const context = canvas.getContext('2d');

    if (!context) {
        throw new Error('Canvas is not available.');
    }

    context.drawImage(image, 0, 0, canvas.width, canvas.height);

    const blob = await new Promise<Blob>((resolve, reject) => {
        canvas.toBlob((result) => (result ? resolve(result) : reject(new Error('WebP conversion failed.'))), 'image/webp', 0.82);
    });
    const originalName = file.name || 'clipboard-image';
    const webpName = `${originalName.replace(/\.[^.]+$/, '') || 'seo-image'}.webp`;

    return new File([blob], webpName, { type: 'image/webp', lastModified: Date.now() });
};

const loadImage = (file: File): Promise<HTMLImageElement> =>
    new Promise((resolve, reject) => {
        const image = new Image();
        const reader = new FileReader();

        reader.onload = () => {
            image.src = String(reader.result ?? '');
        };
        reader.onerror = () => reject(new Error('Cannot read image.'));
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('Cannot decode image.'));
        reader.readAsDataURL(file);
    });

const formatFileSize = (bytes: number): string => {
    if (bytes <= 0) return '0 KB';
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;

    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
};

watch(
    () => props.modelValue,
    async (isOpen) => {
        if (isOpen) {
            reset();
            await nextTick();
            dropZone.value?.focus();
        } else {
            reset();
        }
    },
);

onMounted(() => window.addEventListener('paste', handlePaste));

onBeforeUnmount(() => {
    window.removeEventListener('paste', handlePaste);
    clearObjectUrl();
});
</script>

<template>
    <Modal :model-value="modelValue" panel-class="max-w-3xl" @update:model-value="close">
        <div class="border-b border-slate-200 px-5 py-4 pr-16 sm:px-6">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-violet-600">Ảnh chuẩn SEO</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">{{ title }}</h2>
            <p class="mt-1 text-sm text-slate-500">Ảnh được thu nhỏ tối đa 1800px và chuyển sang WebP trước khi tải lên.</p>
        </div>

        <div class="grid gap-5 p-5 sm:p-6">
            <button
                ref="dropZone"
                type="button"
                class="app-focus relative grid min-h-64 place-items-center overflow-hidden rounded-2xl border-2 border-dashed p-5 text-center transition"
                :class="dragging ? 'border-violet-500 bg-violet-50' : 'border-slate-300 bg-slate-50 hover:border-violet-400 hover:bg-violet-50/60'"
                :disabled="busy"
                @click="triggerFileSelect"
                @dragenter.prevent="dragging = true"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="handleDrop"
            >
                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileInput" />

                <img v-if="previewUrl" :src="previewUrl" alt="Ảnh xem trước" class="absolute inset-0 h-full w-full object-contain p-3" />
                <div v-else class="grid justify-items-center gap-3 text-slate-500">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white text-violet-600 shadow-sm">
                        <ImagePlus class="h-7 w-7" />
                    </span>
                    <div>
                        <p class="font-bold text-slate-800">Kéo thả ảnh vào đây</p>
                        <p class="mt-1 text-sm">hoặc bấm để chọn ảnh từ máy</p>
                    </div>
                </div>

                <span v-if="previewUrl && !busy" class="absolute bottom-3 rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-bold text-white">
                    Bấm hoặc kéo ảnh khác để thay đổi
                </span>

                <span v-if="busy" class="absolute inset-0 grid place-items-center bg-white/85 text-sm font-bold text-violet-700 backdrop-blur-sm">
                    <span class="grid justify-items-center gap-2">
                        <LoaderCircle class="h-7 w-7 animate-spin" />
                        {{ uploading ? 'Đang tải ảnh WebP...' : 'Đang tối ưu ảnh...' }}
                    </span>
                </span>
            </button>

            <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm sm:grid-cols-[1fr_auto] sm:items-center">
                <div class="flex items-start gap-3">
                    <ClipboardPaste class="mt-0.5 h-5 w-5 shrink-0 text-violet-600" />
                    <div>
                        <p class="font-bold text-slate-800">Có thể dán ảnh trực tiếp</p>
                        <p class="mt-1 text-slate-500">Copy ảnh rồi nhấn Ctrl+V khi modal đang mở. Hỗ trợ JPG, PNG, WebP, tối đa 10 MB.</p>
                    </div>
                </div>
                <div v-if="optimizedFile" class="text-xs font-semibold text-slate-500 sm:text-right">
                    <p>Gốc: {{ sourceSizeLabel }}</p>
                    <p class="mt-1 text-emerald-700">WebP: {{ optimizedSizeLabel }}</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="app-focus min-h-11 rounded-xl border border-slate-300 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50"
                    :disabled="busy"
                    @click="close"
                >
                    Hủy
                </button>
                <button
                    type="button"
                    class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-bold text-white hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!optimizedFile || busy"
                    @click="upload"
                >
                    <UploadCloud class="h-4 w-4" />
                    Tối ưu và sử dụng ảnh
                </button>
            </div>
        </div>
    </Modal>
</template>
