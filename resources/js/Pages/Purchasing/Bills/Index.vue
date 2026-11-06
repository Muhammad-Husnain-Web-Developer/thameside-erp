<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Card from '../../../Components/Card.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import SelectInput from '../../../Components/SelectInput.vue';
import StatCard from '../../../Components/StatCard.vue';

const props = defineProps({
    bills: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    suppliers: { type: Array, default: () => [] },
    stats: { type: Array, default: () => [] },
});

const status = ref(props.filters.status ?? '');
const supplier = ref(props.filters.supplier ?? '');

watch([status, supplier], () => {
    router.get(
        '/purchasing/bills',
        { status: status.value || undefined, supplier: supplier.value || undefined },
        { preserveState: true, replace: true, preserveScroll: true },
    );
});

function tone(bill) {
    if (bill.status === 'paid') return 'brand';
    if (bill.is_overdue) return 'rose';
    return 'amber';
}
</script>

<template>
    <Head title="Supplier bills" />

    <AppLayout>
        <PageHeader
            eyebrow="Purchasing"
            title="Supplier bills"
            description="Every bill posted from a goods receipt, the VAT that came with it and what is still owed."
        />

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard v-for="stat in stats" :key="stat.key" :label="stat.label" :value="stat.value" :meta="stat.meta" :icon="stat.icon" />
        </div>

        <Card :padded="false">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3.5">
                <div class="w-full sm:w-52">
                    <SelectInput
                        v-model="status"
                        :options="[{ value: 'unpaid', label: 'Unpaid' }, { value: 'part_paid', label: 'Part paid' }, { value: 'paid', label: 'Paid' }]"
                        placeholder="Any status"
                    />
                </div>
                <div class="w-full sm:w-60">
                    <SelectInput v-model="supplier" :options="suppliers.map((item) => ({ value: item.id, label: item.name }))" placeholder="All suppliers" />
                </div>
                <p class="ml-auto text-[12px] text-slate-500">{{ bills.total }} bills</p>
            </div>

            <EmptyState
                v-if="bills.data.length === 0"
                icon="receipt"
                title="No supplier bills"
                :description="filters.status || filters.supplier ? 'Nothing matches those filters.' : 'Bills appear here when a purchase order is received.'"
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Bill</th>
                            <th class="table-heading">Supplier</th>
                            <th class="table-heading hidden lg:table-cell">Bill date</th>
                            <th class="table-heading hidden md:table-cell">Due</th>
                            <th class="table-heading">Status</th>
                            <th class="table-heading text-right">Total</th>
                            <th class="table-heading text-right">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="bill in bills.data" :key="bill.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell whitespace-nowrap">
                                <Link :href="`/purchasing/bills/${bill.id}`" class="font-medium text-slate-900 hover:text-brand-700">{{ bill.number }}</Link>
                            </td>
                            <td class="table-cell">{{ bill.supplier }}</td>
                            <td class="table-cell hidden whitespace-nowrap text-[13px] text-slate-500 lg:table-cell">{{ bill.bill_date }}</td>
                            <td class="table-cell hidden whitespace-nowrap text-[13px] text-slate-500 md:table-cell">{{ bill.due_date }}</td>
                            <td class="table-cell">
                                <Badge :tone="tone(bill)" dot>{{ bill.is_overdue ? 'Overdue' : bill.status.replace('_', ' ') }}</Badge>
                            </td>
                            <td class="table-cell text-right tabular-nums text-slate-900">{{ bill.total }}</td>
                            <td class="table-cell text-right font-medium tabular-nums" :class="bill.outstanding === '£0.00' ? 'text-slate-400' : 'text-slate-900'">
                                {{ bill.outstanding }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="bills.data.length > 0" :paginator="bills" />
        </Card>
    </AppLayout>
</template>
