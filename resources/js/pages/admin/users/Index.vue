<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import adminUsers from '@/routes/admin/users';
import type { AdminUser, Paginated } from '@/types';

defineProps<{
    users: Paginated<AdminUser>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Користувачі',
                href: adminUsers.index(),
            },
        ],
    },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const userPendingDeletion = ref<AdminUser | null>(null);
const isDeleting = ref(false);

const isConfirmOpen = computed({
    get: () => userPendingDeletion.value !== null,
    set: (value: boolean) => {
        if (!value) {
            userPendingDeletion.value = null;
        }
    },
});

function confirmDeletion(): void {
    const user = userPendingDeletion.value;

    if (!user) {
        return;
    }

    isDeleting.value = true;

    router.delete(adminUsers.destroy(user.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            userPendingDeletion.value = null;
        },
    });
}
</script>

<template>
    <Head title="Користувачі" />

    <div class="flex flex-col gap-4 p-4 md:gap-6 md:p-6">
        <PageHeader title="Користувачі" description="Доступ до панелі та ролі">
            <template #default>
                <Button as-child size="sm">
                    <Link :href="adminUsers.create()">
                        <Plus class="size-4" />
                        Додати
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="users.data.length === 0"
            title="Користувачів немає"
            description="Створіть першого користувача, щоб надати доступ до панелі."
            :icon="Users"
        >
            <Button as-child>
                <Link :href="adminUsers.create()">Додати користувача</Link>
            </Button>
        </EmptyState>

        <template v-else>
            <div class="flex flex-col gap-3 md:hidden">
                <div
                    v-for="user in users.data"
                    :key="user.id"
                    class="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-3 rounded-xl border p-4"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ user.name }}</p>
                        <p class="text-muted-foreground truncate text-sm">
                            {{ user.email }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-1">
                        <Badge
                            v-for="role in user.roles"
                            :key="role.id"
                            variant="secondary"
                        >
                            {{ role.label }}
                        </Badge>
                        <span
                            v-if="user.roles.length === 0"
                            class="text-muted-foreground text-xs"
                        >
                            Без ролей
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground flex-1 text-xs">
                            {{ formatDate(user.createdAt) }}
                        </span>
                        <Button as-child variant="outline" size="sm">
                            <Link :href="adminUsers.edit(user.id)">
                                <Pencil class="size-4" />
                                Змінити
                            </Link>
                        </Button>
                        <Button
                            v-if="user.id !== currentUserId"
                            variant="ghost"
                            size="icon"
                            :aria-label="`Видалити ${user.name}`"
                            @click="userPendingDeletion = user"
                        >
                            <Trash class="text-destructive size-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <div
                class="border-sidebar-border/70 dark:border-sidebar-border hidden overflow-hidden rounded-xl border md:block"
            >
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Користувач</th>
                            <th class="px-4 py-3 font-medium">Ролі</th>
                            <th class="px-4 py-3 font-medium">Створено</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-sidebar-border/60 divide-y">
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ user.name }}</p>
                                <p class="text-muted-foreground">
                                    {{ user.email }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="role in user.roles"
                                        :key="role.id"
                                        variant="secondary"
                                    >
                                        {{ role.label }}
                                    </Badge>
                                    <span
                                        v-if="user.roles.length === 0"
                                        class="text-muted-foreground text-xs"
                                    >
                                        —
                                    </span>
                                </div>
                            </td>
                            <td class="text-muted-foreground px-4 py-3">
                                {{ formatDate(user.createdAt) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        as-child
                                        variant="ghost"
                                        size="icon"
                                    >
                                        <Link :href="adminUsers.edit(user.id)">
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="user.id !== currentUserId"
                                        variant="ghost"
                                        size="icon"
                                        :aria-label="`Видалити ${user.name}`"
                                        @click="userPendingDeletion = user"
                                    >
                                        <Trash
                                            class="text-destructive size-4"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="users.last_page > 1"
                class="flex items-center justify-between gap-2"
            >
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    :disabled="!users.prev_page_url"
                >
                    <Link
                        :href="users.prev_page_url ?? adminUsers.index().url"
                        preserve-scroll
                    >
                        Назад
                    </Link>
                </Button>
                <span class="text-muted-foreground text-sm">
                    Сторінка {{ users.current_page }} з {{ users.last_page }}
                </span>
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    :disabled="!users.next_page_url"
                >
                    <Link
                        :href="users.next_page_url ?? adminUsers.index().url"
                        preserve-scroll
                    >
                        Далі
                    </Link>
                </Button>
            </div>
        </template>
    </div>

    <ConfirmDialog
        v-model:open="isConfirmOpen"
        tone="danger"
        title="Видалити користувача?"
        :description="
            userPendingDeletion
                ? `«${userPendingDeletion.name}» втратить доступ до панелі.`
                : ''
        "
        confirm-label="Видалити"
        cancel-label="Скасувати"
        :processing="isDeleting"
        @confirm="confirmDeletion"
    />
</template>
