<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, toggleAppearance } = useAppearance();

const label = computed(() =>
    resolvedAppearance.value === 'dark'
        ? 'Увімкнути світлу тему'
        : 'Увімкнути темну тему',
);
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        class="text-muted-foreground hover:text-foreground size-10 rounded-full md:size-9"
        :aria-label="label"
        :title="label"
        data-test="appearance-toggle"
        @click="toggleAppearance"
    >
        <Moon v-if="resolvedAppearance === 'light'" class="size-5" />
        <Sun v-else class="size-5" />
    </Button>
</template>
