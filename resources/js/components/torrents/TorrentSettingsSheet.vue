<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Gauge, ListOrdered, Network, Rabbit, Turtle } from '@lucide/vue';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import torrents from '@/routes/torrents';
import torrentSettings from '@/routes/torrents/settings';
import type { TorrentSessionSettings } from '@/types';

const props = defineProps<{
    settings: TorrentSessionSettings;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    startAddedTorrents: props.settings.startAddedTorrents,
    speedLimitDown: props.settings.speedLimitDown,
    speedLimitDownEnabled: props.settings.speedLimitDownEnabled,
    speedLimitUp: props.settings.speedLimitUp,
    speedLimitUpEnabled: props.settings.speedLimitUpEnabled,
    altSpeedDown: props.settings.altSpeedDown,
    altSpeedUp: props.settings.altSpeedUp,
    altSpeedEnabled: props.settings.altSpeedEnabled,
    downloadQueueSize: props.settings.downloadQueueSize,
    downloadQueueEnabled: props.settings.downloadQueueEnabled,
    seedQueueSize: props.settings.seedQueueSize,
    seedQueueEnabled: props.settings.seedQueueEnabled,
    seedRatioLimit: props.settings.seedRatioLimit,
    seedRatioLimited: props.settings.seedRatioLimited,
    peerLimitGlobal: props.settings.peerLimitGlobal,
});

/**
 * The list behind the sheet keeps polling, so the settings it carries can
 * change under an open form. Only a closed form is refilled from them.
 */
watch(
    () => props.settings,
    (settings) => {
        if (open.value) {
            return;
        }

        form.startAddedTorrents = settings.startAddedTorrents;
        form.speedLimitDown = settings.speedLimitDown;
        form.speedLimitDownEnabled = settings.speedLimitDownEnabled;
        form.speedLimitUp = settings.speedLimitUp;
        form.speedLimitUpEnabled = settings.speedLimitUpEnabled;
        form.altSpeedDown = settings.altSpeedDown;
        form.altSpeedUp = settings.altSpeedUp;
        form.altSpeedEnabled = settings.altSpeedEnabled;
        form.downloadQueueSize = settings.downloadQueueSize;
        form.downloadQueueEnabled = settings.downloadQueueEnabled;
        form.seedQueueSize = settings.seedQueueSize;
        form.seedQueueEnabled = settings.seedQueueEnabled;
        form.seedRatioLimit = settings.seedRatioLimit;
        form.seedRatioLimited = settings.seedRatioLimited;
        form.peerLimitGlobal = settings.peerLimitGlobal;
    },
);

function submit(): void {
    form.put(torrentSettings.update.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}

function testPort(): void {
    router.post(torrents.portTest.url(), {}, { preserveScroll: true });
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            side="right"
            class="w-full gap-0 overflow-y-auto sm:max-w-md"
        >
            <SheetHeader class="pr-10">
                <SheetTitle>Налаштування</SheetTitle>
                <SheetDescription>
                    Діють на весь торент-клієнт.
                </SheetDescription>
            </SheetHeader>

            <form
                id="torrent-settings"
                class="flex flex-col gap-6 px-4 pb-4"
                @submit.prevent="submit"
            >
                <section class="space-y-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <Gauge class="size-4" />
                        Обмеження швидкості
                    </h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="gap-2 text-xs">
                                <Checkbox
                                    :model-value="form.speedLimitDownEnabled"
                                    @update:model-value="
                                        form.speedLimitDownEnabled =
                                            $event === true
                                    "
                                />
                                Завантаження, КБ/с
                            </Label>
                            <Input
                                v-model="form.speedLimitDown"
                                type="number"
                                min="0"
                                class="h-11 sm:h-9"
                                :disabled="!form.speedLimitDownEnabled"
                                aria-label="Обмеження завантаження"
                            />
                            <InputError :message="form.errors.speedLimitDown" />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="gap-2 text-xs">
                                <Checkbox
                                    :model-value="form.speedLimitUpEnabled"
                                    @update:model-value="
                                        form.speedLimitUpEnabled =
                                            $event === true
                                    "
                                />
                                Роздача, КБ/с
                            </Label>
                            <Input
                                v-model="form.speedLimitUp"
                                type="number"
                                min="0"
                                class="h-11 sm:h-9"
                                :disabled="!form.speedLimitUpEnabled"
                                aria-label="Обмеження роздачі"
                            />
                            <InputError :message="form.errors.speedLimitUp" />
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <Turtle class="size-4" />
                        Повільний режим
                    </h3>

                    <Label class="gap-2">
                        <Checkbox
                            :model-value="form.altSpeedEnabled"
                            @update:model-value="
                                form.altSpeedEnabled = $event === true
                            "
                        />
                        Увімкнути замість звичайних обмежень
                    </Label>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-xs" for="alt-down">
                                Завантаження, КБ/с
                            </Label>
                            <Input
                                id="alt-down"
                                v-model="form.altSpeedDown"
                                type="number"
                                min="0"
                                class="h-11 sm:h-9"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs" for="alt-up">
                                Роздача, КБ/с
                            </Label>
                            <Input
                                id="alt-up"
                                v-model="form.altSpeedUp"
                                type="number"
                                min="0"
                                class="h-11 sm:h-9"
                            />
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <ListOrdered class="size-4" />
                        Черги
                    </h3>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="gap-2 text-xs">
                                <Checkbox
                                    :model-value="form.downloadQueueEnabled"
                                    @update:model-value="
                                        form.downloadQueueEnabled =
                                            $event === true
                                    "
                                />
                                Завантажень одночасно
                            </Label>
                            <Input
                                v-model="form.downloadQueueSize"
                                type="number"
                                min="1"
                                class="h-11 sm:h-9"
                                :disabled="!form.downloadQueueEnabled"
                                aria-label="Розмір черги завантаження"
                            />
                            <InputError
                                :message="form.errors.downloadQueueSize"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="gap-2 text-xs">
                                <Checkbox
                                    :model-value="form.seedQueueEnabled"
                                    @update:model-value="
                                        form.seedQueueEnabled = $event === true
                                    "
                                />
                                Роздач одночасно
                            </Label>
                            <Input
                                v-model="form.seedQueueSize"
                                type="number"
                                min="1"
                                class="h-11 sm:h-9"
                                :disabled="!form.seedQueueEnabled"
                                aria-label="Розмір черги роздачі"
                            />
                            <InputError :message="form.errors.seedQueueSize" />
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <Rabbit class="size-4" />
                        Роздача
                    </h3>

                    <div class="space-y-1.5">
                        <Label class="gap-2 text-xs">
                            <Checkbox
                                :model-value="form.seedRatioLimited"
                                @update:model-value="
                                    form.seedRatioLimited = $event === true
                                "
                            />
                            Зупиняти при рейтингу
                        </Label>
                        <Input
                            v-model="form.seedRatioLimit"
                            type="number"
                            min="0"
                            step="0.1"
                            class="h-11 w-32 sm:h-9"
                            :disabled="!form.seedRatioLimited"
                            aria-label="Граничний рейтинг"
                        />
                        <InputError :message="form.errors.seedRatioLimit" />
                    </div>

                    <Label class="gap-2">
                        <Checkbox
                            :model-value="form.startAddedTorrents"
                            @update:model-value="
                                form.startAddedTorrents = $event === true
                            "
                        />
                        Запускати додані торенти одразу
                    </Label>
                </section>

                <section class="space-y-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <Network class="size-4" />
                        Мережа
                    </h3>

                    <div class="space-y-1.5">
                        <Label class="text-xs" for="peer-limit">
                            Максимум підключень
                        </Label>
                        <Input
                            id="peer-limit"
                            v-model="form.peerLimitGlobal"
                            type="number"
                            min="1"
                            class="h-11 w-32 sm:h-9"
                        />
                        <InputError :message="form.errors.peerLimitGlobal" />
                    </div>

                    <div
                        class="text-muted-foreground flex items-center justify-between gap-3 text-xs"
                    >
                        <span>Вхідний порт {{ settings.peerPort }}</span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="testPort"
                        >
                            Перевірити
                        </Button>
                    </div>

                    <p class="text-muted-foreground text-xs break-all">
                        Тека завантаження: {{ settings.downloadDir }}
                    </p>
                </section>
            </form>

            <SheetFooter>
                <Button
                    type="submit"
                    form="torrent-settings"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    Зберегти
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
