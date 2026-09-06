<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const { mainNavItems } = useAppNavigation();
const { isCurrentOrParentUrl } = useCurrentUrl();

/** The bar never scrolls, so it keeps at most five reachable destinations. */
const visibleItems = computed(() => mainNavItems.value.slice(0, 5));

function isActive(item: (typeof visibleItems.value)[number]): boolean {
    return isCurrentOrParentUrl(item.activeMatch ?? item.href);
}
</script>

<template>
    <nav
        aria-label="Основна навігація"
        class="bg-background/95 border-sidebar-border/70 pb-safe fixed inset-x-0 bottom-0 z-40 border-t backdrop-blur-sm md:hidden"
    >
        <ul
            class="mx-auto flex w-full max-w-lg items-stretch justify-around px-1"
        >
            <li
                v-for="item in visibleItems"
                :key="item.shortTitle"
                class="min-w-0 flex-1"
            >
                <Link
                    :href="item.href"
                    :aria-current="isActive(item) ? 'page' : undefined"
                    class="flex min-h-[56px] flex-col items-center justify-center gap-1 rounded-lg px-1 py-2 text-center transition-colors"
                    :class="
                        isActive(item)
                            ? 'text-primary'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                >
                    <component
                        :is="item.icon"
                        class="size-5 shrink-0"
                        aria-hidden="true"
                    />
                    <span class="w-full truncate text-[11px] leading-none">
                        {{ item.shortTitle }}
                    </span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
