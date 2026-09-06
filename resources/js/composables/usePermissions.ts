import { usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';

export type UsePermissionsReturn = {
    permissions: ComputedRef<string[]>;
    can: (permission: string) => boolean;
    canAny: (...permissions: string[]) => boolean;
    canAll: (...permissions: string[]) => boolean;
};

type AuthPropShape = {
    user?: { permissions?: unknown } | null;
};

function toPermissionList(value: unknown): string[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.filter((item): item is string => typeof item === 'string');
}

/**
 * Reads the permission list shared by `HandleInertiaRequests` and exposes
 * reactive helpers for gating navigation items, actions and whole sections.
 */
export function usePermissions(): UsePermissionsReturn {
    const page = usePage();

    const permissions = computed<string[]>(() => {
        const auth = page.props.auth as AuthPropShape | undefined;

        return toPermissionList(auth?.user?.permissions);
    });

    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    function canAny(...wanted: string[]): boolean {
        return wanted.some((permission) => can(permission));
    }

    function canAll(...wanted: string[]): boolean {
        return wanted.every((permission) => can(permission));
    }

    return { permissions, can, canAny, canAll };
}
