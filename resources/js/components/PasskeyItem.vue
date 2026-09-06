<script setup lang="ts">
import { KeyRound, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Passkey } from '@/types/auth';

const props = defineProps<{
    passkey: Passkey;
}>();

const emit = defineEmits<{
    remove: [id: number, onError: () => void];
}>();

const isDeleting = ref(false);

const handleDelete = () => {
    isDeleting.value = true;
    emit('remove', props.passkey.id, () => {
        isDeleting.value = false;
    });
};
</script>

<template>
    <div
        class="flex min-h-16 items-center justify-between gap-2 border-b p-4 last:border-b-0"
    >
        <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
            <div
                class="bg-muted flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
            >
                <KeyRound class="text-muted-foreground h-5 w-5" />
            </div>
            <div class="min-w-0 space-y-1">
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <p class="truncate font-medium tracking-tight">
                        {{ passkey.name }}
                    </p>
                    <span
                        v-if="passkey.authenticator"
                        class="bg-muted text-muted-foreground ring-border inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium tracking-wide uppercase ring-1 ring-inset"
                    >
                        {{ passkey.authenticator }}
                    </span>
                </div>
                <p class="text-muted-foreground text-xs sm:text-sm">
                    Додано {{ passkey.created_at_diff }}
                    <template v-if="passkey.last_used_at_diff">
                        <span class="text-muted-foreground/50 mx-1">·</span>
                        Востаннє використано {{ passkey.last_used_at_diff }}
                    </template>
                </p>
            </div>
        </div>

        <Dialog>
            <DialogTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon-lg"
                    class="text-destructive hover:bg-destructive/10 hover:text-destructive size-11 shrink-0 sm:size-10"
                >
                    <Trash2 class="h-4 w-4" />
                    <span class="sr-only">
                        Видалити ключ «{{ passkey.name }}»
                    </span>
                </Button>
            </DialogTrigger>

            <DialogContent class="rounded-2xl sm:max-w-md">
                <DialogTitle>Видалити ключ доступу?</DialogTitle>
                <DialogDescription>
                    Ключ «{{ passkey.name }}» буде видалено, і входити з його
                    допомогою більше не вийде.
                </DialogDescription>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button
                            variant="outline"
                            class="h-11 w-full sm:h-9 sm:w-auto"
                        >
                            Скасувати
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        class="h-11 w-full sm:h-9 sm:w-auto"
                        :disabled="isDeleting"
                        @click="handleDelete"
                    >
                        {{ isDeleting ? 'Видаляємо…' : 'Видалити' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
