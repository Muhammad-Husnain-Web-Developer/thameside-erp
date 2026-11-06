<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import AppLayout from '../../Layouts/AppLayout.vue';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import Icon from '../../Components/Icon.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';

const props = defineProps({
    series: { type: Array, default: () => [] },
    topProducts: { type: Array, default: () => [] },
    stats: { type: Array, default: () => [] },
});

const labels = computed(() => props.series.map((row) => row.label));

const chartOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#0f8163', '#94a3b8', '#f59e0b'],
    plotOptions: { bar: { columnWidth: '46%', borderRadius: 4 } },
    dataLabels: { enabled: false },
    stroke: { width: [0, 0, 3], curve: 'smooth' },
    grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
    legend: { position: 'top', horizontalAlign: 'right', markers: { radius: 4 } },
    xaxis: { categories: labels.value, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: {
        labels: { formatter: (value) => `£${Math.round(value).toLocaleString('en-GB')}` },
    },
    tooltip: { y: { formatter: (value) => `£${Number(value).toLocaleString('en-GB', { minimumFractionDigits: 2 })}` } },
}));

const chartSeries = computed(() => [
    { name: 'Sales', type: 'bar', data: props.series.map((row) => row.sales) },
    { name: 'Purchases', type: 'bar', data: props.series.map((row) => row.purchases) },
    { name: 'Gross profit', type: 'line', data: props.series.map((row) => row.profit) },
]);

const totals = computed(() => ({
    sales: props.series.reduce((sum, row) => sum + row.sales, 0),
    purchases: props.series.reduce((sum, row) => sum + row.purchases, 0),
    profit: props.series.reduce((sum, row) => sum + row.profit, 0),
}));

const money = (value) =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 0 }).format(value || 0);
</script>

<template>
    <Head title="Sales summary" />

    <AppLayout>
        <PageHeader
            eyebrow="Reports"
            title="Sales summary"
            description="Six months of invoiced sales against supplier bills, with the gross profit those sales actually earned."
        >
            <template #actions>
                <a
                    href="/reports/exports/invoices"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <Icon name="receipt" :size="16" />
                    Export invoices
                </a>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard v-for="stat in stats" :key="stat.key" :label="stat.label" :value="stat.value" :meta="stat.meta" :icon="stat.icon" />
        </div>

        <Card title="Sales, purchases and gross profit" description="Last six months, net of VAT. Profit is sales less the cost of the units actually sold.">
            <VueApexCharts type="line" height="320" :options="chartOptions" :series="chartSeries" />

            <div class="mt-5 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-3">
                <div>
                    <p class="text-[12px] text-slate-500">Sales</p>
                    <p class="text-lg font-semibold tabular-nums text-slate-900">{{ money(totals.sales) }}</p>
                </div>
                <div>
                    <p class="text-[12px] text-slate-500">Purchases</p>
                    <p class="text-lg font-semibold tabular-nums text-slate-900">{{ money(totals.purchases) }}</p>
                </div>
                <div>
                    <p class="text-[12px] text-slate-500">Gross profit</p>
                    <p class="text-lg font-semibold tabular-nums text-brand-700">{{ money(totals.profit) }}</p>
                </div>
            </div>
        </Card>

        <Card class="mt-6" title="Best sellers" description="Ranked by invoiced revenue across all time." :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Product</th>
                            <th class="table-heading text-right">Units sold</th>
                            <th class="table-heading text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="product in topProducts" :key="product.sku ?? product.name">
                            <td class="table-cell">
                                <p class="font-medium text-slate-900">{{ product.name }}</p>
                                <p class="text-[12px] text-slate-500">{{ product.sku ?? '—' }}</p>
                            </td>
                            <td class="table-cell text-right tabular-nums">{{ product.units }}</td>
                            <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ product.revenue }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
