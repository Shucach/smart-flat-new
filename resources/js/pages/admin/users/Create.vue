<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import adminUsers from '@/routes/admin/users';
import type { AdminUserRole } from '@/types';

defineProps<{
    roles: AdminUserRole[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Користувачі',
                href: adminUsers.index(),
            },
            {
                title: 'Новий користувач',
                href: adminUsers.create(),
            },
        ],
    },
});

const form = useForm<{
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    roles: number[];
}>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [],
});

function toggleRole(roleId: number, checked: boolean): void {
    form.roles = checked
        ? [...form.roles, roleId]
        : form.roles.filter((id) => id !== roleId);
}

function submit(): void {
    form.post(adminUsers.store.url(), {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Новий користувач" />

    <div class="flex flex-col gap-4 p-4 md:gap-6 md:p-6">
        <PageHeader
            title="Новий користувач"
            description="Створіть обліковий запис і надайте йому ролі"
        >
            <template #default>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="adminUsers.index()">
                        <ArrowLeft class="size-4" />
                        До списку
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <form class="flex flex-col gap-4 md:gap-6" @submit.prevent="submit">
            <SectionCard title="Обліковий запис">
                <div class="flex flex-col gap-4">
                    <div class="grid gap-2">
                        <Label for="name">Імʼя</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            autocomplete="name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="username"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Пароль</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">
                            Підтвердження пароля
                        </Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError
                            :message="form.errors.password_confirmation"
                        />
                    </div>
                </div>
            </SectionCard>

            <SectionCard
                title="Ролі"
                description="Права визначаються набором ролей"
            >
                <div class="flex flex-col gap-1">
                    <label
                        v-for="role in roles"
                        :key="role.id"
                        class="hover:bg-accent/40 flex min-h-12 cursor-pointer items-center gap-3 rounded-lg px-2"
                    >
                        <Checkbox
                            :model-value="form.roles.includes(role.id)"
                            @update:model-value="
                                (checked) =>
                                    toggleRole(role.id, checked === true)
                            "
                        />
                        <span class="flex-1 text-sm">{{ role.label }}</span>
                    </label>
                    <InputError :message="form.errors.roles" />
                </div>
            </SectionCard>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Збереження…' : 'Створити' }}
                </Button>
                <Button as-child variant="ghost" :disabled="form.processing">
                    <Link :href="adminUsers.index()">Скасувати</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
