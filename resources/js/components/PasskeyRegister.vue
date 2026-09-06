<script setup lang="ts">
import { usePasskeyRegister } from '@laravel/passkeys/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emit = defineEmits<{
    success: [];
}>();

const getDefaultPasskeyName = () => {
    const ua = navigator.userAgent;

    const browser = [
        { pattern: /Edg|Edge/, name: 'Edge' },
        { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
        { pattern: /Firefox|FxiOS/, name: 'Firefox' },
        { pattern: /Chrome|CriOS/, name: 'Chrome' },
        { pattern: /Safari/, name: 'Safari' },
    ].find(({ pattern }) => pattern.test(ua))?.name;

    const os = [
        { pattern: /iPhone/, name: 'iPhone' },
        { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
        { pattern: /Android/, name: 'Android' },
        { pattern: /Mac/, name: 'Mac' },
        { pattern: /Windows/, name: 'Windows' },
    ].find(({ pattern }) => pattern.test(ua))?.name;

    return [browser, os].filter(Boolean).join(' на ') || '';
};

const name = ref(getDefaultPasskeyName());
const showForm = ref(false);

const { register, isLoading, error, isSupported } = usePasskeyRegister({
    onSuccess: () => {
        name.value = '';
        showForm.value = false;
        emit('success');
    },
});

const handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (!name.value.trim()) {
        return;
    }

    await register(name.value);
};

const handleCancel = () => {
    showForm.value = false;
    name.value = '';
};
</script>

<template>
    <div v-if="!isSupported" class="text-muted-foreground text-sm">
        Цей браузер не підтримує ключі доступу.
    </div>

    <Button
        v-else-if="!showForm"
        variant="outline"
        class="h-11 w-full sm:h-9 sm:w-auto"
        @click="showForm = true"
    >
        Додати ключ доступу
    </Button>

    <form
        v-else
        @submit="handleSubmit"
        class="border-border bg-muted/50 space-y-4 rounded-xl border p-4"
    >
        <div class="grid gap-2">
            <Label for="passkey-name">Назва ключа</Label>
            <Input
                id="passkey-name"
                type="text"
                v-model="name"
                placeholder="Наприклад: iPhone, робочий ноутбук"
                class="border-foreground/20 mt-1 block h-11 w-full sm:h-9"
                autofocus
            />
            <p class="text-muted-foreground text-xs">
                Назва допоможе впізнати цей ключ у списку.
            </p>
        </div>

        <InputError v-if="error" :message="error" />

        <div class="flex flex-col gap-2 sm:flex-row">
            <Button
                type="submit"
                class="h-11 w-full sm:h-9 sm:w-auto"
                :disabled="isLoading || !name.trim()"
            >
                {{ isLoading ? 'Додаємо…' : 'Додати ключ' }}
            </Button>
            <Button
                type="button"
                variant="ghost"
                class="h-11 w-full sm:h-9 sm:w-auto"
                @click="handleCancel"
            >
                Скасувати
            </Button>
        </div>
    </form>
</template>
