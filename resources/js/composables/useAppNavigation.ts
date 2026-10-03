import {
    ArrowDownToLine,
    Cpu,
    FolderOpen,
    Images,
    LayoutGrid,
    ShieldUser,
} from '@lucide/vue';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import adminUsers from '@/routes/admin/users';
import frame from '@/routes/frame';
import media from '@/routes/media';
import system from '@/routes/system';
import torrents from '@/routes/torrents';
import type { NavItem } from '@/types';

export type AppNavItem = NavItem & {
    /** Compact caption used by the mobile bottom bar. */
    shortTitle: string;
    /** Path prefix that keeps the item highlighted across a whole section. */
    activeMatch?: string;
    /** Text colour that gives each section its own hue (icons, badges). */
    colorClass: string;
};

export type UseAppNavigationReturn = {
    mainNavItems: ComputedRef<AppNavItem[]>;
};

/**
 * Builds the application navigation shared by the desktop sidebar and the
 * mobile bottom bar, filtered by the permissions of the current user.
 */
export function useAppNavigation(): UseAppNavigationReturn {
    const { can } = usePermissions();

    const mainNavItems = computed<AppNavItem[]>(() => {
        const items: AppNavItem[] = [
            {
                title: 'Дашборд',
                shortTitle: 'Дашборд',
                href: dashboard(),
                icon: LayoutGrid,
                colorClass: 'text-primary',
            },
        ];

        if (can('media.view')) {
            items.push({
                title: 'Медіа',
                shortTitle: 'Медіа',
                href: media.index(),
                icon: FolderOpen,
                colorClass: 'text-chart-1',
            });
        }

        if (can('torrent.view')) {
            items.push({
                title: 'Торенти',
                shortTitle: 'Торенти',
                href: torrents.index(),
                icon: ArrowDownToLine,
                colorClass: 'text-chart-2',
            });
        }

        if (can('frame.view')) {
            items.push({
                title: 'Розумна рамка',
                shortTitle: 'Рамка',
                href: frame.index(),
                icon: Images,
                colorClass: 'text-chart-3',
            });
        }

        if (can('system.view')) {
            items.push({
                title: 'Система',
                shortTitle: 'Система',
                href: system.index(),
                icon: Cpu,
                colorClass: 'text-chart-4',
            });
        }

        if (can('users.manage')) {
            items.push({
                title: 'Адміністрування',
                shortTitle: 'Адмін',
                href: adminUsers.index(),
                icon: ShieldUser,
                colorClass: 'text-chart-5',
                activeMatch: '/admin',
            });
        }

        return items;
    });

    return { mainNavItems };
}
