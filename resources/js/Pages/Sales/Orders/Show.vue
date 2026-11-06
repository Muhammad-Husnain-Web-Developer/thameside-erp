<script setup>
import { computed, ref } from 'vue';
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
    order: { type: Object, required: true },
    lines: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const showInvoice = ref(false);
const serialInputs = ref(
    Object.fromEntries(props.lines.filter((line) => line.tracks_serial).map((line) => [line.id, ''])),
);

const form = useForm({
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    serials: {},
});

const serialLines = computed(() => props.lines.filter((line) => line.tracks_serial));

function invoice() {
    form.serials = Object.fromEntries(
        Object.entries(serialInputs.value).map(([id, value]) => [
            id,
            value.split('\n').map((serial) => serial.trim()).filter(Boolean),
        ]),
    );

    form.post(`/sales/orders/${props.order.id}/invoice`, {
        preserveScroll: true,
        onSuccess: () => {
            showInvoice.value = false;
        },
    });
}

function tone() {
    if (props.order.status === 'fulfilled') return 'brand';
    if (props.order.status === 'cancelled') return 'rose';
    return 'amber';
}
</script>

<template>
    <Head :title="order.number" />

    <AppLayout>
        <PageHeader eyebrow="Sales order" :title="order.number" :description="`${order.customer.name} · ordered ${order.ordered_at}`">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Badge :tone="tone()" dot>{{ order.status_label }}</Badge>
                    <Button v-if="can.invoice" @click="showInvoice = !showInvoice">
                        <Icon :name="showInvoice ? 'x' : 'receipt'" :size="16" />
                        {{ showInvoice ? 'Close' : 'Invoice order' }}
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div v-if="showInvoice && can.invoice" class="mb-6">
            <Card title="Invoice this order" description="Invoicing releases the stock and posts debtor, sales and VAT entries in one transaction.">
                <form class="space-y-4" @submit.prevent="invoice">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Field label="Invoice date" for-id="invoice_date" :error="form.errors.invoice_date">
                            <TextInput id="invoice_date" v-model="form.invoice_date" type="date" />
                        </Field>
                        <Field label="Due date" for-id="due_date" :error="form.errors.due_date" :hint="`Defaults to ${order.customer.terms} day terms.`">
                            <TextInput id="due_date" v-model="form.due_date" type="date" />
                        </Field>
                    </div>

                    <div v-for="line in serialLines" :key="line.id" class="rounded-xl border border-amber-200 bg-amber-50/40 p-3">
                        <Field
                            :label="`${line.product} — ${line.quantity} serial number(s)`"
                            :for-id="`serials-${line.id}`"
                            :error="form.errors[`serials.${line.id}`]"
                            hint="One per line, one per unit sold."
                        >
                            <textarea :id="`serials-${line.id}`" v-model="serialInputs[line.id]" rows="3" class="field-input" />
                        </Field>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="secondary" @click="showInvoice = false">Cancel</Button>
                        <Button type="submit" :loading="form.processing">
                            <Icon name="check" :size="16" />
                            Post invoice
                        </Button>
                    </div>
                </form>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card :padded="false">
                    <div class="border-b border-slate-100 px-5 py-3.5">
                        <p class="text-[13px] font-semibold text-slate-900">Order lines</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50/70">
                                <tr>
                                    <th class="table-heading">Product</th>
                                    <th class="table-heading text-right">Qty</th>
                                    <th class="table-heading hidden text-right md:table-cell">Unit price</th>
                                    <th class="table-heading text-right">Line total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="line in lines" :key="line.id">
                                    <td class="table-cell">
                                        <p class="font-medium text-slate-900">{{ line.product }}</p>
                                        <p class="text-[12px] text-slate-500">
                                            {{ line.sku }}<span v-if="line.tracks_serial"> · serial tracked</span>
                                            <span v-if="line.stock < line.quantity" class="text-rose-600"> · only {{ line.stock }} in stock</span>
                                        </p>
                                    </td>
                                    <td class="table-cell text-right tabular-nums">{{ line.quantity }}</td>
                                    <td class="table-cell hidden text-right tabular-nums text-slate-500 md:table-cell">{{ line.unit_price }}</td>
                                    <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ line.total }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50/70">
                                <tr>
                                    <td class="table-cell font-semibold text-slate-900" colspan="3">Subtotal</td>
                                    <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ order.subtotal }}</td>
                                </tr>
                                <tr>
                                    <td class="table-cell text-slate-500" colspan="3">VAT</td>
                                    <td class="table-cell text-right tabular-nums text-slate-600">{{ order.vat }}</td>
                                </tr>
                                <tr>
                                    <td class="table-cell font-semibold text-slate-900" colspan="3">Total</td>
                                    <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ order.total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>

                <Card v-if="invoices.length" title="Invoices" :padded="false">
                    <div class="divide-y divide-slate-100">
                        <div v-for="invoice in invoices" :key="invoice.id" class="flex items-center justify-between gap-3 px-5 py-3">
                            <div>
                                <Link :href="`/sales/invoices/${invoice.id}`" class="font-medium text-slate-900 hover:text-brand-700">{{ invoice.number }}</Link>
                                <p class="text-[12px] text-slate-500">{{ invoice.status.replace('_', ' ') }}</p>
                            </div>
                            <p class="font-medium tabular-nums text-slate-900">{{ invoice.total }}</p>
                        </div>
                    </div>
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Customer">
                    <p class="font-medium text-slate-900">{{ order.customer.name }}</p>
                    <p class="text-[13px] text-slate-500">{{ order.customer.code }}</p>
                    <dl class="mt-4 space-y-2 text-[13px]">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="text-slate-700">{{ order.customer.email ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="text-slate-700">{{ order.customer.phone ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Terms</dt><dd class="text-slate-700">{{ order.customer.terms }} days</dd></div>
                    </dl>
                </Card>

                <Card title="Order information">
                    <dl class="space-y-2 text-[13px]">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Invoiced</dt><dd class="text-slate-700">{{ order.fulfilled_at ?? 'Not yet' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Raised by</dt><dd class="text-slate-700">{{ order.author ?? 'System' }}</dd></div>
                    </dl>
                    <p v-if="order.notes" class="mt-4 border-t border-slate-100 pt-4 text-[13px] text-slate-500">{{ order.notes }}</p>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
