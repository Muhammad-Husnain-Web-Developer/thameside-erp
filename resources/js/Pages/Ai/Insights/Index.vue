<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Card from '../../../Components/Card.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatCard from '../../../Components/StatCard.vue';

defineProps({
    insight: { type: Object, required: true },
    forecast: { type: Array, default: () => [] },
    anomalies: { type: Array, default: () => [] },
    stats: { type: Array, default: () => [] },
});

function sourceBadge(source) {
    if (source === 'model') return { label: 'Groq analysis', tone: 'brand' };
    if (source === 'queued') return { label: 'Queued retry', tone: 'amber' };
    return { label: 'Built-in rules', tone: 'slate' };
}

function severityTone(severity) {
    return severity === 'high' ? 'rose' : 'amber';
}
</script>

<template>
    <Head title="AI insights" />

    <AppLayout>
        <PageHeader
            eyebrow="Insights"
            title="Operational insights"
            description="Read-only analysis of demand and ledger anomalies. Nothing here changes stock, prices or journals — it only points at what deserves attention."
        />

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard v-for="stat in stats" :key="stat.key" :label="stat.label" :value="stat.value" :meta="stat.meta" :icon="stat.icon" />
        </div>

        <Card class="mb-6" title="This month's summary">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-100">
                    <Icon name="sparkles" :size="18" />
                </span>
                <div>
                    <Badge :tone="sourceBadge(insight.source).tone" dot>{{ sourceBadge(insight.source).label }}</Badge>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ insight.text }}</p>
                    <p class="mt-2 text-[12px] text-slate-500">
                        Generated {{ new Date(insight.generated_at).toLocaleString('en-GB') }}
                        <span v-if="insight.model"> · {{ insight.model }}</span>
                    </p>
                </div>
            </div>
        </Card>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card title="Reorder suggestions" description="Based on the last six months of sales and current cover." :padded="false">
                <EmptyState v-if="forecast.length === 0" icon="box" title="Nothing to reorder" description="Every line has at least three months of cover." />

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50/70">
                            <tr>
                                <th class="table-heading">Product</th>
                                <th class="table-heading text-right">Cover</th>
                                <th class="table-heading text-right">Order</th>
                                <th class="table-heading text-right">Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in forecast" :key="row.product_id">
                                <td class="table-cell">
                                    <p class="font-medium text-slate-900">{{ row.name }}</p>
                                    <p class="text-[12px] text-slate-500">{{ row.sku }} · {{ row.on_hand }} on hand</p>
                                </td>
                                <td class="table-cell text-right">
                                    <Badge :tone="row.urgent ? 'amber' : 'slate'">
                                        {{ row.months_cover === null ? 'No sales' : `${row.months_cover} mo` }}
                                    </Badge>
                                </td>
                                <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ row.suggested_order }}</td>
                                <td class="table-cell text-right tabular-nums text-slate-500">{{ row.suggested_cost }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <Card title="Anomalies" description="Unusual movements, invoices and overdue debt from the last 90 days." :padded="false">
                <EmptyState v-if="anomalies.length === 0" icon="shield" title="Nothing unusual" description="No outliers were detected in the recent ledger." />

                <ul v-else class="divide-y divide-slate-100">
                    <li v-for="(anomaly, index) in anomalies" :key="`${anomaly.title}-${index}`" class="px-5 py-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <Link :href="anomaly.href" class="text-[13px] font-medium text-slate-900 hover:text-brand-700">{{ anomaly.title }}</Link>
                            <Badge :tone="severityTone(anomaly.severity)">{{ anomaly.severity }}</Badge>
                        </div>
                        <p class="mt-1 text-[12px] text-slate-500">{{ anomaly.detail }}</p>
                    </li>
                </ul>
            </Card>
        </div>

        <p class="mt-4 text-[12px] text-slate-500">
            Forecasts use average monthly units sold; anomalies compare against the 90-day average. The AI layer only writes the summary sentence — the numbers come from SQL.
        </p>
    </AppLayout>
</template>
