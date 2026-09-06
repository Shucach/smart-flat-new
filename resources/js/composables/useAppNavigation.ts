import { FolderOpen, Images, LayoutGrid, ShieldUser, Cpu } from '@lucide/vue';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import adminUsers from '@/routes/admin/users';
import frame from '@/routes/frame';
import media from '@/routes/media';
import system from '@/routes/system';
import type { NavItem } from '@/types';

export type AppNavItem = NavItem & {
    /** Compact caption used by the mobile bottom bar. */
    shortTitle: string;
    /** Path prefix that keeps the item highlighted across a whole section. */
    activeMatch?: string;
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
            },
        ];

        if (can('media.view')) {
            items.push({
                title: 'Медіа',
                shortTitle: 'Медіа',
                href: media.index(),
                icon: FolderOpen,
            });
        }

        if (can('frame.view')) {
            items.push({
                title: 'Розумна рамка',
                shortTitle: 'Рамка',
                href: frame.index(),
                icon: Images,
            });
        }

        if (can('system.view')) {
            items.push({
                title: 'Система',
                shortTitle: 'Система',
                href: system.index(),
                icon: Cpu,
            });
        }

        if (can('users.manage')) {
            items.push({
                title: 'Адміністрування',
                shortTitle: 'Адмін',
                href: adminUsers.index(),
                icon: ShieldUser,
                activeMatch: '/admin',
            });
        }

        return items;
    });

    return { mainNavItems };
}
