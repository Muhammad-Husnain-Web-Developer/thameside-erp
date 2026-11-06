<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Avatar from '../../../Components/Avatar.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q ?? '');
let searchTimer = null;

watch(search, (value) => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
        if ((props.filters.q ?? '') === value.trim()) {
            return;
        }

        router.get('/settings/users', { q: value.trim() }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const deleteTarget = ref(null);
const deleting = ref(false);

function confirmDelete() {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/settings/users/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}

function roleLabel(role) {
    return role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Staff';
}
</script>

<template>
    <Head title="Staff accounts" />

    <AppLayout>
        <PageHeader
            eyebrow="Administration"
            title="Staff accounts"
            description="Everyone who can sign in to the Thameside workspace, and the role they hold."
        >
            <template #actions>
                <Button v-if="can.create" href="/settings/users/create">
                    <Icon name="plus" :size="16" />
                    Invite staff
                </Button>
            </template>
        </PageHeader>

        <Card :padded="false">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                <div class="relative w-full max-w-xs">
                    <Icon name="search" :size="15" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search name, email or job title"
                        class="field-input pl-9"
                    />
                </div>
                <p class="text-[12px] text-slate-500">
                    {{ users.total }} {{ users.total === 1 ? 'account' : 'accounts' }}
                </p>
            </div>

            <EmptyState
                v-if="users.data.length === 0"
                icon="users"
                title="No staff accounts found"
                :description="filters.q ? `Nothing matches “${filters.q}”. Try a different search.` : 'Invite your first team member to get started.'"
            >
                <Button v-if="can.create && !filters.q" href="/settings/users/create" size="sm">
                    <Icon name="plus" :size="15" />
                    Invite staff
                </Button>
            </EmptyState>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Staff member</th>
                            <th class="table-heading">Role</th>
                            <th class="table-heading hidden md:table-cell">Job title</th>
                            <th class="table-heading">Status</th>
                            <th class="table-heading hidden lg:table-cell">Last sign-in</th>
                            <th class="table-heading text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="member in users.data" :key="member.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    <Avatar :initials="member.initials" size="md" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-900">{{ member.name }}</p>
                                        <p class="truncate text-[12px] text-slate-500">{{ member.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell">
                                <div class="flex flex-wrap gap-1">
                                    <Badge v-for="role in member.roles" :key="role" tone="brand">{{ roleLabel(role) }}</Badge>
                                    <span v-if="!member.roles.length" class="text-[12px] text-slate-400">—</span>
                                </div>
                            </td>
                            <td class="table-cell hidden md:table-cell">{{ member.job_title ?? '—' }}</td>
                            <td class="table-cell">
                                <Badge :tone="member.is_active ? 'brand' : 'slate'" dot>
                                    {{ member.is_active ? 'Active' : 'Deactivated' }}
                                </Badge>
                            </td>
                            <td class="table-cell hidden text-[12px] text-slate-500 lg:table-cell">{{ member.last_login_human }}</td>
                            <td class="table-cell">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        v-if="member.can.update"
                                        :href="`/settings/users/${member.id}/edit`"
                                        class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                        title="Edit account"
                                    >
                                        <Icon name="pencil" :size="16" />
                                        <span class="sr-only">Edit {{ member.name }}</span>
                                    </Link>
                                    <button
                                        v-if="member.can.delete"
                                        type="button"
                                        class="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                        title="Remove account"
                                        @click="deleteTarget = member"
                                    >
                                        <Icon name="trash" :size="16" />
                                        <span class="sr-only">Remove {{ member.name }}</span>
                                    </button>
                                    <span v-if="!member.can.update && !member.can.delete" class="text-[12px] text-slate-300">—</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="users.data.length > 0" :paginator="users" />
        </Card>

        <ConfirmDialog
            :show="!!deleteTarget"
            title="Remove staff account?"
            :message="deleteTarget ? `${deleteTarget.name} will lose access immediately. Their history is kept in the audit log.` : ''"
            confirm-label="Remove account"
            :processing="deleting"
            @confirm="confirmDelete"
            @close="deleteTarget = null"
        />
    </AppLayout>
</template>
