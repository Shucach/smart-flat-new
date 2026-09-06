<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, ShieldCheck, Trash } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { pluralizeUk } from '@/lib/format';
import adminRoles from '@/routes/admin/roles';
import type { AdminRole, PermissionGroup } from '@/types';

defineProps<{
    roles: AdminRole[];
    permissionGroups: PermissionGroup[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ролі та доступи',
                href: adminRoles.index(),
            },
        ],
    },
});

const SUPER_ROLE = 'admin';

const editedRole = ref<AdminRole | null>(null);
const isDialogOpen = ref(false);

const form = useForm<{
    name: string;
    label: string;
    permissions: string[];
}>({
    name: '',
    label: '',
    permissions: [],
});

const isSuperRole = computed(() => editedRole.value?.name === SUPER_ROLE);

function openCreateDialog(): void {
    editedRole.value = null;
    form.defaults({ name: '', label: '', permissions: [] });
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
}

function openEditDialog(role: AdminRole): void {
    editedRole.value = role;
    form.defaults({
        name: role.name,
        label: role.label,
        permissions: [...role.permissions],
    });
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
}

function togglePermission(permission: string, checked: boolean): void {
    form.permissions = checked
        ? [...form.permissions, permission]
        : form.permissions.filter((value) => value !== permission);
}

function submit(): void {
    const role = editedRole.value;

    if (role) {
        form.put(adminRoles.update.url(role.id), {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });

        return;
    }

    form.post(adminRoles.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            isDialogOpen.value = false;
        },
    });
}

const rolePendingDeletion = ref<AdminRole | null>(null);
const isDeleting = ref(false);

const isConfirmOpen = computed({
    get: () => rolePendingDeletion.value !== null,
    set: (value: boolean) => {
        if (!value) {
            rolePendingDeletion.value = null;
        }
    },
});

function confirmDeletion(): void {
    const role = rolePendingDeletion.value;

    if (!role) {
        return;
    }

    isDeleting.value = true;

    router.delete(adminRoles.destroy(role.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            rolePendingDeletion.value = null;
        },
    });
}
</script>

<template>
    <Head title="Ролі та доступи" />

    <div class="flex flex-col gap-4 p-4 md:gap-6 md:p-6">
        <PageHeader
            title="Ролі та доступи"
            description="Набори прав, які надаються користувачам"
        >
            <template #default>
                <Button size="sm" @click="openCreateDialog">
                    <Plus class="size-4" />
                    Нова роль
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="roles.length === 0"
            title="Ролей ще немає"
            description="Створіть роль і оберіть, що вона дозволяє."
            :icon="ShieldCheck"
        >
            <Button @click="openCreateDialog">Нова роль</Button>
        </EmptyState>

        <div v-else class="flex flex-col gap-3">
            <div
                v-for="role in roles"
                :key="role.id"
                class="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">{{ role.label }}</p>
                        <Badge v-if="role.name === SUPER_ROLE">
                            Повний доступ
                        </Badge>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        {{ role.name }} ·
                        {{
                            pluralizeUk(
                                role.usersCount,
                                'користувач',
                                'користувачі',
                                'користувачів',
                            )
                        }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="openEditDialog(role)"
                    >
                        <Pencil class="size-4" />
                        {{
                            role.name === SUPER_ROLE ? 'Переглянути' : 'Змінити'
                        }}
                    </Button>
                    <Button
                        v-if="role.name !== SUPER_ROLE"
                        variant="ghost"
                        size="icon"
                        :aria-label="`Видалити роль ${role.label}`"
                        @click="rolePendingDeletion = role"
                    >
                        <Trash class="text-destructive size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <Dialog v-model:open="isDialogOpen">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ editedRole ? editedRole.label : 'Нова роль' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        isSuperRole
                            ? 'Системна роль із повним доступом — її права незмінні.'
                            : 'Назва використовується в коді, підпис бачать користувачі.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="role-name">Назва</Label>
                    <Input
                        id="role-name"
                        v-model="form.name"
                        :disabled="editedRole !== null"
                        placeholder="напр. operator"
                        required
                    />
                    <p v-if="editedRole" class="text-muted-foreground text-xs">
                        Системну назву ролі змінити не можна.
                    </p>
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="role-label">Підпис</Label>
                    <Input id="role-label" v-model="form.label" required />
                    <InputError :message="form.errors.label" />
                </div>

                <div v-if="!isSuperRole" class="flex flex-col gap-4">
                    <div
                        v-for="group in permissionGroups"
                        :key="group.group"
                        class="flex flex-col gap-1"
                    >
                        <p
                            class="text-muted-foreground text-xs font-medium uppercase"
                        >
                            {{ group.group }}
                        </p>
                        <label
                            v-for="item in group.items"
                            :key="item.value"
                            class="hover:bg-accent/40 flex min-h-11 cursor-pointer items-center gap-3 rounded-lg px-2"
                        >
                            <Checkbox
                                :model-value="
                                    form.permissions.includes(item.value)
                                "
                                @update:model-value="
                                    (checked) =>
                                        togglePermission(
                                            item.value,
                                            checked === true,
                                        )
                                "
                            />
                            <span class="flex-1 text-sm">{{ item.label }}</span>
                        </label>
                    </div>
                    <InputError :message="form.errors.permissions" />
                </div>

                <p
                    v-else
                    class="text-muted-foreground rounded-lg border border-dashed p-3 text-sm"
                >
                    Роль «{{ editedRole?.label }}» має всі права застосунку.
                </p>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        :disabled="form.processing"
                        @click="isDialogOpen = false"
                    >
                        Закрити
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Збереження…' : 'Зберегти' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="isConfirmOpen"
        tone="danger"
        title="Видалити роль?"
        :description="
            rolePendingDeletion
                ? `Роль «${rolePendingDeletion.label}» буде видалено, а користувачі втратять надані нею права.`
                : ''
        "
        confirm-label="Видалити"
        cancel-label="Скасувати"
        :processing="isDeleting"
        @confirm="confirmDeletion"
    />
</template>
