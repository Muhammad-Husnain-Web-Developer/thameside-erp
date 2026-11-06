<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Card from '../../../Components/Card.vue';
import PageHeader from '../../../Components/PageHeader.vue';

defineProps({
    groups: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="Chart of accounts" />

    <AppLayout>
        <PageHeader
            eyebrow="Accounting"
            title="Chart of accounts"
            description="Thameside's nominal ledger. Codes are fixed so invoices, bills and payments always post to the same account."
        />

        <div class="space-y-6">
            <Card v-for="group in groups" :key="group.type" :padded="false">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    <p class="text-[13px] font-semibold text-slate-900">{{ group.label }}</p>
                    <p class="text-[12px] text-slate-500">{{ group.accounts.length }} accounts</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50/70">
                            <tr>
                                <th class="table-heading">Code</th>
                                <th class="table-heading">Account</th>
                                <th class="table-heading hidden text-right md:table-cell">Debits</th>
                                <th class="table-heading hidden text-right md:table-cell">Credits</th>
                                <th class="table-heading text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="account in group.accounts" :key="account.code" class="transition hover:bg-slate-50/60">
                                <td class="table-cell whitespace-nowrap font-medium tabular-nums text-slate-900">{{ account.code }}</td>
                                <td class="table-cell">{{ account.name }}</td>
                                <td class="table-cell hidden text-right tabular-nums text-slate-500 md:table-cell">{{ account.debit }}</td>
                                <td class="table-cell hidden text-right tabular-nums text-slate-500 md:table-cell">{{ account.credit }}</td>
                                <td class="table-cell text-right">
                                    <span class="font-medium tabular-nums text-slate-900">{{ account.balance }}</span>
                                    <Badge :tone="account.side === 'Dr' ? 'brand' : 'slate'" class="ml-2">{{ account.side }}</Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-6 rounded-xl border border-slate-200 bg-slate-50/60 px-5 py-4 text-sm">
            <p class="text-slate-500">Total debits <span class="font-semibold tabular-nums text-slate-900">{{ totals.debit }}</span></p>
            <p class="text-slate-500">Total credits <span class="font-semibold tabular-nums text-slate-900">{{ totals.credit }}</span></p>
        </div>
    </AppLayout>
</template>
