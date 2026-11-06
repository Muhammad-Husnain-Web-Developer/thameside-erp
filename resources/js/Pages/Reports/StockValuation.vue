<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import AppLayout from '../../Layouts/AppLayout.vue';
import Badge from '../../Components/Badge.vue';
import Card from '../../Components/Card.vue';
import Icon from '../../Components/Icon.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    stats: { type: Array, default: () => [] },
});

const donutOptions = computed(() => ({
    chart: { fontFamily: 'inherit' },
    labels: props.categories.map((row) => row.category),
    colors: ['#0f8163', '#0d9488', '#14b8a6', '#5eead4', '#94a3b8', '#cbd5e1', '#f59e0b'],
    legend: { position: 'bottom', fontSize: '12px' },
    dataLabels: { enabled: true, formatter: (value) => `${Math.round(value)}%` },
    tooltip: { y: { formatter: (value) => `£${Number(value).toLocaleString('en-GB')}` } },
    stroke: { width: 2, colors: ['#fff'] },
}));

const donutSeries = computed(() => props.categories.map((row) => row.value_float));
</script>

<template>
    <Head title="Stock valuation" />

    <AppLayout>
        <PageHeader
            eyebrow="Reports"
            title="Stock valuation"
            description="What the depot is holding, valued at cost and at retail, split by product category."
        >
            <template #actions>
                <a
                    href="/reports/exports/stock"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <Icon name="box" :size="16" />
                    Export to Excel
                </a>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard v-for="stat in stats" :key="stat.key" :label="stat.label" :value="stat.value" :meta="stat.meta" :icon="stat.icon" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card title="Value by category" description="Share of stock at cost.">
                <VueApexCharts type="donut" height="300" :options="donutOptions" :series="donutSeries" />
            </Card>

            <div class="lg:col-span-2">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50/70">
                                <tr>
                                    <th class="table-heading">Category</th>
                                    <th class="table-heading text-right">Units</th>
                                    <th class="table-heading text-right">At cost</th>
                                    <th class="table-heading text-right">At retail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="category in categories" :key="category.category">
                                    <td class="table-cell font-medium text-slate-900">{{ category.category }}</td>
                                    <td class="table-cell text-right tabular-nums">{{ category.units }}</td>
                                    <td class="table-cell text-right tabular-nums text-slate-900">{{ category.value }}</td>
                                    <td class="table-cell text-right tabular-nums text-slate-500">{{ category.retail }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>

        <Card class="mt-6" title="Highest value lines" description="The stock lines tying up the most cash." :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Product</th>
                            <th class="table-heading hidden lg:table-cell">Category</th>
                            <th class="table-heading text-right">On hand</th>
                            <th class="table-heading text-right">At cost</th>
                            <th class="table-heading hidden text-right sm:table-cell">At retail</th>
                            <th class="table-heading"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="product in products" :key="product.sku">
                            <td class="table-cell">
                                <p class="font-medium text-slate-900">{{ product.name }}</p>
                                <p class="text-[12px] text-slate-500">{{ product.sku }}</p>
                            </td>
                            <td class="table-cell hidden text-[13px] text-slate-500 lg:table-cell">{{ product.category }}</td>
                            <td class="table-cell text-right tabular-nums">{{ product.on_hand }} {{ product.unit }}</td>
                            <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ product.value }}</td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-500 sm:table-cell">{{ product.retail }}</td>
                            <td class="table-cell text-right">
                                <Badge v-if="product.is_low" tone="amber">Low</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
