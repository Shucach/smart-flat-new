<script setup lang="ts">
import {
    CheckCircle2,
    Film,
    Image as ImageIcon,
    TriangleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import SectionCard from '@/components/SectionCard.vue';
import type { FrameUpload } from '@/types';

const props = defineProps<{
    uploads: FrameUpload[];
}>();

/**
 * Converting a clip into the H.264 the panel decodes takes minutes on this
 * hardware, so the queue is shown rather than hidden: without it an upload just
 * seems to vanish until it appears in the gallery.
 */
const active = computed(() =>
    props.uploads.filter((upload) => upload.status !== 'completed'),
);

const finished = computed(
    () =>
        props.uploads.filter((upload) => upload.status === 'completed').length,
);

function isFailed(upload: FrameUpload): boolean {
    return upload.status === 'failed';
}

function detail(upload: FrameUpload): string {
    if (upload.status === 'failed') {
        return upload.message ?? 'Не вдалося обробити файл.';
    }

    if (upload.status === 'queued') {
        return upload.kind === 'video'
            ? 'Очікує на перекодування'
            : 'Очікує в черзі';
    }

    return `${upload.statusLabel} · ${upload.progress}%`;
}
</script>

<template>
    <SectionCard
        v-if="uploads.length > 0"
        title="Обробка файлів"
        description="Відео перекодовується під панель рамки — це триває кілька хвилин і відбувається у фоні."
    >
        <div class="flex flex-col gap-3">
            <div
                v-for="upload in active"
                :key="upload.id"
                class="flex items-start gap-3"
            >
                <component
                    :is="upload.kind === 'video' ? Film : ImageIcon"
                    class="text-muted-foreground mt-0.5 size-4 shrink-0"
                />

                <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="truncate text-sm font-medium">
                            {{ upload.name }}
                        </span>
                        <span
                            class="shrink-0 text-xs"
                            :class="
                                isFailed(upload)
                                    ? 'text-red-600 dark:text-red-500'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ detail(upload) }}
                        </span>
                    </div>

                    <div
                        v-if="!isFailed(upload)"
                        class="bg-muted h-1.5 w-full overflow-hidden rounded-full"
                    >
                        <div
                            class="bg-primary h-full transition-all duration-500"
                            :class="{ 'animate-pulse': upload.progress === 0 }"
                            :style="{
                                width: `${Math.max(upload.progress, 4)}%`,
                            }"
                        />
                    </div>

                    <p
                        v-else
                        class="flex items-start gap-1.5 text-xs text-red-600 dark:text-red-500"
                    >
                        <TriangleAlert class="mt-px size-3.5 shrink-0" />
                        <span>{{
                            upload.message ?? 'Не вдалося обробити файл.'
                        }}</span>
                    </p>
                </div>
            </div>

            <p
                v-if="finished > 0"
                class="text-muted-foreground flex items-center gap-1.5 text-xs"
            >
                <CheckCircle2
                    class="size-3.5 text-emerald-600 dark:text-emerald-500"
                />
                Готово: {{ finished }}. Натисніть «Оновити рамку», щоб панель
                почала їх показувати.
            </p>
        </div>
    </SectionCard>
</template>
