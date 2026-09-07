<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ChevronDown, Magnet, Paperclip, Plus, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import torrents from '@/routes/torrents';

const props = defineProps<{
    /** Where the daemon puts downloads when nothing else is asked for. */
    defaultDownloadDir: string;
    maxFileMegabytes: number;
}>();

type AddForm = {
    url: string;
    file: File | null;
    downloadDir: string;
    paused: boolean;
};

const form = useForm<AddForm>({
    url: '',
    file: null,
    downloadDir: '',
    paused: false,
});

const fileInput = ref<HTMLInputElement | null>(null);
const showOptions = ref(false);

function onFilePicked(event: Event): void {
    const input = event.target as HTMLInputElement;

    form.file = input.files?.[0] ?? null;
}

function clearFile(): void {
    form.file = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit(): void {
    form.post(torrents.store.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            clearFile();
            showOptions.value = false;
        },
    });
}
</script>

<template>
    <SectionCard title="Додати торент">
        <form class="flex flex-col gap-3" @submit.prevent="submit">
            <div class="flex flex-col gap-2 sm:flex-row">
                <div class="relative min-w-0 flex-1">
                    <Magnet
                        class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="form.url"
                        type="text"
                        class="h-11 pl-9 sm:h-9"
                        placeholder="magnet:?xt=… або https://…/file.torrent"
                        autocomplete="off"
                        spellcheck="false"
                        :aria-invalid="Boolean(form.errors.url)"
                    />
                </div>

                <Button
                    type="submit"
                    class="h-11 sm:h-9"
                    :disabled="
                        form.processing || (!form.url.trim() && !form.file)
                    "
                >
                    <Spinner v-if="form.processing" />
                    <Plus v-else class="size-4" />
                    Додати
                </Button>
            </div>

            <InputError :message="form.errors.url" />
            <InputError :message="form.errors.file" />

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="fileInput?.click()"
                >
                    <Paperclip class="size-4" />
                    .torrent файл
                </Button>

                <span
                    v-if="form.file"
                    class="bg-muted flex min-w-0 items-center gap-1 rounded-full py-1 pr-1 pl-3 text-xs"
                >
                    <span class="truncate">{{ form.file.name }}</span>
                    <button
                        type="button"
                        class="hover:text-destructive p-1"
                        aria-label="Прибрати файл"
                        @click="clearFile"
                    >
                        <X class="size-3" />
                    </button>
                </span>

                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="ml-auto"
                    @click="showOptions = !showOptions"
                >
                    Параметри
                    <ChevronDown
                        class="size-4 transition-transform"
                        :class="showOptions ? 'rotate-180' : ''"
                    />
                </Button>
            </div>

            <input
                ref="fileInput"
                type="file"
                accept=".torrent,application/x-bittorrent"
                class="hidden"
                @change="onFilePicked"
            />

            <div v-if="showOptions" class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <Label for="download-dir">Тека завантаження</Label>
                    <Input
                        id="download-dir"
                        v-model="form.downloadDir"
                        type="text"
                        class="h-11 sm:h-9"
                        :placeholder="props.defaultDownloadDir"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.downloadDir" />
                </div>

                <div class="flex items-end">
                    <Label class="gap-2">
                        <Checkbox
                            :model-value="form.paused"
                            @update:model-value="form.paused = $event === true"
                        />
                        Додати зупиненим
                    </Label>
                </div>
            </div>

            <p class="text-muted-foreground text-xs">
                Файл .torrent — до {{ props.maxFileMegabytes }} МБ.
            </p>
        </form>
    </SectionCard>
</template>
