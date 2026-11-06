<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatCard from '../../../Components/StatCard.vue';

defineProps({
    runs: { type: Object, required: true },
    stats: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="Payroll" />

    <AppLayout>
        <PageHeader
            eyebrow="People"
            title="Payroll"
            description="Monthly runs posting gross wages, PAYE/NI, pension and net pay straight to the nominal ledger."
        >
            <template #actions>
                <Button v-if="can.run" href="/people/payroll/create">
                    <Icon name="plus" :size="16" />
                    Run payroll
                </Button>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard v-for="stat in stats" :key="stat.key" :label="stat.label" :value="stat.value" :meta="stat.meta" :icon="stat.icon" />
        </div>

        <Card :padded="false">
            <EmptyState
                v-if="runs.data.length === 0"
                icon="banknotes"
                title="No payroll runs yet"
                description="Run the first payroll to calculate payslips and post the wages journal."
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Run</th>
                            <th class="table-heading hidden sm:table-cell">Pay date</th>
                            <th class="table-heading hidden text-right md:table-cell">Employees</th>
                            <th class="table-heading text-right">Gross</th>
                            <th class="table-heading hidden text-right lg:table-cell">Deductions</th>
                            <th class="table-heading text-right">Net pay</th>
                            <th class="table-heading hidden lg:table-cell">Journal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="run in runs.data" :key="run.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <Link :href="`/people/payroll/${run.id}`" class="font-medium text-slate-900 hover:text-brand-700">{{ run.reference }}</Link>
                                <p class="text-[12px] text-slate-500">{{ run.period }}</p>
                            </td>
                            <td class="table-cell hidden whitespace-nowrap text-[13px] text-slate-500 sm:table-cell">{{ run.pay_date }}</td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-600 md:table-cell">{{ run.employees }}</td>
                            <td class="table-cell text-right tabular-nums text-slate-900">{{ run.gross }}</td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-500 lg:table-cell">{{ run.deductions }}</td>
                            <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ run.net }}</td>
                            <td class="table-cell hidden text-[13px] text-slate-500 lg:table-cell">{{ run.journal ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="runs.data.length > 0" :paginator="runs" />
        </Card>
    </AppLayout>
</template>
