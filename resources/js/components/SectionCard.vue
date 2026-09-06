<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

type Props = {
    title?: string;
    description?: string;
    icon?: LucideIcon;
    /** Drops the inner padding — use for edge-to-edge lists and tables. */
    flush?: boolean;
    class?: string;
    contentClass?: string;
};

const props = defineProps<Props>();
</script>

<template>
    <section
        :class="
            cn(
                'bg-card text-card-foreground overflow-hidden rounded-xl border',
                props.class,
            )
        "
    >
        <header
            v-if="title || $slots.action || $slots.header"
            class="flex items-start justify-between gap-3 px-4 pt-4 pb-3 sm:px-5"
        >
            <slot name="header">
                <div class="flex min-w-0 items-start gap-3">
                    <span
                        v-if="icon"
                        class="bg-muted text-muted-foreground flex size-8 shrink-0 items-center justify-center rounded-lg"
                        aria-hidden="true"
                    >
                        <component :is="icon" class="size-4" />
                    </span>
                    <div class="min-w-0 space-y-0.5">
                        <h2 class="truncate text-base font-semibold">
                            {{ title }}
                        </h2>
                        <p
                            v-if="description"
                            class="text-muted-foreground text-sm"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
            </slot>

            <div v-if="$slots.action" class="flex shrink-0 items-center gap-2">
                <slot name="action" />
            </div>
        </header>

        <div
            :class="
                cn(
                    flush ? '' : 'px-4 pb-4 sm:px-5 sm:pb-5',
                    !(title || $slots.action || $slots.header) && !flush
                        ? 'pt-4 sm:pt-5'
                        : '',
                    props.contentClass,
                )
            "
        >
            <slot />
        </div>

        <footer
            v-if="$slots.footer"
            class="bg-muted/40 border-t px-4 py-3 sm:px-5"
        >
            <slot name="footer" />
        </footer>
    </section>
</template>
