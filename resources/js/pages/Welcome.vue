<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
</script>

<template>
    <Head title="SmartFlat" />

    <div
        class="bg-background text-foreground flex min-h-svh flex-col items-center justify-center gap-8 px-6 py-12"
    >
        <div class="flex flex-col items-center gap-4 text-center">
            <div
                class="bg-primary text-primary-foreground flex size-14 items-center justify-center rounded-2xl"
            >
                <AppLogoIcon class="size-7 fill-current" />
            </div>
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">SmartFlat</h1>
                <p class="text-muted-foreground max-w-sm text-sm">
                    Панель керування розумною квартирою: медіасховище, розумна
                    рамка та стан сервера — з телефона однією рукою.
                </p>
            </div>
        </div>

        <Button as-child size="lg" class="w-full max-w-xs">
            <Link :href="isAuthenticated ? dashboard() : login()">
                {{ isAuthenticated ? 'До панелі' : 'Увійти' }}
            </Link>
        </Button>
    </div>
</template>
