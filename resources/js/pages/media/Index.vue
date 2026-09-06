<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Archive,
    ChevronRight,
    CornerLeftUp,
    File,
    FileText,
    Film,
    Folder,
    HardDrive,
    Image,
    Music,
    Trash,
} from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { formatDateTime } from '@/lib/format';
import media from '@/routes/media';
import type { MediaBreadcrumb, MediaEntry } from '@/types';

const props = defineProps<{
    path: string;
    parentPath: string | null;
    breadcrumbs: MediaBreadcrumb[];
    entries: MediaEntry[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Медіа',
                href: media.index(),
            },
        ],
    },
});

const { can } = usePermissions();

const EXTENSION_ICONS: Record<string, typeof File> = Object.fromEntries([
    ...['mp4', 'mkv', 'avi', 'mov', 'm4v', 'webm', 'wmv', 'flv', 'mpg'].map(
        (extension) => [extension, Film] as const,
    ),
    ...['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'svg'].map(
        (extension) => [extension, Image] as const,
    ),
    ...['mp3', 'flac', 'wav', 'aac', 'ogg', 'm4a', 'wma'].map(
        (extension) => [extension, Music] as const,
    ),
    ...['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'iso'].map(
        (extension) => [extension, Archive] as const,
    ),
    ...['txt', 'srt', 'sub', 'nfo', 'md', 'pdf', 'json'].map(
        (extension) => [extension, FileText] as const,
    ),
]);

function entryIcon(entry: MediaEntry) {
    if (entry.isDirectory) {
        return Folder;
    }

    const extension = entry.name.split('.').pop()?.toLowerCase() ?? '';

    return EXTENSION_ICONS[extension] ?? File;
}

const trail = ref<HTMLElement | null>(null);

/** Keeps the deepest breadcrumbs in view while browsing on a phone. */
watch(
    () => props.breadcrumbs,
    async () => {
        await nextTick();

        if (trail.value) {
            trail.value.scrollLeft = trail.value.scrollWidth;
        }
    },
    { immediate: true },
);

const entryPendingDeletion = ref<MediaEntry | null>(null);
const isDeleting = ref(false);
const isConfirmOpen = computed({
    get: () => entryPendingDeletion.value !== null,
    set: (value: boolean) => {
        if (!value) {
            entryPendingDeletion.value = null;
        }
    },
});

function confirmDeletion(): void {
    const entry = entryPendingDeletion.value;

    if (!entry) {
        return;
    }

    isDeleting.value = true;

    router.delete(media.destroy(), {
        data: { path: entry.path },
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            entryPendingDeletion.value = null;
        },
    });
}
</script>

<template>
    <Head title="Медіа" />

    <div class="flex flex-col gap-4 p-4 md:gap-6 md:p-6">
        <PageHeader title="Медіа" description="Файли та теки на медіасервері" />

        <div
            ref="trail"
            class="-mx-1 flex items-center gap-1 overflow-x-auto px-1 py-1"
        >
            <Button
                v-for="(crumb, index) in breadcrumbs"
                :key="crumb.path"
                as-child
                variant="ghost"
                size="sm"
                class="shrink-0"
                :class="
                    index === breadcrumbs.length - 1
                        ? 'text-foreground font-medium'
                        : 'text-muted-foreground'
                "
            >
                <Link
                    :href="media.index.url({ query: { path: crumb.path } })"
                    preserve-scroll
                >
                    <HardDrive v-if="index === 0" class="size-4" />
                    <ChevronRight
                        v-else
                        class="text-muted-foreground -ml-1 size-4"
                    />
                    {{ crumb.name }}
                </Link>
            </Button>
        </div>

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border divide-sidebar-border/60 divide-y overflow-hidden rounded-xl border"
        >
            <Link
                v-if="parentPath !== null"
                :href="media.index.url({ query: { path: parentPath } })"
                preserve-scroll
                class="hover:bg-accent/60 text-muted-foreground flex min-h-14 items-center gap-3 px-4 text-sm transition-colors"
            >
                <CornerLeftUp class="size-5 shrink-0" />
                <span>Вгору</span>
            </Link>

            <div
                v-for="entry in entries"
                :key="entry.path"
                class="hover:bg-accent/40 flex min-h-16 items-stretch transition-colors"
            >
                <Link
                    v-if="entry.isDirectory"
                    :href="media.index.url({ query: { path: entry.path } })"
                    preserve-scroll
                    class="flex min-w-0 flex-1 items-center gap-3 px-4 py-3"
                >
                    <Folder class="text-primary size-5 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ entry.name }}
                        </p>
                        <p class="text-muted-foreground truncate text-xs">
                            {{ formatDateTime(entry.modifiedAt) }}
                        </p>
                    </div>
                    <ChevronRight
                        class="text-muted-foreground size-4 shrink-0"
                    />
                </Link>

                <div
                    v-else
                    class="flex min-w-0 flex-1 items-center gap-3 px-4 py-3"
                >
                    <component
                        :is="entryIcon(entry)"
                        class="text-muted-foreground size-5 shrink-0"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ entry.name }}
                        </p>
                        <p class="text-muted-foreground truncate text-xs">
                            {{ entry.sizeForHumans }} ·
                            {{ formatDateTime(entry.modifiedAt) }}
                        </p>
                    </div>
                </div>

                <button
                    v-if="can('media.delete')"
                    type="button"
                    class="text-muted-foreground hover:text-destructive flex w-12 shrink-0 items-center justify-center transition-colors"
                    :aria-label="`Видалити ${entry.name}`"
                    @click="entryPendingDeletion = entry"
                >
                    <Trash class="size-5" />
                </button>
            </div>

            <EmptyState
                v-if="entries.length === 0"
                title="Тека порожня"
                description="Тут поки що немає ні файлів, ні вкладених тек."
                :icon="Folder"
            />
        </div>
    </div>

    <ConfirmDialog
        v-model:open="isConfirmOpen"
        tone="danger"
        title="Видалити назавжди?"
        :description="
            entryPendingDeletion
                ? `«${entryPendingDeletion.name}» буде видалено з медіасховища. Дію не можна скасувати.`
                : ''
        "
        confirm-label="Видалити"
        cancel-label="Скасувати"
        :processing="isDeleting"
        @confirm="confirmDeletion"
    />
</template>
