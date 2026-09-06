<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Clock, Cpu, HardDrive, MemoryStick } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import StatTile from '@/components/StatTile.vue';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { usePermissions } from '@/composables/usePermissions';
import {
    formatBytesRatio,
    formatDuration,
    formatPercent,
    pluralizeUk,
} from '@/lib/format';
import { dashboard } from '@/routes';
import { index as systemIndex } from '@/routes/system';
import type { ChartTone, DiskUsage, SystemSnapshot } from '@/types';

type DashboardCounters = {
    media: { directories: number; files: number } | null;
    frame: { total: number } | null;
    users: { total: number; roles: number } | null;
};

const props = withDefaults(
    defineProps<{
        snapshot?: SystemSnapshot | null;
        counters?: DashboardCounters | null;
    }>(),
    {
        snapshot: null,
        counters: null,
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Дашборд',
                href: dashboard(),
            },
        ],
    },
});

const { can } = usePermissions();
const { mainNavItems } = useAppNavigation();

const quickLinks = computed(() =>
    mainNavItems.value.filter((item) => item.shortTitle !== 'Дашборд'),
);

/** The disk closest to being full is the one worth showing first. */
const busiestDisk = computed<DiskUsage | null>(() => {
    const disks = props.snapshot?.disks ?? [];

    if (disks.length === 0) {
        return null;
    }

    return [...disks].sort((a, b) => b.usagePercent - a.usagePercent)[0];
});

/** Short line under each section link, built from the counters prop. */
function sectionSummary(shortTitle: string): string | null {
    const counters = props.counters;

    if (!counters) {
        return null;
    }

    if (shortTitle === 'Медіа' && counters.media) {
        return `${pluralizeUk(counters.media.directories, 'тека', 'теки', 'тек')} · ${pluralizeUk(counters.media.files, 'файл', 'файли', 'файлів')}`;
    }

    if (shortTitle === 'Рамка' && counters.frame) {
        return pluralizeUk(
            counters.frame.total,
            'зображення',
            'зображення',
            'зображень',
        );
    }

    if (shortTitle === 'Адмін' && counters.users) {
        return `${pluralizeUk(counters.users.total, 'користувач', 'користувачі', 'користувачів')} · ${pluralizeUk(counters.users.roles, 'роль', 'ролі', 'ролей')}`;
    }

    return null;
}

function usageTone(percent: number): ChartTone {
    if (percent >= 90) {
        return 'danger';
    }

    if (percent >= 75) {
        return 'warn';
    }

    return 'ok';
}
</script>

<template>
    <Head title="Дашборд" />

    <div class="flex flex-col gap-4 p-4 md:gap-6 md:p-6">
        <PageHeader
            title="SmartFlat"
            description="Коротке зведення по квартирі"
        />

        <div
            v-if="snapshot && can('system.view')"
            class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4"
        >
            <StatTile
                label="Процесор"
                :value="formatPercent(snapshot.cpu.usagePercent)"
                :icon="Cpu"
                :tone="usageTone(snapshot.cpu.usagePercent)"
                :href="systemIndex()"
            />
            <StatTile
                label="Памʼять"
                :value="formatPercent(snapshot.memory.usagePercent)"
                :icon="MemoryStick"
                :tone="usageTone(snapshot.memory.usagePercent)"
                :href="systemIndex()"
            >
                <p class="text-muted-foreground text-xs">
                    {{
                        formatBytesRatio(
                            snapshot.memory.usedBytes,
                            snapshot.memory.totalBytes,
                        )
                    }}
                </p>
            </StatTile>
            <StatTile
                v-if="busiestDisk"
                :label="busiestDisk.label || busiestDisk.mountPoint"
                :value="formatPercent(busiestDisk.usagePercent)"
                :icon="HardDrive"
                :tone="usageTone(busiestDisk.usagePercent)"
                :href="systemIndex()"
            >
                <p class="text-muted-foreground text-xs">
                    {{
                        formatBytesRatio(
                            busiestDisk.usedBytes,
                            busiestDisk.totalBytes,
                        )
                    }}
                </p>
            </StatTile>
            <StatTile
                label="Аптайм"
                :value="formatDuration(snapshot.host.uptimeSeconds)"
                :icon="Clock"
                tone="neutral"
            >
                <p class="text-muted-foreground truncate text-xs">
                    {{ snapshot.host.name }}
                </p>
            </StatTile>
        </div>

        <SectionCard title="Розділи">
            <div class="grid gap-2 sm:grid-cols-2">
                <Link
                    v-for="item in quickLinks"
                    :key="item.title"
                    :href="item.href"
                    class="hover:bg-accent/50 border-sidebar-border/70 dark:border-sidebar-border flex min-h-14 items-center gap-3 rounded-xl border px-4 py-3 transition-colors"
                >
                    <component
                        :is="item.icon"
                        class="text-primary size-5 shrink-0"
                    />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">
                            {{ item.title }}
                        </span>
                        <span
                            v-if="sectionSummary(item.shortTitle)"
                            class="text-muted-foreground block truncate text-xs"
                        >
                            {{ sectionSummary(item.shortTitle) }}
                        </span>
                    </span>
                    <ChevronRight class="text-muted-foreground size-4" />
                </Link>
            </div>
        </SectionCard>
    </div>
</template>
