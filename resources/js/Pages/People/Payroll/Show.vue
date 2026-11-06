<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';

defineProps({
    run: { type: Object, required: true },
    payslips: { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="run.reference" />

    <AppLayout>
        <PageHeader eyebrow="Payroll" :title="run.reference" :description="`${run.period} · paid ${run.pay_date}`">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Badge tone="brand" dot>Posted</Badge>
                    <Button href="/people/payroll" variant="secondary">
                        <Icon name="arrow-left" :size="16" />
                        Back to payroll
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="card p-5">
                <p class="text-[13px] font-medium text-slate-500">Gross pay</p>
                <p class="mt-2 text-[26px] font-semibold tabular-nums text-slate-900">{{ run.gross }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ run.employees }} employees</p>
            </div>
            <div class="card p-5">
                <p class="text-[13px] font-medium text-slate-500">PAYE & NI</p>
                <p class="mt-2 text-[26px] font-semibold tabular-nums text-slate-900">{{ run.paye }}</p>
                <p class="mt-2 text-xs text-slate-500">NI {{ run.ni }} · PAYE {{ run.paye }}</p>
            </div>
            <div class="card p-5">
                <p class="text-[13px] font-medium text-slate-500">Pension</p>
                <p class="mt-2 text-[26px] font-semibold tabular-nums text-slate-900">{{ run.pension }}</p>
                <p class="mt-2 text-xs text-slate-500">Employee contributions</p>
            </div>
            <div class="card p-5">
                <p class="text-[13px] font-medium text-slate-500">Net pay</p>
                <p class="mt-2 text-[26px] font-semibold tabular-nums text-brand-700">{{ run.net }}</p>
                <p class="mt-2 text-xs text-slate-500">Paid to employees</p>
            </div>
        </div>

        <Card :padded="false">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                <div>
                    <p class="text-[13px] font-semibold text-slate-900">Payslips</p>
                    <p class="text-[12px] text-slate-500">{{ run.period_start }} – {{ run.period_end }}</p>
                </div>
                <Link v-if="run.journal" :href="`/accounting/journals/${run.journal.id}`" class="text-[13px] font-medium text-brand-700 hover:text-brand-800">
                    View journal {{ run.journal.number }}
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Employee</th>
                            <th class="table-heading hidden lg:table-cell">Department</th>
                            <th class="table-heading text-right">Gross</th>
                            <th class="table-heading hidden text-right md:table-cell">PAYE</th>
                            <th class="table-heading hidden text-right md:table-cell">NI</th>
                            <th class="table-heading hidden text-right lg:table-cell">Pension</th>
                            <th class="table-heading text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="payslip in payslips" :key="payslip.id">
                            <td class="table-cell">
                                <p class="font-medium text-slate-900">{{ payslip.employee }}</p>
                                <p class="text-[12px] text-slate-500">{{ payslip.number }}</p>
                            </td>
                            <td class="table-cell hidden text-[13px] text-slate-500 lg:table-cell">{{ payslip.department ?? '—' }}</td>
                            <td class="table-cell text-right tabular-nums text-slate-900">{{ payslip.gross }}</td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-500 md:table-cell">{{ payslip.paye }}</td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-500 md:table-cell">{{ payslip.ni }}</td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-500 lg:table-cell">{{ payslip.pension }}</td>
                            <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ payslip.net }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
