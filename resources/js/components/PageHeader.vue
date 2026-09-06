<script setup lang="ts">
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    /** Small muted line above the title, e.g. the current section. */
    eyebrow?: string;
    class?: string;
};

const props = defineProps<Props>();
</script>

<template>
    <header
        :class="
            cn(
                'flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between',
                props.class,
            )
        "
    >
        <div class="min-w-0 space-y-1">
            <p
                v-if="eyebrow"
                class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1
                class="truncate text-xl font-semibold tracking-tight sm:text-2xl"
            >
                {{ title }}
            </h1>
            <p v-if="description" class="text-muted-foreground text-sm">
                {{ description }}
            </p>
        </div>

        <div
            v-if="$slots.default"
            class="flex shrink-0 flex-wrap items-center gap-2"
        >
            <slot />
        </div>
    </header>
</template>
