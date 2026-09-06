<script setup lang="ts">
import AppBottomNav from '@/components/AppBottomNav.vue';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
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
    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <slot />
            <div class="pb-safe h-14 shrink-0 md:hidden" aria-hidden="true" />
        </AppContent>
        <AppBottomNav />
        <Toaster position="top-center" />
    </AppShell>
</template>
