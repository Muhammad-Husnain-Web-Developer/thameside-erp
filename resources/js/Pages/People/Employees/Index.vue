<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatCard from '../../../Components/StatCard.vue';

const props = defineProps({
    employees: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q ?? '');
let searchTimer = null;

watch(search, () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
        router.get('/people/employees', { q: search.value.trim() || undefined }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});
</script>

<template>
    <Head title="Employees" />

    <AppLayout>
        <PageHeader
            eyebrow="People"
            title="Employees"
            description="The Thameside team, their departments and salaries. Payroll is calculated from these records each month."
        >
            <template #actions>
                <Button v-if="can.create" href="/people/employees/create">
                    <Icon name="plus" :size="16" />
                    Add employee
                </Button>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard v-for="stat in stats" :key="stat.key" :label="stat.label" :value="stat.value" :meta="stat.meta" :icon="stat.icon" />
        </div>

        <Card :padded="false">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3.5">
                <div class="relative w-full max-w-xs">
                    <Icon name="search" :size="15" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="search" type="search" placeholder="Search name, number or job" class="field-input pl-9" />
                </div>
                <p class="ml-auto text-[12px] text-slate-500">{{ employees.total }} employees</p>
            </div>

            <EmptyState
                v-if="employees.data.length === 0"
                icon="users"
                title="No employees found"
                :description="filters.q ? 'Nothing matches that search.' : 'Add the first employee to start running payroll.'"
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Employee</th>
                            <th class="table-heading hidden lg:table-cell">Department</th>
                            <th class="table-heading hidden md:table-cell">Contract</th>
                            <th class="table-heading hidden xl:table-cell">Started</th>
                            <th class="table-heading text-right">Salary</th>
                            <th class="table-heading"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="employee in employees.data" :key="employee.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <p class="font-medium text-slate-900">{{ employee.name }}</p>
                                <p class="text-[12px] text-slate-500">{{ employee.employee_number }} · {{ employee.job_title }}</p>
                            </td>
                            <td class="table-cell hidden text-[13px] text-slate-600 lg:table-cell">{{ employee.department ?? '—' }}</td>
                            <td class="table-cell hidden text-[13px] text-slate-500 md:table-cell">{{ employee.contract_type }}</td>
                            <td class="table-cell hidden whitespace-nowrap text-[13px] text-slate-500 xl:table-cell">{{ employee.started_on }}</td>
                            <td class="table-cell text-right">
                                <span class="font-medium tabular-nums text-slate-900">{{ employee.salary }}</span>
                                <Badge v-if="!employee.is_active" tone="slate" class="ml-2">Inactive</Badge>
                            </td>
                            <td class="table-cell text-right">
                                <Link
                                    v-if="employee.can.update"
                                    :href="`/people/employees/${employee.id}/edit`"
                                    class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                    title="Edit employee"
                                >
                                    <Icon name="pencil" :size="16" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="employees.data.length > 0" :paginator="employees" />
        </Card>
    </AppLayout>
</template>
