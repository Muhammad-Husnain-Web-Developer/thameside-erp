<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import Field from '../../../Components/Field.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import TextInput from '../../../Components/TextInput.vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    lines: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const form = useForm({
    amount: (props.invoice.outstanding_pence / 100).toFixed(2),
    received_at: new Date().toISOString().slice(0, 10),
    reference: '',
});

function pay() {
    form.post(`/sales/invoices/${props.invoice.id}/pay`, { preserveScroll: true });
}

function tone() {
    return props.invoice.status === 'paid' ? 'brand' : 'amber';
}
</script>

<template>
    <Head :title="invoice.number" />

    <AppLayout>
        <PageHeader eyebrow="Sales invoice" :title="invoice.number" :description="`${invoice.customer.name} · invoiced ${invoice.invoice_date}`">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Badge :tone="tone()" dot>{{ invoice.status.replace('_', ' ') }}</Badge>
                    <a
                        :href="`/sales/invoices/${invoice.id}/pdf`"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <Icon name="receipt" :size="16" />
                        Download PDF
                    </a>
                </div>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card :padded="false">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <p class="text-[13px] font-semibold text-slate-900">Invoice lines</p>
                            <p class="text-[12px] text-slate-500">{{ invoice.customer.name }} · {{ invoice.customer.city }}</p>
                        </div>
                        <div class="text-right text-[12px] text-slate-500">
                            <p>Due {{ invoice.due_date }}</p>
                            <p v-if="invoice.order">
                                Order <Link :href="`/sales/orders/${invoice.order.id}`" class="font-medium text-brand-700">{{ invoice.order.number }}</Link>
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50/70">
                                <tr>
                                    <th class="table-heading">Description</th>
                                    <th class="table-heading text-right">Qty</th>
                                    <th class="table-heading hidden text-right sm:table-cell">Unit price</th>
                                    <th class="table-heading text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="line in lines" :key="line.id">
                                    <td class="table-cell font-medium text-slate-900">{{ line.description }}</td>
                                    <td class="table-cell text-right tabular-nums">{{ line.quantity }}</td>
                                    <td class="table-cell hidden text-right tabular-nums text-slate-500 sm:table-cell">{{ line.unit_price }}</td>
                                    <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ line.total }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50/70">
                                <tr>
                                    <td class="table-cell font-semibold text-slate-900" colspan="3">Subtotal</td>
                                    <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ invoice.subtotal }}</td>
                                </tr>
                                <tr>
                                    <td class="table-cell text-slate-500" colspan="3">VAT</td>
                                    <td class="table-cell text-right tabular-nums text-slate-600">{{ invoice.vat }}</td>
                                </tr>
                                <tr>
                                    <td class="table-cell font-semibold text-slate-900" colspan="3">Total</td>
                                    <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ invoice.total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>

                <Card v-if="can.pay" title="Record a receipt" description="Receiving payment debits the bank and clears the debtor balance.">
                    <form class="grid gap-4 sm:grid-cols-3" @submit.prevent="pay">
                        <Field label="Amount" for-id="amount" :error="form.errors.amount" required>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                                <TextInput id="amount" v-model="form.amount" class="pl-7 tabular-nums" inputmode="decimal" :invalid="!!form.errors.amount" required />
                            </div>
                        </Field>

                        <Field label="Received on" for-id="received_at" :error="form.errors.received_at">
                            <TextInput id="received_at" v-model="form.received_at" type="date" />
                        </Field>

                        <Field label="Reference" for-id="reference" :error="form.errors.reference">
                            <TextInput id="reference" v-model="form.reference" placeholder="BACS ref" />
                        </Field>

                        <div class="sm:col-span-3 flex justify-end">
                            <Button type="submit" :loading="form.processing">
                                <Icon name="check" :size="16" />
                                Post receipt
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Balance">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Invoiced</dt><dd class="font-medium tabular-nums text-slate-900">{{ invoice.total }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Paid</dt><dd class="tabular-nums text-slate-700">{{ invoice.paid }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Outstanding</dt><dd class="font-semibold tabular-nums text-slate-900">{{ invoice.outstanding }}</dd></div>
                    </dl>
                </Card>

                <Card title="Customer">
                    <p class="font-medium text-slate-900">{{ invoice.customer.name }}</p>
                    <p class="text-[13px] text-slate-500">{{ invoice.customer.code }}</p>
                    <p class="mt-3 text-[13px] text-slate-500">{{ invoice.customer.email ?? '' }}</p>
                    <p class="text-[13px] text-slate-500">{{ invoice.customer.address ?? '' }}</p>
                    <p class="text-[13px] text-slate-500">{{ invoice.customer.city ?? '' }} {{ invoice.customer.postcode ?? '' }}</p>
                </Card>

                <Card v-if="invoice.journal" title="Accounting">
                    <Link :href="`/accounting/journals/${invoice.journal.id}`" class="font-medium text-brand-700 hover:text-brand-800">
                        {{ invoice.journal.number }} (journal)
                    </Link>
                    <p class="mt-2 text-[12px] text-slate-500">Debtors, sales and output VAT posted automatically when the invoice was raised.</p>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
