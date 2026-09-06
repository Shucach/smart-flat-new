<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { Check, Copy, ScanLine } from '@lucide/vue';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useAppearance } from '@/composables/useAppearance';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';
import type { TwoFactorConfigContent } from '@/types';

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const { resolvedAppearance } = useAppearance();

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { copy, copied } = useClipboard();
const { qrCodeSvg, manualSetupKey, clearSetupData, fetchSetupData, errors } =
    useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<string>('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: 'Двоетапну перевірку увімкнено',
            description:
                'Відскануйте QR-код або введіть ключ налаштування вручну у застосунку-автентифікаторі.',
            buttonText: 'Закрити',
        };
    }

    if (showVerificationStep.value) {
        return {
            title: 'Підтвердіть код',
            description:
                'Введіть шестизначний код із застосунку-автентифікатора',
            buttonText: 'Далі',
        };
    }

    return {
        title: 'Увімкнення двоетапної перевірки',
        description:
            'Відскануйте QR-код або введіть ключ налаштування вручну у застосунку-автентифікаторі',
        buttonText: 'Далі',
    };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = '';
};

watch(
    () => isOpen.value,
    async (isOpen) => {
        if (!isOpen) {
            resetModalState();

            return;
        }

        if (!qrCodeSvg.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent
            class="max-h-[90svh] overflow-y-auto rounded-2xl sm:max-w-md"
        >
            <DialogHeader class="flex items-center justify-center">
                <div
                    class="border-border bg-card mb-3 w-auto rounded-full border p-0.5 shadow-sm"
                >
                    <div
                        class="border-border bg-muted relative overflow-hidden rounded-full border p-2.5"
                    >
                        <div
                            class="absolute inset-0 grid grid-cols-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`col-${i}`"
                                class="border-border border-r last:border-r-0"
                            />
                        </div>
                        <div
                            class="absolute inset-0 grid grid-rows-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`row-${i}`"
                                class="border-border border-b last:border-b-0"
                            />
                        </div>
                        <ScanLine
                            class="text-foreground relative z-20 size-6"
                        />
                    </div>
                </div>
                <DialogTitle>{{ modalConfig.title }}</DialogTitle>
                <DialogDescription class="text-center">
                    {{ modalConfig.description }}
                </DialogDescription>
            </DialogHeader>

            <div
                class="relative flex w-auto flex-col items-center justify-center space-y-5"
            >
                <template v-if="!showVerificationStep">
                    <AlertError v-if="errors?.length" :errors="errors" />
                    <template v-else>
                        <div
                            class="relative mx-auto flex max-w-md items-center overflow-hidden"
                        >
                            <div
                                class="border-border relative mx-auto aspect-square w-64 overflow-hidden rounded-lg border"
                            >
                                <div
                                    v-if="!qrCodeSvg"
                                    class="bg-background absolute inset-0 z-10 flex aspect-square h-auto w-full animate-pulse items-center justify-center"
                                >
                                    <Spinner class="size-6" />
                                </div>
                                <div
                                    v-else
                                    class="relative z-10 overflow-hidden border p-5"
                                >
                                    <div
                                        v-html="qrCodeSvg"
                                        class="flex aspect-square size-full items-center justify-center"
                                        :style="{
                                            filter:
                                                resolvedAppearance === 'dark'
                                                    ? 'invert(1) brightness(1.5)'
                                                    : undefined,
                                        }"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex w-full items-center space-x-5">
                            <Button
                                class="h-11 w-full sm:h-10"
                                @click="handleModalNextStep"
                            >
                                {{ modalConfig.buttonText }}
                            </Button>
                        </div>

                        <div
                            class="relative flex w-full items-center justify-center"
                        >
                            <div
                                class="bg-border absolute inset-0 top-1/2 h-px w-full"
                            />
                            <span
                                class="bg-background text-muted-foreground relative px-2 py-1 text-sm"
                            >
                                або введіть ключ вручну
                            </span>
                        </div>

                        <div
                            class="flex w-full items-center justify-center space-x-2"
                        >
                            <div
                                class="border-border flex w-full items-stretch overflow-hidden rounded-xl border"
                            >
                                <div
                                    v-if="!manualSetupKey"
                                    class="bg-muted flex h-full w-full items-center justify-center p-3"
                                >
                                    <Spinner />
                                </div>
                                <template v-else>
                                    <input
                                        type="text"
                                        readonly
                                        aria-label="Ключ налаштування"
                                        :value="manualSetupKey"
                                        class="bg-background text-foreground h-full min-w-0 flex-1 p-3 font-mono text-xs sm:text-sm"
                                    />
                                    <button
                                        type="button"
                                        :aria-label="
                                            copied
                                                ? 'Ключ скопійовано'
                                                : 'Скопіювати ключ'
                                        "
                                        :title="
                                            copied
                                                ? 'Ключ скопійовано'
                                                : 'Скопіювати ключ'
                                        "
                                        @click="copy(manualSetupKey || '')"
                                        class="border-border hover:bg-muted relative block h-auto w-12 shrink-0 border-l"
                                    >
                                        <Check
                                            v-if="copied"
                                            class="text-ok mx-auto w-4"
                                        />
                                        <Copy v-else class="mx-auto w-4" />
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </template>

                <template v-else>
                    <Form
                        v-bind="confirm.form()"
                        error-bag="confirmTwoFactorAuthentication"
                        reset-on-error
                        @finish="code = ''"
                        @success="isOpen = false"
                        v-slot="{ errors, processing }"
                    >
                        <input type="hidden" name="code" :value="code" />
                        <div
                            ref="pinInputContainerRef"
                            class="relative w-full space-y-3"
                        >
                            <div
                                class="flex w-full flex-col items-center justify-center space-y-3 py-2"
                            >
                                <InputOTP
                                    id="otp"
                                    v-model="code"
                                    :maxlength="6"
                                    :disabled="processing"
                                    autofocus
                                >
                                    <InputOTPGroup>
                                        <InputOTPSlot
                                            v-for="index in 6"
                                            :key="index"
                                            :index="index - 1"
                                            class="h-11 w-11 text-base sm:h-9 sm:w-9 sm:text-sm"
                                        />
                                    </InputOTPGroup>
                                </InputOTP>
                                <InputError :message="errors?.code" />
                            </div>

                            <div class="flex w-full items-center space-x-5">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-11 w-auto flex-1 sm:h-10"
                                    @click="showVerificationStep = false"
                                    :disabled="processing"
                                >
                                    Назад
                                </Button>
                                <Button
                                    type="submit"
                                    class="h-11 w-auto flex-1 sm:h-10"
                                    :disabled="processing || code.length < 6"
                                >
                                    Підтвердити
                                </Button>
                            </div>
                        </div>
                    </Form>
                </template>
            </div>
        </DialogContent>
    </Dialog>
</template>
