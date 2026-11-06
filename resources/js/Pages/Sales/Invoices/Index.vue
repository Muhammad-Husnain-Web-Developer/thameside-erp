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
    invoices: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Array, default: () => [] },
});

const search = ref(props.filters.q ?? '');
const status = ref(props.filters.status ?? '');
let searchTimer = null;

function apply() {
    router.get(
        '/sales/invoices',
        { status: status.value || undefined, q: search.value.trim() || undefined },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watch(search, () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(apply, 300);
});

watch(status, apply);

function tone(invoice) {
    if (invoice.status === 'paid') return 'brand';
    if (invoice.is_overdue) return 'rose';
    return 'amber';
}
</script>

<template>
    <Head title="Invoices" />

    <AppLayout>
        <PageHeader
            eyebrow="Sales"
            title="Invoices"
            description="Every posted sales invoice, the VAT it carries and what is still owed by the customer."
        />

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard v-for="stat in stats" :key="stat.key" :label="stat.label" :value="stat.value" :meta="stat.meta" :icon="stat.icon" />
        </div>

        <Card :padded="false">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3.5">
                <div class="relative w-full max-w-xs">
                    <Icon name="search" :size="15" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="search" type="search" placeholder="Search number or customer" class="field-input pl-9" />
                </div>
                <div class="w-full sm:w-44">
                    <SelectInput
                        v-model="status"
                        :options="[{ value: 'posted', label: 'Posted' }, { value: 'part_paid', label: 'Part paid' }, { value: 'paid', label: 'Paid' }]"
                        placeholder="Any status"
                    />
                </div>
                <p class="ml-auto text-[12px] text-slate-500">{{ invoices.total }} invoices</p>
            </div>

            <EmptyState
                v-if="invoices.data.length === 0"
                icon="receipt"
                title="No invoices"
                :description="filters.q || filters.status ? 'Nothing matches those filters.' : 'Invoice a sales order to raise the first one.'"
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Invoice</th>
                            <th class="table-heading">Customer</th>
                            <th class="table-heading hidden lg:table-cell">Date</th>
                            <th class="table-heading hidden md:table-cell">Due</th>
                            <th class="table-heading">Status</th>
                            <th class="table-heading text-right">Total</th>
                            <th class="table-heading text-right">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell whitespace-nowrap">
                                <Link :href="`/sales/invoices/${invoice.id}`" class="font-medium text-slate-900 hover:text-brand-700">{{ invoice.number }}</Link>
                            </td>
                            <td class="table-cell">{{ invoice.customer }}</td>
                            <td class="table-cell hidden whitespace-nowrap text-[13px] text-slate-500 lg:table-cell">{{ invoice.invoice_date }}</td>
                            <td class="table-cell hidden whitespace-nowrap text-[13px] text-slate-500 md:table-cell">{{ invoice.due_date }}</td>
                            <td class="table-cell">
                                <Badge :tone="tone(invoice)" dot>{{ invoice.is_overdue ? 'Overdue' : invoice.status.replace('_', ' ') }}</Badge>
                            </td>
                            <td class="table-cell text-right tabular-nums text-slate-900">{{ invoice.total }}</td>
                            <td class="table-cell text-right font-medium tabular-nums" :class="invoice.outstanding === '£0.00' ? 'text-slate-400' : 'text-slate-900'">
                                {{ invoice.outstanding }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="invoices.data.length > 0" :paginator="invoices" />
        </Card>
    </AppLayout>
</template>
