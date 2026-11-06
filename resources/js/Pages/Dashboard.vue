<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import Badge from '../Components/Badge.vue';
import Button from '../Components/Button.vue';
import Card from '../Components/Card.vue';
import EmptyState from '../Components/EmptyState.vue';
import Icon from '../Components/Icon.vue';
import PageHeader from '../Components/PageHeader.vue';
import StatCard from '../Components/StatCard.vue';

const props = defineProps({
    stats: { type: Array, default: () => [] },
    recentActivity: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const company = computed(() => page.props.company ?? {});

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
});

const firstName = computed(() => (user.value?.name ?? '').split(' ')[0]);

const eventTone = (event) =>
    ({ created: 'brand', updated: 'amber', deleted: 'rose' })[event] ?? 'slate';

function roleLabel(role) {
    return role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Staff';
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <PageHeader
            :title="`${greeting}, ${firstName}`"
            description="Here is how the Thameside workspace looks today."
            eyebrow="Operations overview"
        >
            <template #actions>
                <Badge v-for="role in user?.roles ?? []" :key="role" tone="brand" dot>{{ roleLabel(role) }}</Badge>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="stat in stats"
                :key="stat.key"
                :label="stat.label"
                :value="stat.value"
                :meta="stat.meta"
                :icon="stat.icon"
                :tone="stat.key === 'activity' ? 'violet' : stat.key === 'roles' ? 'amber' : stat.key === 'permissions' ? 'slate' : 'brand'"
            />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Card title="Recent activity" description="Everything recorded by the audit trail." :padded="false">
                    <template #actions>
                        <Link
                            v-if="user?.permissions?.includes('activity.view')"
                            href="/settings/activity"
                            class="inline-flex items-center gap-1 text-[12px] font-medium text-brand-700 transition hover:text-brand-800"
                        >
                            View all
                            <Icon name="arrow-right" :size="13" />
                        </Link>
                    </template>

                    <EmptyState
                        v-if="recentActivity.length === 0"
                        icon="history"
                        title="No activity yet"
                        description="Changes to staff accounts and business documents will appear here."
                    />

                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="activity in recentActivity" :key="activity.id" class="flex gap-3 px-5 py-3.5">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 ring-1 ring-inset ring-slate-200">
                                <Icon
                                    :name="activity.event === 'signed' ? 'lock' : activity.subject === 'User' ? 'users' : 'history'"
                                    :size="15"
                                />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] text-slate-700">
                                    <span class="font-medium text-slate-900">{{ activity.causer ?? 'System' }}</span>
                                    <span class="text-slate-500">
                                        {{ activity.log === 'auth' ? 'signed in' : `${activity.description} ${activity.subject?.toLowerCase() ?? 'record'}` }}
                                    </span>
                                </p>
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    {{ activity.created_human }}
                                    <span v-if="activity.subject_id"> · {{ activity.subject }} #{{ activity.subject_id }}</span>
                                </p>
                            </div>
                            <Badge :tone="eventTone(activity.event)" class="self-start">{{ activity.event ?? activity.log }}</Badge>
                        </li>
                    </ul>
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Your access">
                    <div class="space-y-4">
                        <div>
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Roles</p>
                            <div class="flex flex-wrap gap-1.5">
                                <Badge v-for="role in user?.roles ?? []" :key="role" tone="brand">{{ roleLabel(role) }}</Badge>
                                <span v-if="!user?.roles?.length" class="text-[12px] text-slate-400">No role assigned</span>
                            </div>
                        </div>
                        <div>
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Permissions</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="permission in user?.permissions ?? []"
                                    :key="permission"
                                    class="rounded-md bg-slate-50 px-1.5 py-0.5 font-mono text-[10.5px] text-slate-600 ring-1 ring-inset ring-slate-200"
                                >
                                    {{ permission }}
                                </span>
                            </div>
                        </div>
                        <div v-if="can.manageUsers" class="border-t border-slate-100 pt-4">
                            <Button href="/settings/users" variant="secondary" size="sm" class="w-full">
                                <Icon name="users" :size="15" />
                                Manage staff accounts
                            </Button>
                        </div>
                    </div>
                </Card>

                <Card title="Trading entity">
                    <dl class="space-y-2.5 text-[12.5px]">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Company</dt>
                            <dd class="text-right font-medium text-slate-800">{{ company.name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">VAT number</dt>
                            <dd class="text-right font-medium text-slate-800">{{ company.vat_number }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Currency</dt>
                            <dd class="text-right font-medium text-slate-800">{{ company.currency }} · VAT {{ company.vat_rate_label }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Depot</dt>
                            <dd class="text-right font-medium text-slate-800">{{ company.city }} {{ company.postcode }}</dd>
                        </div>
                    </dl>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
