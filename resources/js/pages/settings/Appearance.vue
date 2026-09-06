<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Вигляд',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Вигляд" />

    <h1 class="sr-only">Налаштування вигляду</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Вигляд"
            description="Оберіть світлу або темну тему інтерфейсу"
        />
        <AppearanceTabs />
    </div>
</template>
