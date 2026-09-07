<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, ShieldUser } from '@lucide/vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { usePermissions } from '@/composables/usePermissions';
import { logout } from '@/routes';
import adminUsers from '@/routes/admin/users';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();

/**
 * The bottom bar shows five destinations at most, which is one short of the
 * full section list, so administration is reachable from here on a phone.
 */
const { can } = usePermissions();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem v-if="can('users.manage')" :as-child="true">
            <Link
                class="flex min-h-11 w-full cursor-pointer items-center sm:min-h-9"
                :href="adminUsers.index()"
            >
                <ShieldUser class="mr-2 h-4 w-4" />
                Адміністрування
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true">
            <Link
                class="flex min-h-11 w-full cursor-pointer items-center sm:min-h-9"
                :href="edit()"
                prefetch
            >
                <Settings class="mr-2 h-4 w-4" />
                Налаштування
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="flex min-h-11 w-full cursor-pointer items-center sm:min-h-9"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Вийти
        </Link>
    </DropdownMenuItem>
</template>
