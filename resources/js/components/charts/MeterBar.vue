<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { ChartThresholds, ChartTone } from '@/types';

type Props = {
    /** Fill percentage in the 0–100 range. `null` renders an empty track. */
    value: number | null;
    label: string;
    /** Human readable figure, e.g. «412 ГБ з 1,8 ТБ». */
    valueText?: string;
    sublabel?: string;
    /** Fill colour switches at these percentages. */
    thresholds?: ChartThresholds;
    /** Base tone used while the value stays below the warn threshold. */
    tone?: ChartTone;
    /** Hides the percentage badge on the right of the label row. */
    hidePercent?: boolean;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    tone: 'accent',
});

const percent = computed(() => {
    if (props.value === null || !Number.isFinite(props.value)) {
        return null;
    }

    return Math.min(100, Math.max(0, props.value));
});

const activeTone = computed<ChartTone>(() => {
    const current = percent.value;
    const thresholds = props.thresholds;

    if (current === null || !thresholds) {
        return props.tone;
    }

    if (current >= thresholds.danger) {
        return 'danger';
    }

    if (current >= thresholds.warn) {
        return 'warn';
    }

    return props.tone;
});

const fillClass = computed(
    () =>
        ({
            accent: 'bg-chart-accent',
            ok: 'bg-ok',
            warn: 'bg-warn',
            danger: 'bg-danger',
            neutral: 'bg-muted-foreground',
        })[activeTone.value],
);

const percentText = computed(() =>
    percent.value === null ? '—' : `${Math.round(percent.value)}%`,
);
</script>

<template>
    <div :class="cn('flex w-full flex-col gap-2', props.class)">
        <div class="flex items-baseline justify-between gap-3">
            <span class="truncate text-sm font-medium">{{ label }}</span>
            <span
                v-if="!hidePercent"
                class="text-muted-foreground shrink-0 text-sm font-semibold tabular-nums"
            >
                {{ percentText }}
            </span>
        </div>

        <div
            class="bg-chart-track h-2.5 w-full overflow-hidden rounded-full"
            role="progressbar"
            :aria-label="label"
            :aria-valuenow="percent ?? undefined"
            aria-valuemin="0"
            aria-valuemax="100"
            :aria-valuetext="valueText ?? percentText"
        >
            <div
                :class="[
                    'meter-fill h-full rounded-full',
                    fillClass,
                    percent === null || percent === 0 ? 'opacity-0' : '',
                ]"
                :style="{ width: `${percent ?? 0}%` }"
            />
        </div>

        <p
            v-if="valueText || sublabel"
            class="text-muted-foreground text-xs tabular-nums"
        >
            {{ valueText ?? sublabel }}
        </p>
    </div>
</template>

<style scoped>
.meter-fill {
    transition:
        width 500ms cubic-bezier(0.4, 0, 0.2, 1),
        background-color 300ms linear;
}

@media (prefers-reduced-motion: reduce) {
    .meter-fill {
        transition: none;
    }
}
</style>
