<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import SelectInput from '../../../Components/SelectInput.vue';
import StatCard from '../../../Components/StatCard.vue';

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q ?? '');
const category = ref(props.filters.category ?? '');
const stock = ref(props.filters.stock ?? '');

let searchTimer = null;

function applyFilters(overrides = {}) {
    router.get(
        '/inventory/products',
        {
            q: search.value.trim() || undefined,
            category: category.value || undefined,
            stock: stock.value || undefined,
            ...overrides,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watch(search, () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => applyFilters(), 300);
});

watch([category, stock], () => applyFilters());

const deleteTarget = ref(null);
const deleting = ref(false);

function confirmDelete() {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/inventory/products/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}

function stockTone(product) {
    if (product.stock_on_hand <= 0) {
        return 'rose';
    }

    return product.is_low ? 'amber' : 'brand';
}

function stockLabel(product) {
    if (product.stock_on_hand <= 0) {
        return 'Out of stock';
    }

    return product.is_low ? 'Low stock' : 'In stock';
}
</script>

<template>
    <Head title="Products" />

    <AppLayout>
        <PageHeader
            eyebrow="Inventory"
            title="Product catalogue"
            description="Every appliance Thameside stocks, with the price it sells at and the stock the ledger says is on the shelf."
        >
            <template #actions>
                <Button v-if="can.create" href="/inventory/products/create">
                    <Icon name="plus" :size="16" />
                    Add product
                </Button>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="(stat, index) in stats"
                :key="stat.key"
                :label="stat.label"
                :value="stat.value"
                :meta="stat.meta"
                :icon="stat.icon"
                :tone="index === 3 && Number(stat.value) > 0 ? 'amber' : 'brand'"
            />
        </div>

        <Card :padded="false">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3.5">
                <div class="relative w-full max-w-xs">
                    <Icon name="search" :size="15" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="search" type="search" placeholder="Search SKU, name or brand" class="field-input pl-9" />
                </div>

                <div class="w-full sm:w-44">
                    <SelectInput v-model="category" :options="categories.map((item) => ({ value: item.id, label: item.name }))" placeholder="All categories" />
                </div>

                <div class="w-full sm:w-40">
                    <SelectInput
                        v-model="stock"
                        :options="[{ value: 'low', label: 'Low stock' }, { value: 'out', label: 'Out of stock' }]"
                        placeholder="Any stock level"
                    />
                </div>

                <p class="ml-auto text-[12px] text-slate-500">
                    {{ products.total }} {{ products.total === 1 ? 'product' : 'products' }}
                </p>
            </div>

            <EmptyState
                v-if="products.data.length === 0"
                icon="box"
                title="No products found"
                :description="filters.q || filters.category || filters.stock ? 'Nothing matches those filters. Try clearing them.' : 'Add your first catalogue line to start receiving stock.'"
            >
                <Button v-if="can.create && !filters.q && !filters.category && !filters.stock" href="/inventory/products/create" size="sm">
                    <Icon name="plus" :size="15" />
                    Add product
                </Button>
            </EmptyState>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Product</th>
                            <th class="table-heading hidden lg:table-cell">Category</th>
                            <th class="table-heading">Stock</th>
                            <th class="table-heading hidden text-right md:table-cell">Cost</th>
                            <th class="table-heading text-right">Sell</th>
                            <th class="table-heading hidden text-right sm:table-cell">Margin</th>
                            <th class="table-heading text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="product in products.data" :key="product.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                        <Icon name="box" :size="17" />
                                    </span>
                                    <div class="min-w-0">
                                        <Link :href="`/inventory/products/${product.id}`" class="flex items-center gap-1.5 font-medium text-slate-900 hover:text-brand-700">
                                            <span class="truncate">{{ product.name }}</span>
                                            <span v-if="product.tracks_serial" class="text-slate-300" title="Serial tracked">
                                                <Icon name="identification" :size="13" />
                                            </span>
                                        </Link>
                                        <p class="truncate text-[12px] text-slate-500">
                                            {{ product.sku }}<span v-if="product.brand"> · {{ product.brand }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell hidden lg:table-cell">{{ product.category ?? '—' }}</td>
                            <td class="table-cell">
                                <div class="flex flex-col items-start gap-1">
                                    <Badge :tone="stockTone(product)" dot>
                                        {{ stockLabel(product) }}
                                    </Badge>
                                    <span class="text-[12px] tabular-nums text-slate-500">
                                        {{ product.stock_on_hand }} {{ product.unit }}<span v-if="product.tracks_serial"> · serials</span>
                                    </span>
                                </div>
                            </td>
                            <td class="table-cell hidden text-right tabular-nums md:table-cell">{{ product.cost_price }}</td>
                            <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ product.sell_price }}</td>
                            <td class="table-cell hidden text-right tabular-nums sm:table-cell">{{ product.margin }}%</td>
                            <td class="table-cell">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        v-if="product.can.update"
                                        :href="`/inventory/products/${product.id}/edit`"
                                        class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                        title="Edit product"
                                    >
                                        <Icon name="pencil" :size="16" />
                                        <span class="sr-only">Edit {{ product.name }}</span>
                                    </Link>
                                    <button
                                        v-if="product.can.delete"
                                        type="button"
                                        class="rounded-md p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                        title="Remove product"
                                        @click="deleteTarget = product"
                                    >
                                        <Icon name="trash" :size="16" />
                                        <span class="sr-only">Remove {{ product.name }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="products.data.length > 0" :paginator="products" />
        </Card>

        <ConfirmDialog
            :show="!!deleteTarget"
            title="Remove product?"
            :message="deleteTarget ? `${deleteTarget.name} will be removed from the catalogue. Products with stock on hand cannot be removed.` : ''"
            confirm-label="Remove product"
            :processing="deleting"
            @confirm="confirmDelete"
            @close="deleteTarget = null"
        />
    </AppLayout>
</template>
