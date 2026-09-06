<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-6">
        <Heading
            variant="small"
            title="Двоетапна перевірка"
            description="Додатковий код під час входу — на випадок, якщо пароль стане відомий комусь іншому"
        />

        <div
            v-if="!twoFactorEnabled"
            class="flex flex-col items-start justify-start space-y-4"
        >
            <p class="text-muted-foreground text-sm">
                Коли двоетапну перевірку увімкнено, під час входу ми
                запитуватимемо ще й одноразовий код. Його показує будь-який
                застосунок з підтримкою TOTP на вашому телефоні.
            </p>

            <div class="w-full sm:w-auto">
                <Button
                    v-if="hasSetupData"
                    class="h-11 w-full sm:h-9 sm:w-auto"
                    @click="showSetupModal = true"
                >
                    <ShieldCheck />
                    Продовжити налаштування
                </Button>
                <Form
                    v-else
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                >
                    <Button
                        type="submit"
                        class="h-11 w-full sm:h-9 sm:w-auto"
                        :disabled="processing"
                    >
                        Увімкнути двоетапну перевірку
                    </Button>
                </Form>
            </div>
        </div>

        <div v-else class="flex flex-col items-start justify-start space-y-4">
            <p class="text-muted-foreground text-sm">
                Під час входу ми запитуватимемо одноразовий код із застосунку з
                підтримкою TOTP на вашому телефоні.
            </p>

            <div class="w-full sm:w-auto">
                <Form v-bind="disable.form()" #default="{ processing }">
                    <Button
                        variant="destructive"
                        type="submit"
                        class="h-11 w-full sm:h-9 sm:w-auto"
                        :disabled="processing"
                    >
                        Вимкнути двоетапну перевірку
                    </Button>
                </Form>
            </div>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
