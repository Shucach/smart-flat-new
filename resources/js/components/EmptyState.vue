<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { Inbox } from '@lucide/vue';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    icon?: LucideIcon;
    class?: string;
};

const props = defineProps<Props>();
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col items-center justify-center gap-3 px-6 py-12 text-center',
                props.class,
            )
        "
    >
        <span
            class="bg-muted text-muted-foreground flex size-12 items-center justify-center rounded-full"
            aria-hidden="true"
        >
            <component :is="icon ?? Inbox" class="size-6" />
        </span>

        <div class="space-y-1">
            <h3 class="text-base font-semibold">{{ title }}</h3>
            <p
                v-if="description"
                class="text-muted-foreground mx-auto max-w-sm text-sm text-balance"
            >
                {{ description }}
            </p>
        </div>

        <div
            v-if="$slots.default"
            class="mt-1 flex flex-wrap justify-center gap-2"
        >
            <slot />
        </div>
    </div>
</template>
