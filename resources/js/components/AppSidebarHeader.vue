<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();

/** The mobile header only has room for the name of the current section. */
const sectionTitle = computed(
    () => props.breadcrumbs.at(-1)?.title ?? 'SmartFlat',
);
</script>

<template>
    <header
        class="bg-background/95 border-sidebar-border/70 sticky top-0 z-30 flex h-14 shrink-0 items-center gap-2 border-b px-4 backdrop-blur-sm md:h-16 md:px-6"
    >
        <SidebarTrigger class="-ml-1 hidden md:inline-flex" />

        <div v-if="breadcrumbs.length > 0" class="hidden min-w-0 md:block">
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>

        <span class="truncate text-base font-semibold md:hidden">
            {{ sectionTitle }}
        </span>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon"
                    class="ml-auto size-10 rounded-full md:hidden"
                    aria-label="Меню користувача"
                >
                    <Avatar class="size-8 overflow-hidden rounded-full">
                        <AvatarImage
                            v-if="user.avatar"
                            :src="user.avatar"
                            :alt="user.name"
                        />
                        <AvatarFallback
                            class="rounded-full bg-neutral-200 text-xs font-semibold text-black dark:bg-neutral-700 dark:text-white"
                        >
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
                <UserMenuContent :user="user" />
            </DropdownMenuContent>
        </DropdownMenu>
    </header>
</template>
