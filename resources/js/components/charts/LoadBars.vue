<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { ChartTone } from '@/types';

type Props = {
    /** Load average over 1, 5 and 15 minutes. */
    load: [number, number, number] | number[];
    /** Core count used to normalise the bars; 1.0 per core means saturated. */
    cores: number;
    label?: string;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    label: 'Середнє навантаження',
});

const PERIODS = ['1 хв', '5 хв', '15 хв'] as const;

const cores = computed(() => (props.cores > 0 ? props.cores : 1));

const bars = computed(() =>
    PERIODS.map((period, index) => {
        const raw = props.load[index];
        const value = Number.isFinite(raw) ? Math.max(0, raw) : 0;
        const ratio = value / cores.value;

        return {
            period,
            value,
            valueText: value.toFixed(2),
            height: Math.min(100, Math.max(2, ratio * 100)),
            tone: (ratio >= 1
                ? 'danger'
                : ratio >= 0.7
                  ? 'warn'
                  : 'accent') as ChartTone,
        };
    }),
);

const toneClasses: Record<ChartTone, string> = {
    accent: 'bg-chart-accent',
    ok: 'bg-ok',
    warn: 'bg-warn',
    danger: 'bg-danger',
    neutral: 'bg-muted-foreground',
};

const ariaLabel = computed(
    () =>
        `${props.label} на ${cores.value} ядер: ` +
        bars.value.map((bar) => `${bar.period} — ${bar.valueText}`).join(', '),
);
</script>

<template>
    <figure
        :class="cn('flex w-full flex-col gap-3', props.class)"
        role="img"
        :aria-label="ariaLabel"
    >
        <div class="grid grid-cols-3 items-end gap-3" aria-hidden="true">
            <div
                v-for="bar in bars"
                :key="bar.period"
                class="flex flex-col items-center gap-2"
            >
                <span class="text-sm font-semibold tabular-nums">
                    {{ bar.valueText }}
                </span>
                <div
                    class="bg-chart-track flex h-20 w-full items-end overflow-hidden rounded-md"
                >
                    <div
                        :class="[
                            'load-bar w-full rounded-md',
                            toneClasses[bar.tone],
                        ]"
                        :style="{ height: `${bar.height}%` }"
                    />
                </div>
                <span class="text-muted-foreground text-xs">
                    {{ bar.period }}
                </span>
            </div>
        </div>

        <figcaption class="text-muted-foreground text-xs">
            {{ label }} · {{ cores }} ядер
        </figcaption>
    </figure>
</template>

<style scoped>
.load-bar {
    transition:
        height 500ms cubic-bezier(0.4, 0, 0.2, 1),
        background-color 300ms linear;
}

@media (prefers-reduced-motion: reduce) {
    .load-bar {
        transition: none;
    }
}
</style>
