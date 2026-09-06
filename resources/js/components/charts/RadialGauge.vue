<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { ChartThresholds, ChartTone } from '@/types';

type Props = {
    /** Percentage in the 0–100 range. `null` renders an empty gauge. */
    value: number | null;
    label: string;
    sublabel?: string;
    /** Rendered width in pixels; the SVG itself scales with its viewBox. */
    size?: number;
    /** Arc colour switches at these percentages. */
    thresholds?: ChartThresholds;
    /** Base tone used while the value stays below the warn threshold. */
    tone?: ChartTone;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    size: 148,
    tone: 'accent',
});

const RADIUS = 42;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;
const ARC_LENGTH = CIRCUMFERENCE * 0.75;

const percent = computed(() => {
    if (props.value === null || !Number.isFinite(props.value)) {
        return null;
    }

    return Math.min(100, Math.max(0, props.value));
});

const dashOffset = computed(
    () => ARC_LENGTH * (1 - (percent.value ?? 0) / 100),
);

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

const toneClass = computed(
    () =>
        ({
            accent: 'text-chart-accent',
            ok: 'text-ok',
            warn: 'text-warn',
            danger: 'text-danger',
            neutral: 'text-muted-foreground',
        })[activeTone.value],
);

const valueText = computed(() =>
    percent.value === null ? '—' : `${Math.round(percent.value)}%`,
);

const ariaLabel = computed(() =>
    percent.value === null
        ? `${props.label}: немає даних`
        : `${props.label}: ${Math.round(percent.value)}%`,
);
</script>

<template>
    <figure
        :class="cn('mx-auto flex w-full flex-col items-center', props.class)"
        :style="{ maxWidth: `${size}px` }"
        role="img"
        :aria-label="ariaLabel"
    >
        <svg viewBox="0 0 100 100" class="w-full" aria-hidden="true">
            <g
                transform="rotate(-225 50 50)"
                fill="none"
                stroke-linecap="round"
            >
                <circle
                    cx="50"
                    cy="50"
                    :r="RADIUS"
                    class="stroke-chart-track"
                    stroke-width="9"
                    :stroke-dasharray="`${ARC_LENGTH} ${CIRCUMFERENCE}`"
                />
                <circle
                    cx="50"
                    cy="50"
                    :r="RADIUS"
                    :class="['stroke-current', toneClass]"
                    stroke-width="9"
                    :stroke-dasharray="`${ARC_LENGTH} ${CIRCUMFERENCE}`"
                    :stroke-dashoffset="dashOffset"
                    style="
                        transition:
                            stroke-dashoffset 600ms cubic-bezier(0.4, 0, 0.2, 1),
                            stroke 300ms linear;
                    "
                />
            </g>

            <text
                x="50"
                y="47"
                text-anchor="middle"
                dominant-baseline="middle"
                class="fill-foreground text-[26px] font-semibold tabular-nums"
            >
                {{ valueText }}
            </text>
            <text
                v-if="sublabel"
                x="50"
                y="66"
                text-anchor="middle"
                dominant-baseline="middle"
                class="fill-muted-foreground text-[9px] font-medium"
            >
                {{ sublabel }}
            </text>
        </svg>

        <figcaption
            class="text-muted-foreground -mt-1 text-center text-sm font-medium"
        >
            {{ label }}
        </figcaption>
    </figure>
</template>

<style scoped>
@media (prefers-reduced-motion: reduce) {
    circle {
        transition: none !important;
    }
}
</style>
