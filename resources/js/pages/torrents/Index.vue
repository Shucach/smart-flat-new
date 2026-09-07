<script setup lang="ts">
import { Head, router, usePoll } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    HardDrive,
    Inbox,
    Pause,
    Play,
    Search,
    Settings2,
    Trash,
    TriangleAlert,
    Waves,
    X,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatTile from '@/components/StatTile.vue';
import TorrentAddForm from '@/components/torrents/TorrentAddForm.vue';
import TorrentDetailSheet from '@/components/torrents/TorrentDetailSheet.vue';
import TorrentRow from '@/components/torrents/TorrentRow.vue';
import TorrentSettingsSheet from '@/components/torrents/TorrentSettingsSheet.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import { formatBytes, formatSpeed, pluralizeUk } from '@/lib/format';
import torrentRoutes from '@/routes/torrents';
import type {
    Torrent,
    TorrentActionName,
    TorrentDetail,
    TorrentLimitsInfo,
    TorrentSessionSettings,
    TorrentStats,
} from '@/types';

const props = defineProps<{
    torrents: Torrent[];
    stats: TorrentStats | null;
    settings: TorrentSessionSettings | null;
    torrent: TorrentDetail | null;
    error: string | null;
    limits: TorrentLimitsInfo;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Торенти',
                href: torrentRoutes.index(),
            },
        ],
    },
});

const { can } = usePermissions();

const canAdd = computed(() => can('torrent.add'));
const canManage = computed(() => can('torrent.manage'));
const canDelete = computed(() => can('torrent.delete'));

/**
 * Speeds and progress only mean anything while they are current, so the list
 * and the counters are refetched without the detail panel, which is far bigger
 * and only worth reloading while it is open.
 */
const { polling } = usePoll(3000, { only: ['torrents', 'stats'] });

/* ----------------------------------------------------------------- filters */

type StatusFilter = 'all' | 'active' | 'downloading' | 'seeding' | 'paused';

const STATUS_FILTERS: { value: StatusFilter; label: string }[] = [
    { value: 'all', label: 'Усі' },
    { value: 'active', label: 'Активні' },
    { value: 'downloading', label: 'Завантаження' },
    { value: 'seeding', label: 'Роздача' },
    { value: 'paused', label: 'Зупинені' },
];

const search = ref('');
const statusFilter = ref<StatusFilter>('all');

function matchesFilter(torrent: Torrent): boolean {
    switch (statusFilter.value) {
        case 'active':
            return torrent.rateDownload > 0 || torrent.rateUpload > 0;
        case 'downloading':
            return (
                torrent.status === 'downloading' ||
                torrent.status === 'download-wait'
            );
        case 'seeding':
            return (
                torrent.status === 'seeding' || torrent.status === 'seed-wait'
            );
        case 'paused':
            return torrent.isPaused;
        default:
            return true;
    }
}

const visibleTorrents = computed(() => {
    const needle = search.value.trim().toLowerCase();

    return props.torrents
        .filter(
            (torrent) =>
                matchesFilter(torrent) &&
                (needle === '' || torrent.name.toLowerCase().includes(needle)),
        )
        .sort((first, second) => first.queuePosition - second.queuePosition);
});

/* --------------------------------------------------------------- selection */

const selectedIds = ref<number[]>([]);

/** Rows that vanished from the list must not stay selected in the bulk bar. */
watch(
    () => props.torrents,
    (list) => {
        const present = new Set(list.map((torrent) => torrent.id));

        selectedIds.value = selectedIds.value.filter((id) => present.has(id));
    },
);

const allVisibleSelected = computed(
    () =>
        visibleTorrents.value.length > 0 &&
        visibleTorrents.value.every((torrent) =>
            selectedIds.value.includes(torrent.id),
        ),
);

function toggleSelection(id: number, selected: boolean): void {
    selectedIds.value = selected
        ? [...new Set([...selectedIds.value, id])]
        : selectedIds.value.filter((current) => current !== id);
}

function toggleAllVisible(selected: boolean): void {
    const visibleIds = visibleTorrents.value.map((torrent) => torrent.id);

    selectedIds.value = selected
        ? [...new Set([...selectedIds.value, ...visibleIds])]
        : selectedIds.value.filter((id) => !visibleIds.includes(id));
}

/* ----------------------------------------------------------------- actions */

function act(action: TorrentActionName, ids: number[]): void {
    if (ids.length === 0) {
        return;
    }

    router.post(
        torrentRoutes.action.url(),
        { action, ids },
        { preserveScroll: true, preserveState: true },
    );
}

/* ------------------------------------------------------------------ detail */

const openTorrentId = ref<number | null>(null);
const isDetailOpen = computed({
    get: () => openTorrentId.value !== null,
    set: (value: boolean) => {
        if (!value) {
            closeDetail();
        }
    },
});

let detailTimer: number | undefined;

function loadDetail(id: number): void {
    router.get(
        torrentRoutes.index.url({ query: { selected: id } }),
        {},
        {
            only: ['torrent'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function openDetail(id: number): void {
    openTorrentId.value = id;
}

function closeDetail(): void {
    openTorrentId.value = null;

    router.get(
        torrentRoutes.index.url(),
        {},
        {
            only: ['torrent'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

/** An open panel keeps itself current on a slower beat than the list. */
watch(openTorrentId, (id) => {
    window.clearInterval(detailTimer);

    if (id === null) {
        return;
    }

    loadDetail(id);
    detailTimer = window.setInterval(() => loadDetail(id), 5000);
});

onBeforeUnmount(() => window.clearInterval(detailTimer));

const openTorrent = computed(() =>
    props.torrent?.id === openTorrentId.value ? props.torrent : null,
);

/* ---------------------------------------------------------------- deletion */

const idsPendingDeletion = ref<number[]>([]);
const deletesData = ref(false);
const isDeleting = ref(false);

const isConfirmOpen = computed({
    get: () => idsPendingDeletion.value.length > 0,
    set: (value: boolean) => {
        if (!value) {
            idsPendingDeletion.value = [];
        }
    },
});

function askToDelete(ids: number[]): void {
    deletesData.value = false;
    idsPendingDeletion.value = ids;
}

const deletionDescription = computed(() => {
    const ids = idsPendingDeletion.value;

    if (ids.length === 1) {
        const torrent = props.torrents.find((item) => item.id === ids[0]);

        return `«${torrent?.name ?? 'Торент'}» буде прибрано з клієнта.`;
    }

    return `${pluralizeUk(ids.length, 'торент', 'торенти', 'торентів')} буде прибрано з клієнта.`;
});

function confirmDeletion(): void {
    const ids = idsPendingDeletion.value;

    if (ids.length === 0) {
        return;
    }

    isDeleting.value = true;

    router.delete(torrentRoutes.destroy.url(), {
        data: { ids, deleteData: deletesData.value },
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isDeleting.value = false;
            idsPendingDeletion.value = [];
            selectedIds.value = selectedIds.value.filter(
                (id) => !ids.includes(id),
            );

            if (
                openTorrentId.value !== null &&
                ids.includes(openTorrentId.value)
            ) {
                closeDetail();
            }
        },
    });
}

/* ---------------------------------------------------------------- settings */

const isSettingsOpen = ref(false);

const freeSpacePercent = computed(() => {
    const stats = props.stats;

    if (!stats || stats.totalSpaceBytes <= 0) {
        return null;
    }

    return Math.round((stats.freeSpaceBytes / stats.totalSpaceBytes) * 100);
});
</script>

<template>
    <Head title="Торенти" />

    <div class="flex flex-col gap-4 p-4 md:gap-6 md:p-6">
        <PageHeader
            title="Торенти"
            :description="
                stats
                    ? `Transmission ${stats.version}`
                    : 'Керування торент-клієнтом'
            "
        >
            <span
                v-if="stats"
                class="text-muted-foreground flex items-center gap-2 text-xs"
            >
                <span
                    class="size-2 rounded-full"
                    :class="
                        polling
                            ? 'bg-ok animate-pulse'
                            : 'bg-muted-foreground/50'
                    "
                />
                {{
                    pluralizeUk(
                        stats.torrentCount,
                        'торент',
                        'торенти',
                        'торентів',
                    )
                }}
            </span>

            <Button
                v-if="canManage && settings"
                variant="outline"
                size="sm"
                @click="isSettingsOpen = true"
            >
                <Settings2 class="size-4" />
                Налаштування
            </Button>
        </PageHeader>

        <div
            v-if="error"
            class="border-danger/40 bg-danger-soft text-foreground flex items-start gap-2 rounded-lg border p-3 text-sm"
        >
            <TriangleAlert class="text-danger mt-0.5 size-4 shrink-0" />
            <span>{{ error }}</span>
        </div>

        <template v-else>
            <div
                v-if="stats"
                class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4"
            >
                <StatTile
                    label="Завантаження"
                    :value="formatSpeed(stats.rateDownload)"
                    :icon="ArrowDownToLine"
                    tone="accent"
                >
                    <p class="text-muted-foreground text-xs">
                        за сесію {{ formatBytes(stats.sessionDownloadedBytes) }}
                    </p>
                </StatTile>

                <StatTile
                    label="Роздача"
                    :value="formatSpeed(stats.rateUpload)"
                    :icon="ArrowUpFromLine"
                    tone="ok"
                >
                    <p class="text-muted-foreground text-xs">
                        за сесію {{ formatBytes(stats.sessionUploadedBytes) }}
                    </p>
                </StatTile>

                <StatTile
                    label="Активні"
                    :value="`${stats.activeTorrentCount} / ${stats.torrentCount}`"
                    :icon="Waves"
                    tone="neutral"
                >
                    <p class="text-muted-foreground text-xs">
                        зупинено {{ stats.pausedTorrentCount }}
                    </p>
                </StatTile>

                <StatTile
                    label="Вільно на диску"
                    :value="formatBytes(stats.freeSpaceBytes)"
                    :icon="HardDrive"
                    :tone="
                        freeSpacePercent !== null && freeSpacePercent < 10
                            ? 'danger'
                            : 'neutral'
                    "
                >
                    <p
                        v-if="freeSpacePercent !== null"
                        class="text-muted-foreground text-xs"
                    >
                        {{ freeSpacePercent }}% з
                        {{ formatBytes(stats.totalSpaceBytes) }}
                    </p>
                </StatTile>
            </div>

            <TorrentAddForm
                v-if="canAdd && settings"
                :default-download-dir="settings.downloadDir"
                :max-file-megabytes="limits.maxFileMegabytes"
            />

            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative min-w-0 flex-1">
                        <Search
                            class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            class="h-11 pl-9 sm:h-9"
                            placeholder="Пошук за назвою"
                            aria-label="Пошук торентів"
                        />
                    </div>

                    <div class="-mx-1 flex gap-1 overflow-x-auto px-1">
                        <Button
                            v-for="filter in STATUS_FILTERS"
                            :key="filter.value"
                            type="button"
                            size="sm"
                            class="shrink-0"
                            :variant="
                                statusFilter === filter.value
                                    ? 'default'
                                    : 'outline'
                            "
                            @click="statusFilter = filter.value"
                        >
                            {{ filter.label }}
                        </Button>
                    </div>
                </div>

                <div
                    v-if="selectedIds.length > 0"
                    class="bg-card flex flex-wrap items-center gap-2 rounded-xl border p-3"
                >
                    <span class="text-sm font-medium">
                        Обрано {{ selectedIds.length }}
                    </span>

                    <Button variant="ghost" size="sm" @click="selectedIds = []">
                        <X class="size-4" />
                        Зняти
                    </Button>

                    <div class="ml-auto flex flex-wrap gap-2">
                        <Button
                            v-if="canManage"
                            variant="outline"
                            size="sm"
                            @click="act('start', selectedIds)"
                        >
                            <Play class="size-4" />
                            Запустити
                        </Button>
                        <Button
                            v-if="canManage"
                            variant="outline"
                            size="sm"
                            @click="act('stop', selectedIds)"
                        >
                            <Pause class="size-4" />
                            Зупинити
                        </Button>
                        <Button
                            v-if="canDelete"
                            variant="destructive"
                            size="sm"
                            @click="askToDelete(selectedIds)"
                        >
                            <Trash class="size-4" />
                            Видалити
                        </Button>
                    </div>
                </div>

                <div
                    class="border-sidebar-border/70 dark:border-sidebar-border divide-sidebar-border/60 divide-y overflow-hidden rounded-xl border"
                >
                    <div
                        v-if="visibleTorrents.length > 0"
                        class="bg-muted/40 flex items-center gap-3 px-3 py-2 sm:px-4"
                    >
                        <Checkbox
                            :model-value="allVisibleSelected"
                            aria-label="Обрати всі"
                            @update:model-value="
                                toggleAllVisible($event === true)
                            "
                        />
                        <span class="text-muted-foreground text-xs">
                            {{
                                pluralizeUk(
                                    visibleTorrents.length,
                                    'торент',
                                    'торенти',
                                    'торентів',
                                )
                            }}
                        </span>
                    </div>

                    <TorrentRow
                        v-for="torrentRow in visibleTorrents"
                        :key="torrentRow.id"
                        :torrent="torrentRow"
                        :selected="selectedIds.includes(torrentRow.id)"
                        :can-manage="canManage"
                        :can-delete="canDelete"
                        @update:selected="
                            toggleSelection(torrentRow.id, $event)
                        "
                        @open="openDetail(torrentRow.id)"
                        @act="act($event, [torrentRow.id])"
                        @remove="askToDelete([torrentRow.id])"
                    />

                    <EmptyState
                        v-if="visibleTorrents.length === 0"
                        :title="
                            torrents.length === 0
                                ? 'Торентів немає'
                                : 'Нічого не знайдено'
                        "
                        :description="
                            torrents.length === 0
                                ? 'Додайте магнет-посилання або .torrent файл вище.'
                                : 'Спробуйте змінити пошук або фільтр.'
                        "
                        :icon="Inbox"
                    />
                </div>
            </div>
        </template>
    </div>

    <TorrentDetailSheet
        v-model:open="isDetailOpen"
        :torrent="openTorrent"
        :can-manage="canManage"
    />

    <TorrentSettingsSheet
        v-if="settings"
        v-model:open="isSettingsOpen"
        :settings="settings"
    />

    <ConfirmDialog
        v-model:open="isConfirmOpen"
        tone="danger"
        title="Видалити торент?"
        :description="deletionDescription"
        confirm-label="Видалити"
        cancel-label="Скасувати"
        :processing="isDeleting"
        @confirm="confirmDeletion"
    >
        <Label
            class="border-danger/40 bg-danger-soft gap-2 rounded-lg border p-3"
        >
            <Checkbox
                :model-value="deletesData"
                @update:model-value="deletesData = $event === true"
            />
            <span class="text-sm">
                Видалити також завантажені файли з диска
            </span>
        </Label>
    </ConfirmDialog>
</template>
