<script setup lang="ts">
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    ChevronsDown,
    ChevronsUp,
    Clock,
    Ellipsis,
    Info,
    Pause,
    Play,
    RadioTower,
    SearchCheck,
    Trash,
    TriangleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatBytes, formatDuration, formatSpeed } from '@/lib/format';
import type { ChartTone, Torrent, TorrentActionName } from '@/types';

const props = defineProps<{
    torrent: Torrent;
    selected: boolean;
    canManage: boolean;
    canDelete: boolean;
}>();

const emit = defineEmits<{
    'update:selected': [value: boolean];
    open: [];
    act: [action: TorrentActionName];
    remove: [];
}>();

const STATUS_ICONS = {
    stopped: Pause,
    'check-wait': Clock,
    checking: SearchCheck,
    'download-wait': Clock,
    downloading: ArrowDownToLine,
    'seed-wait': Clock,
    seeding: ArrowUpFromLine,
} as const;

const STATUS_TONES: Record<Torrent['status'], ChartTone> = {
    stopped: 'neutral',
    'check-wait': 'warn',
    checking: 'warn',
    'download-wait': 'neutral',
    downloading: 'accent',
    'seed-wait': 'neutral',
    seeding: 'ok',
};

/** A recheck reports its own progress; everything else reports the download. */
const percent = computed(() =>
    props.torrent.status === 'checking'
        ? props.torrent.recheckPercent
        : props.torrent.percentDone,
);

const tone = computed<ChartTone>(() =>
    props.torrent.error ? 'danger' : STATUS_TONES[props.torrent.status],
);

const fillClass = computed(
    () =>
        ({
            accent: 'bg-chart-accent',
            ok: 'bg-ok',
            warn: 'bg-warn',
            danger: 'bg-danger',
            neutral: 'bg-muted-foreground',
        })[tone.value],
);

const badgeClass = computed(
    () =>
        ({
            accent: 'bg-chart-accent/12 text-chart-accent',
            ok: 'bg-ok-soft text-ok',
            warn: 'bg-warn-soft text-warn',
            danger: 'bg-danger-soft text-danger',
            neutral: 'bg-muted text-muted-foreground',
        })[tone.value],
);

/**
 * A magnet link has no file list until the metadata arrives, so until then the
 * only honest thing to show is how much of the metadata is in.
 */
const isFetchingMetadata = computed(() => props.torrent.metadataPercent < 100);

const transferText = computed(() => {
    const torrent = props.torrent;

    if (isFetchingMetadata.value) {
        return `Метадані ${Math.round(torrent.metadataPercent)}%`;
    }

    if (torrent.percentDone >= 100) {
        return `${torrent.sizeForHumans} · роздано ${formatBytes(torrent.uploadedBytes)}`;
    }

    return `${formatBytes(torrent.sizeWhenDoneBytes - torrent.leftUntilDoneBytes)} з ${formatBytes(torrent.sizeWhenDoneBytes)}`;
});
</script>

<template>
    <div class="flex items-start gap-3 px-3 py-3 sm:px-4">
        <Checkbox
            :model-value="selected"
            :aria-label="`Обрати ${torrent.name}`"
            class="mt-1 shrink-0"
            @update:model-value="emit('update:selected', $event === true)"
        />

        <button
            type="button"
            class="min-w-0 flex-1 space-y-2 text-left"
            @click="emit('open')"
        >
            <div class="flex min-w-0 items-start justify-between gap-2">
                <p class="min-w-0 flex-1 truncate text-sm font-medium">
                    {{ torrent.name }}
                </p>
                <span
                    class="flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium"
                    :class="badgeClass"
                >
                    <component
                        :is="STATUS_ICONS[torrent.status]"
                        class="size-3"
                        aria-hidden="true"
                    />
                    {{ torrent.statusLabel }}
                </span>
            </div>

            <div
                class="bg-chart-track h-1.5 w-full overflow-hidden rounded-full"
                role="progressbar"
                :aria-label="torrent.name"
                :aria-valuenow="Math.round(percent)"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div
                    class="torrent-fill h-full rounded-full"
                    :class="[fillClass, percent === 0 ? 'opacity-0' : '']"
                    :style="{ width: `${Math.min(100, percent)}%` }"
                />
            </div>

            <div
                class="text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 text-xs tabular-nums"
            >
                <span class="font-medium">{{ percent.toFixed(1) }}%</span>
                <span>{{ transferText }}</span>
                <span
                    v-if="torrent.rateDownload > 0"
                    class="text-chart-accent flex items-center gap-1"
                >
                    <ArrowDownToLine class="size-3" aria-hidden="true" />
                    {{ formatSpeed(torrent.rateDownload) }}
                </span>
                <span
                    v-if="torrent.rateUpload > 0"
                    class="text-ok flex items-center gap-1"
                >
                    <ArrowUpFromLine class="size-3" aria-hidden="true" />
                    {{ formatSpeed(torrent.rateUpload) }}
                </span>
                <span v-if="torrent.etaSeconds !== null">
                    залишилось {{ formatDuration(torrent.etaSeconds) }}
                </span>
                <span>рейтинг {{ torrent.ratio.toFixed(2) }}</span>
                <span v-if="torrent.peersConnected > 0">
                    {{ torrent.peersSendingToUs }}↓ /
                    {{ torrent.peersGettingFromUs }}↑ з
                    {{ torrent.peersConnected }}
                </span>
                <Badge
                    v-for="label in torrent.labels"
                    :key="label"
                    variant="outline"
                    class="text-[10px]"
                >
                    {{ label }}
                </Badge>
            </div>

            <p
                v-if="torrent.error"
                class="text-danger flex items-start gap-1.5 text-xs"
            >
                <TriangleAlert class="mt-0.5 size-3.5 shrink-0" />
                <span class="min-w-0">{{ torrent.error }}</span>
            </p>
            <p
                v-else-if="torrent.isStalled && !torrent.isPaused"
                class="text-warn text-xs"
            >
                Немає активних підключень.
            </p>
        </button>

        <div class="flex shrink-0 items-center gap-1">
            <Button
                v-if="canManage"
                variant="ghost"
                size="icon"
                class="size-9"
                :aria-label="torrent.isPaused ? 'Запустити' : 'Зупинити'"
                @click="emit('act', torrent.isPaused ? 'start' : 'stop')"
            >
                <Play v-if="torrent.isPaused" class="size-4" />
                <Pause v-else class="size-4" />
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-9"
                        aria-label="Дії з торентом"
                    >
                        <Ellipsis class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <DropdownMenuItem @select="emit('open')">
                        <Info class="mr-2 size-4" />
                        Деталі
                    </DropdownMenuItem>

                    <template v-if="canManage">
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-if="torrent.isPaused"
                            @select="emit('act', 'start-now')"
                        >
                            <Play class="mr-2 size-4" />
                            Запустити поза чергою
                        </DropdownMenuItem>
                        <DropdownMenuItem @select="emit('act', 'verify')">
                            <SearchCheck class="mr-2 size-4" />
                            Перевірити файли
                        </DropdownMenuItem>
                        <DropdownMenuItem @select="emit('act', 'reannounce')">
                            <RadioTower class="mr-2 size-4" />
                            Оновити анонс
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @select="emit('act', 'queue-top')">
                            <ChevronsUp class="mr-2 size-4" />
                            На початок черги
                        </DropdownMenuItem>
                        <DropdownMenuItem @select="emit('act', 'queue-bottom')">
                            <ChevronsDown class="mr-2 size-4" />
                            В кінець черги
                        </DropdownMenuItem>
                    </template>

                    <template v-if="canDelete">
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            @select="emit('remove')"
                        >
                            <Trash class="mr-2 size-4" />
                            Видалити
                        </DropdownMenuItem>
                    </template>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>

<style scoped>
.torrent-fill {
    transition: width 500ms cubic-bezier(0.4, 0, 0.2, 1);
}

@media (prefers-reduced-motion: reduce) {
    .torrent-fill {
        transition: none;
    }
}
</style>
