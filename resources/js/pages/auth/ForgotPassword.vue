<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Забули пароль?',
        description:
            'Вкажіть електронну пошту — ми надішлемо посилання для зміни пароля',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Забули пароль" />

    <div v-if="status" class="text-ok mb-4 text-center text-sm font-medium">
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">Електронна пошта</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    inputmode="email"
                    autofocus
                    placeholder="email@example.com"
                    class="h-11 sm:h-9"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="h-11 w-full sm:h-10"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Надіслати посилання
                </Button>
            </div>
        </Form>

        <div class="text-muted-foreground space-x-1 text-center text-sm">
            <span>Згадали пароль?</span>
            <TextLink :href="login()">Повернутись до входу</TextLink>
        </div>
    </div>
</template>
