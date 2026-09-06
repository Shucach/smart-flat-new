<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Видалення акаунта"
            description="Обліковий запис і всі його дані буде видалено назавжди"
        />
        <div
            class="bg-danger-soft border-danger/20 space-y-4 rounded-xl border p-4"
        >
            <div class="text-danger relative space-y-0.5">
                <p class="font-medium">Увага</p>
                <p class="text-sm">Дію не можна скасувати.</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        class="h-11 w-full sm:h-9 sm:w-auto"
                        data-test="delete-user-button"
                    >
                        Видалити акаунт
                    </Button>
                </DialogTrigger>
                <DialogContent class="rounded-2xl sm:max-w-md">
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                Видалити ваш обліковий запис?
                            </DialogTitle>
                            <DialogDescription>
                                Разом з обліковим записом назавжди зникнуть усі
                                його дані. Введіть пароль, щоб підтвердити
                                видалення.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">
                                Пароль
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                class="h-11 sm:h-9"
                                placeholder="Пароль"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="outline"
                                    class="h-11 w-full sm:h-9 sm:w-auto"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Скасувати
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                class="h-11 w-full sm:h-9 sm:w-auto"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                Видалити акаунт
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
