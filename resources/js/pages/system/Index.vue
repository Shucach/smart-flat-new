<script setup lang="ts">
import { Head, router, usePoll } from '@inertiajs/vue3';
import {
    Clock,
    Cpu,
    HardDrive,
    MemoryStick,
    Power,
    RotateCcw,
    Server,
    Thermometer,
    TriangleAlert,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import LoadBars from '@/components/charts/LoadBars.vue';
import MeterBar from '@/components/charts/MeterBar.vue';
import RadialGauge from '@/components/charts/RadialGauge.vue';
import Sparkline from '@/components/charts/Sparkline.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import StatTile from '@/components/StatTile.vue';
import { Button } from '@/components/ui/button';
import {
    formatBytes,
    formatBytesRatio,
    formatDuration,
    formatTemperature,
    formatTime,
    pluralizeUk,
} from '@/lib/format';
import system from '@/routes/system';
import type { ChartTone, SystemSnapshot } from '@/types';

const props = defineProps<{
    snapshot: SystemSnapshot;
    canPower: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Система',
                href: system.index(),
            },
        ],
    },
});

const { polling } = usePoll(3000, { only: ['snapshot'] });

/* ----------------------------------------------------------------- history */

const HISTORY_LENGTH = 60;

const cpuHistory = ref<number[]>([props.snapshot.cpu.usagePercent]);
const memoryHistory = ref<number[]>([props.snapshot.memory.usagePercent]);

function pushSample(buffer: number[], value: number): number[] {
    const next = [...buffer, value];

    return next.length > HISTORY_LENGTH
        ? next.slice(next.length - HISTORY_LENGTH)
        : next;
}

watch(
    () => props.snapshot.capturedAt,
    () => {
        cpuHistory.value = pushSample(
            cpuHistory.value,
            props.snapshot.cpu.usagePercent,
        );
        memoryHistory.value = pushSample(
            memoryHistory.value,
            props.snapshot.memory.usagePercent,
        );
    },
);

/* -------------------------------------------------------------------- tone */

const temperatureTone = computed<ChartTone>(() => {
    const temperature = props.snapshot.cpu.temperatureCelsius;

    if (temperature === null) {
        return 'neutral' as const;
    }

    if (temperature >= 75) {
        return 'danger' as const;
    }

    if (temperature >= 60) {
        return 'warn' as const;
    }

    return 'ok' as const;
});

const hasSwap = computed(() => props.snapshot.memory.swapTotalBytes > 0);

/* ------------------------------------------------------------------- power */

type PowerAction = 'reboot' | 'shutdown';

const pendingAction = ref<PowerAction | null>(null);
const isSendingPower = ref(false);

const isPowerDialogOpen = computed({
    get: () => pendingAction.value !== null,
    set: (value: boolean) => {
        if (!value) {
            pendingAction.value = null;
        }
    },
});

const powerDialogCopy = computed(() =>
    pendingAction.value === 'shutdown'
        ? {
              title: 'Вимкнути сервер?',
              description:
                  'Сервер справді вимкнеться. Панель стане недоступною, і увімкнути його доведеться вручну.',
              confirmLabel: 'Вимкнути',
          }
        : {
              title: 'Перезавантажити сервер?',
              description:
                  'Сервер перезавантажиться. Медіа й рамка будуть недоступні кілька хвилин.',
              confirmLabel: 'Перезавантажити',
          },
);

function confirmPowerAction(): void {
    const action = pendingAction.value;

    if (!action) {
        return;
    }

    isSendingPower.value = true;

    router.post(
        system.power.url(),
        { action },
        {
            preserveScroll: true,
            onFinish: () => {
                isSendingPower.value = false;
                pendingAction.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Система" />

    <div class="flex flex-col gap-4 p-4 md:gap-6 md:p-6">
        <PageHeader
            title="Система"
            description="Стан медіасервера в реальному часі"
        >
            <span class="text-muted-foreground flex items-center gap-2 text-xs">
                <span
                    class="size-2 rounded-full"
                    :class="
                        polling
                            ? 'bg-ok animate-pulse'
                            : 'bg-muted-foreground/50'
                    "
                />
                Оновлено {{ formatTime(snapshot.capturedAt) }}
            </span>
        </PageHeader>

        <div class="grid grid-cols-2 gap-3 md:gap-4">
            <SectionCard>
                <div class="flex flex-col items-center gap-3">
                    <RadialGauge
                        :value="snapshot.cpu.usagePercent"
                        label="Процесор"
                        :sublabel="
                            pluralizeUk(
                                snapshot.cpu.cores,
                                'ядро',
                                'ядра',
                                'ядер',
                            )
                        "
                        :thresholds="{ warn: 70, danger: 90 }"
                    />
                    <Sparkline
                        :values="cpuHistory"
                        :max="100"
                        :min="0"
                        label="Завантаження процесора"
                        class="w-full"
                    />
                </div>
            </SectionCard>

            <SectionCard>
                <div class="flex flex-col items-center gap-3">
                    <RadialGauge
                        :value="snapshot.memory.usagePercent"
                        label="Памʼять"
                        :sublabel="
                            formatBytesRatio(
                                snapshot.memory.usedBytes,
                                snapshot.memory.totalBytes,
                            )
                        "
                        :thresholds="{ warn: 75, danger: 90 }"
                    />
                    <Sparkline
                        :values="memoryHistory"
                        :max="100"
                        :min="0"
                        label="Використання памʼяті"
                        class="w-full"
                    />
                </div>
            </SectionCard>
        </div>

        <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
            <StatTile
                label="Аптайм"
                :value="formatDuration(snapshot.host.uptimeSeconds)"
                :icon="Clock"
                tone="neutral"
            >
                <p class="text-muted-foreground text-xs">
                    Запущено {{ formatTime(snapshot.host.bootedAt) }}
                </p>
            </StatTile>
            <StatTile
                v-if="snapshot.cpu.temperatureCelsius !== null"
                label="Температура CPU"
                :value="formatTemperature(snapshot.cpu.temperatureCelsius)"
                :icon="Thermometer"
                :tone="temperatureTone"
            />
            <StatTile
                label="Ядра"
                :value="String(snapshot.cpu.cores)"
                :icon="Cpu"
                tone="neutral"
            />
            <StatTile
                label="Хост"
                :value="snapshot.host.name"
                :icon="Server"
                tone="neutral"
            >
                <p class="text-muted-foreground truncate text-xs">
                    {{ snapshot.host.os }}
                </p>
            </StatTile>
            <StatTile
                v-if="hasSwap"
                label="Swap"
                :value="
                    formatBytesRatio(
                        snapshot.memory.swapUsedBytes,
                        snapshot.memory.swapTotalBytes,
                    )
                "
                :icon="MemoryStick"
                tone="neutral"
            />
            <StatTile
                label="Вільна памʼять"
                :value="formatBytes(snapshot.memory.freeBytes)"
                :icon="MemoryStick"
                tone="neutral"
            />
        </div>

        <SectionCard
            title="Середнє навантаження"
            description="За 1, 5 та 15 хвилин"
        >
            <LoadBars
                :load="snapshot.cpu.loadAverage"
                :cores="snapshot.cpu.cores"
            />
        </SectionCard>

        <SectionCard title="Диски">
            <div class="flex flex-col gap-4">
                <MeterBar
                    v-for="disk in snapshot.disks"
                    :key="disk.mountPoint"
                    :value="disk.usagePercent"
                    :label="disk.label || disk.mountPoint"
                    :value-text="
                        formatBytesRatio(disk.usedBytes, disk.totalBytes)
                    "
                    :thresholds="{ warn: 80, danger: 92 }"
                />
                <p
                    v-if="snapshot.disks.length === 0"
                    class="text-muted-foreground flex items-center gap-2 text-sm"
                >
                    <HardDrive class="size-4" />
                    Дисків не знайдено.
                </p>
            </div>
        </SectionCard>

        <SectionCard
            v-if="canPower"
            title="Керування живленням"
            description="Ці дії впливають на реальний сервер — переконайтеся, що ніхто ним не користується."
        >
            <div
                class="border-warn/40 bg-warn-soft text-foreground mb-4 flex items-start gap-2 rounded-lg border p-3 text-sm"
            >
                <TriangleAlert class="text-warn mt-0.5 size-4 shrink-0" />
                <span>
                    Після вимкнення увімкнути сервер віддалено не вийде.
                </span>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <Button
                    variant="outline"
                    class="flex-1"
                    :disabled="isSendingPower"
                    @click="pendingAction = 'reboot'"
                >
                    <RotateCcw class="size-4" />
                    Перезавантажити
                </Button>
                <Button
                    variant="destructive"
                    class="flex-1"
                    :disabled="isSendingPower"
                    @click="pendingAction = 'shutdown'"
                >
                    <Power class="size-4" />
                    Вимкнути
                </Button>
            </div>
        </SectionCard>
    </div>

    <ConfirmDialog
        v-model:open="isPowerDialogOpen"
        tone="danger"
        :title="powerDialogCopy.title"
        :description="powerDialogCopy.description"
        :confirm-label="powerDialogCopy.confirmLabel"
        cancel-label="Скасувати"
        :processing="isSendingPower"
        @confirm="confirmPowerAction"
    />
</template>
