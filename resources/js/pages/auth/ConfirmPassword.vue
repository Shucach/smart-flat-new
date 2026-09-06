<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/PasskeyVerify.vue';

defineOptions({
    layout: {
        title: 'Підтвердіть пароль',
        description:
            'Це захищений розділ. Введіть пароль ще раз, щоб продовжити.',
    },
});
</script>

<template>
    <Head title="Підтвердження пароля" />

    <PasskeyVerify
        :routes="{
            options: confirmOptions(),
            submit: confirmStore(),
        }"
        label="Підтвердити ключем доступу"
        loading-label="Підтверджуємо…"
        separator="Або введіть пароль"
    />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">Пароль</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block h-11 w-full sm:h-9"
                    required
                    autocomplete="current-password"
                    placeholder="Пароль"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="h-11 w-full sm:h-10"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    Підтвердити
                </Button>
            </div>
        </div>
    </Form>
</template>
