<script setup lang="ts">
import AppBottomNav from '@/components/AppBottomNav.vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="min-h-0 flex-1">
                <slot />
            </div>
            <div class="pb-safe h-14 shrink-0 md:hidden" aria-hidden="true" />
        </AppContent>
        <AppBottomNav />
        <Toaster position="top-center" />
    </AppShell>
</template>
