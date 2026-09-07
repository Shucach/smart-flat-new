<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Check, Copy, FileText, Gauge, RadioTower, Users } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import {
    formatBytes,
    formatDateTime,
    formatSpeed,
    pluralizeUk,
} from '@/lib/format';
import torrentFiles from '@/routes/torrents/files';
import torrentLimits from '@/routes/torrents/limits';
import type { TorrentDetail, TorrentFilePriority } from '@/types';

const props = defineProps<{
    torrent: TorrentDetail | null;
    canManage: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

/** Long file lists are drawn in slices so a season pack stays scrollable. */
const FILE_PAGE_SIZE = 100;

const visibleFileCount = ref(FILE_PAGE_SIZE);

const visibleFiles = computed(
    () => props.torrent?.files.slice(0, visibleFileCount.value) ?? [],
);

watch(
    () => props.torrent?.id,
    () => {
        visibleFileCount.value = FILE_PAGE_SIZE;
    },
);

const PRIORITIES: { value: TorrentFilePriority; label: string }[] = [
    { value: 'low', label: 'Низ' },
    { value: 'normal', label: 'Сер' },
    { value: 'high', label: 'Вис' },
];

const pendingFileIndex = ref<number | null>(null);

function updateFile(
    index: number,
    payload: { wanted?: boolean; priority?: TorrentFilePriority },
): void {
    const id = props.torrent?.id;

    if (id === undefined) {
        return;
    }

    pendingFileIndex.value = index;

    router.put(
        torrentFiles.update.url(id),
        { indexes: [index], ...payload },
        {
            preserveScroll: true,
            onFinish: () => {
                pendingFileIndex.value = null;
            },
        },
    );
}

/* ------------------------------------------------------------------ limits */

type SeedMode = 'session' | 'ratio' | 'forever';

/** Empty strings, not nulls: an empty number input reads back as ''. */
const limitsForm = useForm({
    downloadKilobytes: '' as number | string,
    uploadKilobytes: '' as number | string,
    seedRatio: '' as number | string,
    seedForever: false,
    honorsSessionLimits: true,
});

function toNullableNumber(value: number | string): number | null {
    return value === '' ? null : Number(value);
}

const seedMode = ref<SeedMode>('session');

/** Reloads the form whenever a different torrent is opened. */
watch(
    () => props.torrent?.id,
    () => {
        const limits = props.torrent?.limits;

        if (!limits) {
            return;
        }

        limitsForm.defaults({
            downloadKilobytes: limits.downloadKilobytes ?? '',
            uploadKilobytes: limits.uploadKilobytes ?? '',
            seedRatio: limits.seedRatio ?? '',
            seedForever: limits.seedForever,
            honorsSessionLimits: limits.honorsSessionLimits,
        });
        limitsForm.reset();

        seedMode.value = limits.seedForever
            ? 'forever'
            : limits.seedRatio !== null
              ? 'ratio'
              : 'session';
    },
    { immediate: true },
);

function submitLimits(): void {
    const id = props.torrent?.id;

    if (id === undefined) {
        return;
    }

    limitsForm
        .transform((data) => ({
            honorsSessionLimits: data.honorsSessionLimits,
            downloadKilobytes: toNullableNumber(data.downloadKilobytes),
            uploadKilobytes: toNullableNumber(data.uploadKilobytes),
            seedForever: seedMode.value === 'forever',
            seedRatio:
                seedMode.value === 'ratio'
                    ? (toNullableNumber(data.seedRatio) ?? 0)
                    : null,
        }))
        .put(torrentLimits.update.url(id), { preserveScroll: true });
}

/* ------------------------------------------------------------------ magnet */

const isMagnetCopied = ref(false);

async function copyMagnet(): Promise<void> {
    const magnet = props.torrent?.magnetLink;

    if (!magnet) {
        return;
    }

    await navigator.clipboard.writeText(magnet);

    isMagnetCopied.value = true;
    window.setTimeout(() => {
        isMagnetCopied.value = false;
    }, 2000);
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            side="right"
            class="w-full gap-0 overflow-y-auto sm:max-w-xl"
        >
            <SheetHeader class="pr-10">
                <SheetTitle class="text-base leading-snug break-words">
                    {{ torrent?.name ?? 'Торент' }}
                </SheetTitle>
                <SheetDescription v-if="torrent">
                    {{ torrent.statusLabel }} · {{ torrent.sizeForHumans }} ·
                    рейтинг {{ torrent.ratio.toFixed(2) }}
                </SheetDescription>
            </SheetHeader>

            <div v-if="!torrent" class="space-y-3 px-4 pb-6">
                <Skeleton class="h-4 w-2/3" />
                <Skeleton class="h-4 w-1/2" />
                <Skeleton class="h-24 w-full" />
            </div>

            <div v-else class="flex flex-col gap-6 px-4 pb-8">
                <dl
                    class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm tabular-nums"
                >
                    <div>
                        <dt class="text-muted-foreground text-xs">Отримано</dt>
                        <dd>{{ formatBytes(torrent.downloadedBytes) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-xs">Роздано</dt>
                        <dd>{{ formatBytes(torrent.uploadedBytes) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-xs">Швидкість</dt>
                        <dd>
                            {{ formatSpeed(torrent.rateDownload) }} /
                            {{ formatSpeed(torrent.rateUpload) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-xs">Піри</dt>
                        <dd>
                            {{ torrent.peersSendingToUs }}↓
                            {{ torrent.peersGettingFromUs }}↑ з
                            {{ torrent.peersConnected }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-xs">Додано</dt>
                        <dd class="text-xs">
                            {{ formatDateTime(torrent.addedAt) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground text-xs">Завершено</dt>
                        <dd class="text-xs">
                            {{ formatDateTime(torrent.doneAt) }}
                        </dd>
                    </div>
                    <div class="col-span-2 min-w-0">
                        <dt class="text-muted-foreground text-xs">Тека</dt>
                        <dd class="truncate text-xs">
                            {{ torrent.downloadDir }}
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-muted-foreground text-xs">Частини</dt>
                        <dd class="text-xs">
                            {{ torrent.pieceCount }} ×
                            {{ formatBytes(torrent.pieceSizeBytes) }}
                        </dd>
                    </div>
                    <div v-if="torrent.creator" class="col-span-2 min-w-0">
                        <dt class="text-muted-foreground text-xs">Створено</dt>
                        <dd class="truncate text-xs">{{ torrent.creator }}</dd>
                    </div>
                    <div v-if="torrent.comment" class="col-span-2 min-w-0">
                        <dt class="text-muted-foreground text-xs">Коментар</dt>
                        <dd class="text-xs break-words">
                            {{ torrent.comment }}
                        </dd>
                    </div>
                </dl>

                <Button
                    v-if="torrent.magnetLink"
                    variant="outline"
                    size="sm"
                    class="w-full"
                    @click="copyMagnet"
                >
                    <Check v-if="isMagnetCopied" class="size-4" />
                    <Copy v-else class="size-4" />
                    {{
                        isMagnetCopied
                            ? 'Скопійовано'
                            : 'Копіювати магнет-посилання'
                    }}
                </Button>

                <!-- files -->
                <section class="space-y-2">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <FileText class="size-4" />
                        Файли
                        <span class="text-muted-foreground font-normal">
                            {{
                                pluralizeUk(
                                    torrent.files.length,
                                    'файл',
                                    'файли',
                                    'файлів',
                                )
                            }}
                        </span>
                    </h3>

                    <p
                        v-if="torrent.files.length === 0"
                        class="text-muted-foreground text-xs"
                    >
                        Метадані ще не отримані.
                    </p>

                    <ul v-else class="divide-y rounded-lg border">
                        <li
                            v-for="file in visibleFiles"
                            :key="file.index"
                            class="flex items-start gap-2 p-2.5"
                        >
                            <Checkbox
                                :model-value="file.wanted"
                                :disabled="
                                    !canManage ||
                                    pendingFileIndex === file.index
                                "
                                :aria-label="`Завантажувати ${file.name}`"
                                class="mt-0.5 shrink-0"
                                @update:model-value="
                                    updateFile(file.index, {
                                        wanted: $event === true,
                                    })
                                "
                            />

                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-xs"
                                    :class="
                                        file.wanted
                                            ? ''
                                            : 'text-muted-foreground line-through'
                                    "
                                    :title="file.name"
                                >
                                    {{ file.name.split('/').pop() }}
                                </p>
                                <p
                                    class="text-muted-foreground text-[11px] tabular-nums"
                                >
                                    {{ file.sizeForHumans }} ·
                                    {{ file.percentDone }}%
                                </p>
                            </div>

                            <div
                                v-if="canManage"
                                class="flex shrink-0 overflow-hidden rounded-md border"
                                role="group"
                                aria-label="Пріоритет файлу"
                            >
                                <button
                                    v-for="priority in PRIORITIES"
                                    :key="priority.value"
                                    type="button"
                                    class="px-2 py-1 text-[11px] transition-colors"
                                    :class="
                                        file.priority === priority.value
                                            ? 'bg-primary text-primary-foreground'
                                            : 'hover:bg-accent'
                                    "
                                    :disabled="pendingFileIndex === file.index"
                                    @click="
                                        updateFile(file.index, {
                                            priority: priority.value,
                                        })
                                    "
                                >
                                    {{ priority.label }}
                                </button>
                            </div>
                        </li>
                    </ul>

                    <Button
                        v-if="torrent.files.length > visibleFileCount"
                        variant="ghost"
                        size="sm"
                        class="w-full"
                        @click="visibleFileCount += FILE_PAGE_SIZE"
                    >
                        Показати ще
                    </Button>
                </section>

                <!-- trackers -->
                <section class="space-y-2">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <RadioTower class="size-4" />
                        Трекери
                    </h3>

                    <ul class="space-y-2">
                        <li
                            v-for="tracker in torrent.trackers"
                            :key="tracker.id"
                            class="rounded-lg border p-2.5"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <p class="min-w-0 truncate text-xs font-medium">
                                    {{ tracker.host }}
                                </p>
                                <Badge
                                    :variant="
                                        tracker.lastAnnounceSucceeded
                                            ? 'secondary'
                                            : 'destructive'
                                    "
                                    class="shrink-0"
                                >
                                    {{ tracker.lastAnnounceResult ?? '—' }}
                                </Badge>
                            </div>
                            <p
                                class="text-muted-foreground mt-1 text-[11px] tabular-nums"
                            >
                                сідів {{ tracker.seederCount }} · лічерів
                                {{ tracker.leecherCount }}
                            </p>
                        </li>
                    </ul>

                    <p
                        v-if="torrent.trackers.length === 0"
                        class="text-muted-foreground text-xs"
                    >
                        Трекерів немає — торент живе на DHT.
                    </p>
                </section>

                <!-- peers -->
                <section class="space-y-2">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <Users class="size-4" />
                        Піри
                    </h3>

                    <div
                        v-if="torrent.peers.length > 0"
                        class="overflow-x-auto rounded-lg border"
                    >
                        <table class="w-full text-xs tabular-nums">
                            <thead class="text-muted-foreground bg-muted/40">
                                <tr>
                                    <th
                                        class="px-2 py-1.5 text-left font-medium"
                                    >
                                        Адреса
                                    </th>
                                    <th
                                        class="px-2 py-1.5 text-left font-medium"
                                    >
                                        Клієнт
                                    </th>
                                    <th
                                        class="px-2 py-1.5 text-right font-medium"
                                    >
                                        %
                                    </th>
                                    <th
                                        class="px-2 py-1.5 text-right font-medium"
                                    >
                                        ↓
                                    </th>
                                    <th
                                        class="px-2 py-1.5 text-right font-medium"
                                    >
                                        ↑
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="peer in torrent.peers"
                                    :key="peer.address"
                                >
                                    <td class="px-2 py-1.5">
                                        {{ peer.address }}
                                    </td>
                                    <td class="max-w-32 truncate px-2 py-1.5">
                                        {{ peer.client }}
                                    </td>
                                    <td class="px-2 py-1.5 text-right">
                                        {{ peer.progressPercent }}
                                    </td>
                                    <td class="px-2 py-1.5 text-right">
                                        {{ formatSpeed(peer.rateDownload) }}
                                    </td>
                                    <td class="px-2 py-1.5 text-right">
                                        {{ formatSpeed(peer.rateUpload) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p v-else class="text-muted-foreground text-xs">
                        Активних підключень немає.
                    </p>
                </section>

                <!-- limits -->
                <section v-if="canManage" class="space-y-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <Gauge class="size-4" />
                        Обмеження цього торента
                    </h3>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="torrent-down">Завантаження, КБ/с</Label>
                            <Input
                                id="torrent-down"
                                v-model="limitsForm.downloadKilobytes"
                                type="number"
                                min="0"
                                class="h-11 sm:h-9"
                                placeholder="без обмеження"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="torrent-up">Роздача, КБ/с</Label>
                            <Input
                                id="torrent-up"
                                v-model="limitsForm.uploadKilobytes"
                                type="number"
                                min="0"
                                class="h-11 sm:h-9"
                                placeholder="без обмеження"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Роздавати до рейтингу</Label>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                v-for="mode in [
                                    { value: 'session', label: 'Як у сесії' },
                                    { value: 'ratio', label: 'Свій рейтинг' },
                                    { value: 'forever', label: 'Завжди' },
                                ] as const"
                                :key="mode.value"
                                type="button"
                                size="sm"
                                :variant="
                                    seedMode === mode.value
                                        ? 'default'
                                        : 'outline'
                                "
                                @click="seedMode = mode.value"
                            >
                                {{ mode.label }}
                            </Button>

                            <Input
                                v-if="seedMode === 'ratio'"
                                v-model="limitsForm.seedRatio"
                                type="number"
                                min="0"
                                step="0.1"
                                class="h-9 w-24"
                                aria-label="Рейтинг"
                            />
                        </div>
                    </div>

                    <Label class="gap-2">
                        <Checkbox
                            :model-value="limitsForm.honorsSessionLimits"
                            @update:model-value="
                                limitsForm.honorsSessionLimits = $event === true
                            "
                        />
                        Враховувати загальні обмеження швидкості
                    </Label>

                    <Button
                        type="button"
                        size="sm"
                        :disabled="limitsForm.processing"
                        @click="submitLimits"
                    >
                        <Spinner v-if="limitsForm.processing" />
                        Зберегти обмеження
                    </Button>
                </section>
            </div>
        </SheetContent>
    </Sheet>
</template>
