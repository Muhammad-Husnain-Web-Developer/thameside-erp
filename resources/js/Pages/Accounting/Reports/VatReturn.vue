<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Field from '../../../Components/Field.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatCard from '../../../Components/StatCard.vue';
import TextInput from '../../../Components/TextInput.vue';

const props = defineProps({
    period: { type: Object, required: true },
    summary: { type: Object, required: true },
    boxes: { type: Array, default: () => [] },
    entries: { type: Array, default: () => [] },
});

const from = ref(props.period.from_input);
const to = ref(props.period.to_input);

function apply() {
    router.get(
        '/accounting/reports/vat-return',
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="VAT return" />

    <AppLayout>
        <PageHeader
            eyebrow="Accounting"
            :title="`VAT return · ${period.from} – ${period.to}`"
            description="Boxes 1 to 6 calculated straight from the posted VAT control accounts. Thameside is on the standard 20% rate."
        >
            <template #actions>
                <Badge :tone="summary.is_repayment ? 'brand' : 'amber'" dot>
                    {{ summary.is_repayment ? 'Repayment due from HMRC' : 'Payment due to HMRC' }}
                </Badge>
            </template>
        </PageHeader>

        <Card class="mb-6">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
                <div class="w-full sm:w-48">
                    <Field label="From" for-id="from">
                        <TextInput id="from" v-model="from" type="date" />
                    </Field>
                </div>
                <div class="w-full sm:w-48">
                    <Field label="To" for-id="to">
                        <TextInput id="to" v-model="to" type="date" />
                    </Field>
                </div>
                <Button type="submit">
                    <Icon name="filter" :size="15" />
                    Apply period
                </Button>
            </form>
        </Card>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Output VAT (sales)" :value="summary.output_vat" meta="Box 1" icon="receipt" />
            <StatCard label="Input VAT (purchases)" :value="summary.input_vat" meta="Box 4" icon="banknotes" />
            <StatCard
                :label="summary.is_repayment ? 'Repayment due' : 'Net VAT due'"
                :value="summary.net_due"
                meta="Box 5"
                icon="chart"
                :tone="summary.is_repayment ? 'brand' : 'amber'"
            />
            <StatCard label="Sales excl. VAT" :value="summary.sales_net" meta="Box 6" icon="building" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card :padded="false">
                <div class="border-b border-slate-100 px-5 py-3.5">
                    <p class="text-[13px] font-semibold text-slate-900">Return boxes</p>
                    <p class="text-[12px] text-slate-500">Copy these figures onto the HMRC return.</p>
                </div>
                <ul class="divide-y divide-slate-100">
                    <li v-for="box in boxes" :key="box.box" class="flex items-start justify-between gap-4 px-5 py-3.5">
                        <div class="flex gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded bg-slate-100 text-[11px] font-semibold text-slate-600">
                                {{ box.box }}
                            </span>
                            <p class="text-[13px] text-slate-600">{{ box.label }}</p>
                        </div>
                        <p class="whitespace-nowrap font-medium tabular-nums text-slate-900">{{ box.value }}</p>
                    </li>
                </ul>
            </Card>

            <Card :padded="false">
                <div class="border-b border-slate-100 px-5 py-3.5">
                    <p class="text-[13px] font-semibold text-slate-900">VAT postings in this period</p>
                    <p class="text-[12px] text-slate-500">Every line that hit the VAT control accounts.</p>
                </div>

                <EmptyState
                    v-if="entries.length === 0"
                    icon="receipt"
                    title="No VAT postings"
                    description="No sales or purchase invoices were posted in this period."
                />

                <div v-else class="max-h-[28rem] overflow-y-auto">
                    <table class="min-w-full">
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(entry, index) in entries" :key="`${entry.id}-${index}`">
                                <td class="table-cell">
                                    <Link :href="`/accounting/journals/${entry.id}`" class="font-medium text-slate-900 hover:text-brand-700">
                                        {{ entry.number }}
                                    </Link>
                                    <p class="text-[12px] text-slate-500">{{ entry.date }} · {{ entry.description }}</p>
                                </td>
                                <td class="table-cell whitespace-nowrap text-right">
                                    <Badge :tone="entry.direction === 'Output' ? 'brand' : 'slate'">{{ entry.direction }}</Badge>
                                    <p class="mt-1 text-[12px] tabular-nums text-slate-500">{{ entry.vat }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
