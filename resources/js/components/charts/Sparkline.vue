<script setup lang="ts">
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';
import type { ChartTone } from '@/types';

type Props = {
    /** History, oldest first. Fewer than two points renders a flat line. */
    values: number[];
    /** Upper bound of the scale; defaults to the largest value. */
    max?: number;
    /** Lower bound of the scale; defaults to the smallest value. */
    min?: number;
    tone?: ChartTone;
    /** Accessible description, e.g. «Завантаження CPU за годину». */
    label?: string;
    /** Hides the marker on the newest value. */
    hideMarker?: boolean;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    tone: 'accent',
    label: 'Історія значень',
});

const VIEWBOX_WIDTH = 100;
const VIEWBOX_HEIGHT = 32;
const PADDING = 3;

const gradientId = `sparkline-gradient-${useId()}`;

const points = computed(() =>
    props.values.filter((value) => Number.isFinite(value)),
);

const scale = computed(() => {
    const list = points.value;
    const lower = props.min ?? (list.length > 0 ? Math.min(...list) : 0);
    const upper = props.max ?? (list.length > 0 ? Math.max(...list) : 1);
    const span = upper - lower;

    return { lower, span: span > 0 ? span : 1, flat: span <= 0 };
});

const coordinates = computed(() => {
    const list = points.value;

    if (list.length === 0) {
        return [];
    }

    const usableHeight = VIEWBOX_HEIGHT - PADDING * 2;
    const step = list.length > 1 ? VIEWBOX_WIDTH / (list.length - 1) : 0;

    return list.map((value, index) => {
        const ratio = scale.value.flat
            ? 0.5
            : (value - scale.value.lower) / scale.value.span;

        return {
            x: list.length > 1 ? index * step : VIEWBOX_WIDTH / 2,
            y: VIEWBOX_HEIGHT - PADDING - ratio * usableHeight,
        };
    });
});

const linePath = computed(() => {
    const list = coordinates.value;

    if (list.length === 0) {
        return '';
    }

    if (list.length === 1) {
        return `M0,${list[0].y} L${VIEWBOX_WIDTH},${list[0].y}`;
    }

    return list
        .map(
            (point, index) =>
                `${index === 0 ? 'M' : 'L'}${point.x.toFixed(2)},${point.y.toFixed(2)}`,
        )
        .join(' ');
});

const areaPath = computed(() =>
    linePath.value === ''
        ? ''
        : `${linePath.value} L${VIEWBOX_WIDTH},${VIEWBOX_HEIGHT} L0,${VIEWBOX_HEIGHT} Z`,
);

const marker = computed(() => {
    const list = coordinates.value;

    if (props.hideMarker || list.length === 0) {
        return null;
    }

    const last = list[list.length - 1];

    return {
        left: `${(last.x / VIEWBOX_WIDTH) * 100}%`,
        top: `${(last.y / VIEWBOX_HEIGHT) * 100}%`,
    };
});

const toneClass = computed(
    () =>
        ({
            accent: 'text-chart-accent',
            ok: 'text-ok',
            warn: 'text-warn',
            danger: 'text-danger',
            neutral: 'text-muted-foreground',
        })[props.tone],
);

const ariaLabel = computed(() => {
    const list = points.value;

    if (list.length === 0) {
        return `${props.label}: немає даних`;
    }

    return `${props.label}: від ${list[0]} до ${list[list.length - 1]}`;
});
</script>

<template>
    <div
        :class="cn('relative h-10 w-full', toneClass, props.class)"
        role="img"
        :aria-label="ariaLabel"
    >
        <svg
            :viewBox="`0 0 ${VIEWBOX_WIDTH} ${VIEWBOX_HEIGHT}`"
            preserveAspectRatio="none"
            class="h-full w-full overflow-visible"
            aria-hidden="true"
        >
            <defs>
                <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
                    <stop
                        offset="0%"
                        stop-color="currentColor"
                        stop-opacity="0.28"
                    />
                    <stop
                        offset="100%"
                        stop-color="currentColor"
                        stop-opacity="0"
                    />
                </linearGradient>
            </defs>

            <path
                v-if="areaPath"
                :d="areaPath"
                :fill="`url(#${gradientId})`"
                stroke="none"
            />
            <path
                v-if="linePath"
                :d="linePath"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                vector-effect="non-scaling-stroke"
            />
        </svg>

        <span
            v-if="marker"
            class="bg-background absolute size-2 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-current"
            :style="marker"
            aria-hidden="true"
        />

        <p
            v-if="points.length === 0"
            class="text-muted-foreground absolute inset-0 flex items-center justify-center text-xs"
        >
            Немає даних
        </p>
    </div>
</template>
