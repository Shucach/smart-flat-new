<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CloudUpload, ImageOff, Trash, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { usePermissions } from '@/composables/usePermissions';
import { pluralizeUk } from '@/lib/format';
import frame from '@/routes/frame';
import type { FrameImage, FramePagination } from '@/types';

const props = defineProps<{
    images: FrameImage[];
    pagination: FramePagination;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Розумна рамка',
                href: frame.index(),
            },
        ],
    },
});

const { can } = usePermissions();

/* ------------------------------------------------------------------ upload */

type PendingImage = {
    file: File;
    previewUrl: string;
};

const pendingImages = ref<PendingImage[]>([]);
const isDraggingOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const uploadForm = useForm<{ images: File[] }>({ images: [] });

const uploadProgress = computed(() => uploadForm.progress?.percentage ?? 0);

function addFiles(files: FileList | null): void {
    if (!files) {
        return;
    }

    for (const file of Array.from(files)) {
        if (!file.type.startsWith('image/')) {
            continue;
        }

        pendingImages.value.push({
            file,
            previewUrl: URL.createObjectURL(file),
        });
    }
}

function removePendingImage(index: number): void {
    const [removed] = pendingImages.value.splice(index, 1);

    if (removed) {
        URL.revokeObjectURL(removed.previewUrl);
    }
}

function clearPendingImages(): void {
    for (const pending of pendingImages.value) {
        URL.revokeObjectURL(pending.previewUrl);
    }

    pendingImages.value = [];
}

function onDrop(event: DragEvent): void {
    isDraggingOver.value = false;
    addFiles(event.dataTransfer?.files ?? null);
}

function onFilePicked(event: Event): void {
    const input = event.target as HTMLInputElement;

    addFiles(input.files);
    input.value = '';
}

function submitUpload(): void {
    if (pendingImages.value.length === 0) {
        return;
    }

    uploadForm.images = pendingImages.value.map((pending) => pending.file);

    uploadForm.post(frame.store.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearPendingImages();
            uploadForm.reset();
            reloadGallery();
        },
    });
}

/* ----------------------------------------------------------------- gallery */

const gallery = ref<FrameImage[]>([...props.images]);
const isLoadingMore = ref(false);

watch(
    () => props.images,
    (images) => {
        if (props.pagination.page <= 1) {
            gallery.value = [...images];

            return;
        }

        const known = new Set(gallery.value.map((image) => image.name));

        gallery.value = [
            ...gallery.value,
            ...images.filter((image) => !known.has(image.name)),
        ];
    },
);

const hasMorePages = computed(
    () => props.pagination.page < props.pagination.lastPage,
);

/**
 * Pages are accumulated on the client: each "load more" is a partial visit
 * that swaps `images` for the next page, and the watcher above appends them.
 */
function loadMore(): void {
    if (isLoadingMore.value || !hasMorePages.value) {
        return;
    }

    isLoadingMore.value = true;

    router.get(
        frame.index.url({ query: { page: props.pagination.page + 1 } }),
        {},
        {
            only: ['images', 'pagination'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isLoadingMore.value = false;
            },
        },
    );
}

function reloadGallery(): void {
    router.get(
        frame.index.url(),
        {},
        {
            only: ['images', 'pagination'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

/* --------------------------------------------------------------- selection */

const isSelecting = ref(false);
const selectedNames = ref<string[]>([]);

function toggleSelection(name: string): void {
    selectedNames.value = selectedNames.value.includes(name)
        ? selectedNames.value.filter((selected) => selected !== name)
        : [...selectedNames.value, name];
}

function stopSelecting(): void {
    isSelecting.value = false;
    selectedNames.value = [];
}

const isDeleteDialogOpen = ref(false);
const isDeleting = ref(false);

function confirmDeletion(): void {
    if (selectedNames.value.length === 0) {
        return;
    }

    isDeleting.value = true;

    router.delete(frame.destroy(), {
        data: { names: selectedNames.value },
        preserveScroll: true,
        onSuccess: () => {
            stopSelecting();
            reloadGallery();
        },
        onFinish: () => {
            isDeleting.value = false;
            isDeleteDialogOpen.value = false;
        },
    });
}

onBeforeUnmount(clearPendingImages);
</script>

<template>
    <Head title="Розумна рамка" />

    <div class="flex flex-col gap-4 p-4 md:gap-6 md:p-6">
        <PageHeader
            title="Розумна рамка"
            description="Галерея зображень, які показує рамка"
        >
            <template #default>
                <Button
                    v-if="can('frame.delete') && gallery.length > 0"
                    variant="outline"
                    size="sm"
                    @click="
                        isSelecting ? stopSelecting() : (isSelecting = true)
                    "
                >
                    {{ isSelecting ? 'Готово' : 'Вибрати' }}
                </Button>
            </template>
        </PageHeader>

        <SectionCard
            v-if="can('frame.upload')"
            title="Завантажити зображення"
            description="Файли обробляються у фоновій черзі — рамка оновиться за кілька хвилин."
        >
            <div class="flex flex-col gap-4">
                <button
                    type="button"
                    class="flex min-h-32 w-full flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors"
                    :class="
                        isDraggingOver
                            ? 'border-primary bg-primary/5'
                            : 'border-sidebar-border/70 hover:border-primary/60'
                    "
                    @click="fileInput?.click()"
                    @dragover.prevent="isDraggingOver = true"
                    @dragleave.prevent="isDraggingOver = false"
                    @drop.prevent="onDrop"
                >
                    <CloudUpload class="text-muted-foreground size-7" />
                    <span class="text-sm font-medium">
                        Перетягніть зображення або торкніться
                    </span>
                    <span class="text-muted-foreground text-xs">
                        Можна обрати кілька файлів одразу
                    </span>
                </button>

                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    multiple
                    class="hidden"
                    @change="onFilePicked"
                />

                <div
                    v-if="pendingImages.length > 0"
                    class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6"
                >
                    <div
                        v-for="(pending, index) in pendingImages"
                        :key="pending.previewUrl"
                        class="bg-muted relative aspect-[9/16] overflow-hidden rounded-lg"
                    >
                        <img
                            :src="pending.previewUrl"
                            :alt="pending.file.name"
                            class="h-full w-full object-cover"
                        />
                        <button
                            type="button"
                            class="bg-background/80 absolute top-1 right-1 flex size-7 items-center justify-center rounded-full backdrop-blur-sm"
                            :aria-label="`Прибрати ${pending.file.name}`"
                            :disabled="uploadForm.processing"
                            @click="removePendingImage(index)"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                </div>

                <div
                    v-if="uploadForm.processing"
                    class="bg-muted h-1.5 w-full overflow-hidden rounded-full"
                >
                    <div
                        class="bg-primary h-full transition-all"
                        :style="{ width: `${uploadProgress}%` }"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        :disabled="
                            pendingImages.length === 0 || uploadForm.processing
                        "
                        @click="submitUpload"
                    >
                        {{
                            uploadForm.processing
                                ? `Завантаження… ${uploadProgress}%`
                                : 'Завантажити'
                        }}
                    </Button>
                    <Button
                        v-if="pendingImages.length > 0"
                        variant="ghost"
                        :disabled="uploadForm.processing"
                        @click="clearPendingImages"
                    >
                        Очистити
                    </Button>
                    <p
                        v-if="uploadForm.errors.images"
                        class="text-sm text-red-600 dark:text-red-500"
                    >
                        {{ uploadForm.errors.images }}
                    </p>
                </div>
            </div>
        </SectionCard>

        <EmptyState
            v-if="gallery.length === 0"
            title="Галерея порожня"
            description="Завантажте перші зображення, щоб рамка мала що показувати."
            :icon="ImageOff"
        />

        <div
            v-else
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
        >
            <button
                v-for="image in gallery"
                :key="image.name"
                type="button"
                class="group bg-muted relative aspect-[9/16] overflow-hidden rounded-xl outline-2 outline-offset-2 outline-transparent transition-[outline]"
                :class="{
                    'outline-primary': selectedNames.includes(image.name),
                }"
                :disabled="!isSelecting"
                @click="toggleSelection(image.name)"
            >
                <img
                    :src="image.preview"
                    :alt="image.name"
                    loading="lazy"
                    class="h-full w-full object-cover"
                />
                <span
                    v-if="isSelecting"
                    class="bg-background/80 absolute top-2 left-2 flex size-9 items-center justify-center rounded-full backdrop-blur-sm"
                >
                    <Checkbox
                        :model-value="selectedNames.includes(image.name)"
                        class="pointer-events-none size-5"
                        tabindex="-1"
                    />
                </span>
            </button>
        </div>

        <div v-if="hasMorePages" class="flex justify-center pt-2">
            <Button
                variant="outline"
                :disabled="isLoadingMore"
                @click="loadMore"
            >
                {{ isLoadingMore ? 'Завантаження…' : 'Завантажити ще' }}
            </Button>
        </div>

        <p
            v-if="gallery.length > 0"
            class="text-muted-foreground text-center text-xs"
        >
            Показано {{ gallery.length }} з
            {{
                pluralizeUk(
                    pagination.total,
                    'зображення',
                    'зображення',
                    'зображень',
                )
            }}
        </p>
    </div>

    <div
        v-if="isSelecting && selectedNames.length > 0"
        class="pb-safe-20 fixed inset-x-0 bottom-0 z-40 px-4 md:pb-6"
    >
        <div
            class="bg-popover text-popover-foreground border-sidebar-border/70 mx-auto flex max-w-md items-center gap-3 rounded-xl border p-3 shadow-lg"
        >
            <span class="flex-1 text-sm font-medium">
                Обрано {{ selectedNames.length }}
            </span>
            <Button variant="ghost" size="sm" @click="stopSelecting">
                Скасувати
            </Button>
            <Button
                variant="destructive"
                size="sm"
                @click="isDeleteDialogOpen = true"
            >
                <Trash class="size-4" />
                Видалити
            </Button>
        </div>
    </div>

    <ConfirmDialog
        v-model:open="isDeleteDialogOpen"
        tone="danger"
        title="Видалити зображення?"
        :description="`Буде видалено ${pluralizeUk(selectedNames.length, 'зображення', 'зображення', 'зображень')}. Дію не можна скасувати.`"
        confirm-label="Видалити"
        cancel-label="Скасувати"
        :processing="isDeleting"
        @confirm="confirmDeletion"
    />
</template>
