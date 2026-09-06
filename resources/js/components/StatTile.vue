<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import { TrendingDown, TrendingUp } from '@lucide/vue';
import { computed } from 'vue';
import Sparkline from '@/components/charts/Sparkline.vue';
import { cn } from '@/lib/utils';
import type { ChartTone } from '@/types';

type Trend = {
    /** Already formatted, e.g. «+12%» or «−3 ГБ». */
    text: string;
    direction: 'up' | 'down' | 'flat';
    /** Overrides the automatic colouring of the trend chip. */
    tone?: ChartTone;
};

type Props = {
    label: string;
    value: string | number;
    /** Rendered smaller right next to the value, e.g. «ГБ» or «°C». */
    unit?: string;
    icon?: LucideIcon;
    /** Tints the icon badge and the sparkline. */
    tone?: ChartTone;
    /** Turns the whole tile into an Inertia link. */
    href?: NonNullable<InertiaLinkProps['href']>;
    trend?: Trend;
    /** Compact history rendered under the value. */
    sparkline?: number[];
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    tone: 'accent',
});

const badgeClasses: Record<ChartTone, string> = {
    accent: 'bg-chart-accent/12 text-chart-accent',
    ok: 'bg-ok-soft text-ok',
    warn: 'bg-warn-soft text-warn',
    danger: 'bg-danger-soft text-danger',
    neutral: 'bg-muted text-muted-foreground',
};

const trendTone = computed<ChartTone>(() => {
    if (!props.trend) {
        return 'neutral';
    }

    if (props.trend.tone) {
        return props.trend.tone;
    }

    return props.trend.direction === 'flat' ? 'neutral' : 'accent';
});

const trendClasses = computed(
    () =>
        ({
            accent: 'text-chart-accent',
            ok: 'text-ok',
            warn: 'text-warn',
            danger: 'text-danger',
            neutral: 'text-muted-foreground',
        })[trendTone.value],
);
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        :class="
            cn(
                'bg-card text-card-foreground flex min-h-[7rem] flex-col justify-between gap-3 rounded-xl border p-4 transition-colors',
                href &&
                    'hover:bg-accent/50 active:bg-accent focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                props.class,
            )
        "
    >
        <div class="flex items-start justify-between gap-2">
            <span
                class="text-muted-foreground text-sm leading-tight font-medium"
            >
                {{ label }}
            </span>
            <span
                v-if="icon"
                :class="
                    cn(
                        'flex size-8 shrink-0 items-center justify-center rounded-lg',
                        badgeClasses[tone],
                    )
                "
                aria-hidden="true"
            >
                <component :is="icon" class="size-4" />
            </span>
        </div>

        <div class="flex items-end justify-between gap-3">
            <p class="flex items-baseline gap-1">
                <span
                    class="text-2xl leading-none font-semibold tabular-nums sm:text-3xl"
                >
                    {{ value }}
                </span>
                <span
                    v-if="unit"
                    class="text-muted-foreground text-sm font-medium"
                >
                    {{ unit }}
                </span>
            </p>

            <span
                v-if="trend"
                :class="
                    cn(
                        'flex shrink-0 items-center gap-1 text-xs font-semibold tabular-nums',
                        trendClasses,
                    )
                "
            >
                <TrendingUp
                    v-if="trend.direction === 'up'"
                    class="size-3.5"
                    aria-hidden="true"
                />
                <TrendingDown
                    v-else-if="trend.direction === 'down'"
                    class="size-3.5"
                    aria-hidden="true"
                />
                {{ trend.text }}
            </span>
        </div>

        <Sparkline
            v-if="sparkline && sparkline.length > 0"
            :values="sparkline"
            :tone="tone"
            :label="label"
            class="h-8"
        />

        <slot />
    </component>
</template>
