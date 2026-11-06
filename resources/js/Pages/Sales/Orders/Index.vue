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
import SelectInput from '../../../Components/SelectInput.vue';
import StatCard from '../../../Components/StatCard.vue';

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Object, default: () => ({}) },
    stats: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q ?? '');
const status = ref(props.filters.status ?? '');
let searchTimer = null;

function apply() {
    router.get(
        '/sales/orders',
        { q: search.value.trim() || undefined, status: status.value || undefined },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watch(search, () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(apply, 300);
});

watch(status, apply);

function tone(order) {
    if (order.status === 'fulfilled') return 'brand';
    if (order.status === 'cancelled') return 'rose';
    return 'amber';
}
</script>

<template>
    <Head title="Sales orders" />

    <AppLayout>
        <PageHeader
            eyebrow="Sales"
            title="Sales orders"
            description="Confirmed customer orders. Invoicing an order releases the stock and posts the sale to the ledger."
        >
            <template #actions>
                <Button v-if="can.create" href="/sales/orders/create">
                    <Icon name="plus" :size="16" />
                    Raise order
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
                    <input v-model="search" type="search" placeholder="Search number or customer" class="field-input pl-9" />
                </div>
                <div class="w-full sm:w-44">
                    <SelectInput v-model="status" :options="Object.entries(statuses).map(([value, label]) => ({ value, label }))" placeholder="Any status" />
                </div>
                <p class="ml-auto text-[12px] text-slate-500">{{ orders.total }} orders</p>
            </div>

            <EmptyState
                v-if="orders.data.length === 0"
                icon="truck"
                title="No sales orders"
                :description="filters.q || filters.status ? 'Nothing matches those filters.' : 'Raise an order for a trade customer to get started.'"
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Order</th>
                            <th class="table-heading">Customer</th>
                            <th class="table-heading hidden lg:table-cell">Ordered</th>
                            <th class="table-heading">Status</th>
                            <th class="table-heading hidden text-right sm:table-cell">Lines</th>
                            <th class="table-heading text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="order in orders.data" :key="order.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell whitespace-nowrap">
                                <Link :href="`/sales/orders/${order.id}`" class="font-medium text-slate-900 hover:text-brand-700">{{ order.number }}</Link>
                            </td>
                            <td class="table-cell">{{ order.customer }}</td>
                            <td class="table-cell hidden whitespace-nowrap text-[13px] text-slate-500 lg:table-cell">{{ order.ordered_at }}</td>
                            <td class="table-cell"><Badge :tone="tone(order)" dot>{{ order.status_label }}</Badge></td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-500 sm:table-cell">{{ order.lines }}</td>
                            <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ order.total }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="orders.data.length > 0" :paginator="orders" />
        </Card>
    </AppLayout>
</template>
