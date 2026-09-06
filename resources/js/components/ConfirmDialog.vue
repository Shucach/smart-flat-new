<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    tone?: 'danger' | 'default';
    processing?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    confirmLabel: 'Підтвердити',
    cancelLabel: 'Скасувати',
    tone: 'danger',
    processing: false,
});

const emit = defineEmits<{
    confirm: [];
}>();

const open = defineModel<boolean>('open', { default: false });

function cancel(): void {
    if (props.processing) {
        return;
    }

    open.value = false;
}

/** Keeps the dialog open while the destructive request is in flight. */
function guardClose(event: Event): void {
    if (props.processing) {
        event.preventDefault();
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="gap-5 rounded-2xl sm:max-w-md"
            :show-close-button="false"
            @escape-key-down="guardClose"
            @pointer-down-outside="guardClose"
        >
            <DialogHeader
                class="items-center gap-3 text-center sm:items-start sm:text-left"
            >
                <span
                    v-if="tone === 'danger'"
                    class="bg-danger-soft text-danger flex size-11 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <TriangleAlert class="size-5" />
                </span>

                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
                <DialogDescription v-else class="sr-only">
                    {{ title }}
                </DialogDescription>
            </DialogHeader>

            <slot />

            <DialogFooter class="gap-2">
                <Button
                    type="button"
                    variant="outline"
                    class="h-11 w-full sm:h-9 sm:w-auto"
                    :disabled="processing"
                    @click="cancel"
                >
                    {{ cancelLabel }}
                </Button>
                <Button
                    type="button"
                    :variant="tone === 'danger' ? 'destructive' : 'default'"
                    class="h-11 w-full sm:h-9 sm:w-auto"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    <Spinner v-if="processing" />
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
