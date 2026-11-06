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
    suppliers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q ?? '');
let searchTimer = null;

watch(search, () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
        router.get('/purchasing/suppliers', { q: search.value.trim() || undefined }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});
</script>

<template>
    <Head title="Suppliers" />

    <AppLayout>
        <PageHeader
            eyebrow="Purchasing"
            title="Suppliers"
            description="The manufacturers and distributors Thameside buys from, with the balance outstanding on each account."
        >
            <template #actions>
                <Button v-if="can.create" href="/purchasing/suppliers/create">
                    <Icon name="plus" :size="16" />
                    Add supplier
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
                    <input v-model="search" type="search" placeholder="Search name, code or email" class="field-input pl-9" />
                </div>
                <p class="ml-auto text-[12px] text-slate-500">{{ suppliers.total }} suppliers</p>
            </div>

            <EmptyState
                v-if="suppliers.data.length === 0"
                icon="building"
                title="No suppliers found"
                :description="filters.q ? 'Nothing matches that search.' : 'Add the first supplier to start raising purchase orders.'"
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Supplier</th>
                            <th class="table-heading hidden lg:table-cell">Contact</th>
                            <th class="table-heading hidden text-right md:table-cell">Terms</th>
                            <th class="table-heading hidden text-right sm:table-cell">Orders</th>
                            <th class="table-heading text-right">Outstanding</th>
                            <th class="table-heading"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="supplier in suppliers.data" :key="supplier.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <p class="font-medium text-slate-900">{{ supplier.name }}</p>
                                <p class="text-[12px] text-slate-500">{{ supplier.code }}</p>
                            </td>
                            <td class="table-cell hidden lg:table-cell">
                                <p class="text-[13px] text-slate-700">{{ supplier.contact_name ?? '—' }}</p>
                                <p class="text-[12px] text-slate-500">{{ supplier.email ?? supplier.phone ?? '' }}</p>
                            </td>
                            <td class="table-cell hidden text-right tabular-nums md:table-cell">{{ supplier.payment_terms_days }} days</td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-500 sm:table-cell">{{ supplier.orders }}</td>
                            <td class="table-cell text-right">
                                <span class="font-medium tabular-nums text-slate-900">{{ supplier.outstanding }}</span>
                                <Badge v-if="!supplier.is_active" tone="slate" class="ml-2">Inactive</Badge>
                            </td>
                            <td class="table-cell text-right">
                                <Link
                                    v-if="supplier.can.update"
                                    :href="`/purchasing/suppliers/${supplier.id}/edit`"
                                    class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                    title="Edit supplier"
                                >
                                    <Icon name="pencil" :size="16" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="suppliers.data.length > 0" :paginator="suppliers" />
        </Card>
    </AppLayout>
</template>
