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
    bills: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const showReceive = ref(false);
const serialInputs = ref(
    Object.fromEntries(props.lines.filter((line) => line.tracks_serial && line.outstanding > 0).map((line) => [line.id, ''])),
);

const form = useForm({
    received_at: new Date().toISOString().slice(0, 10),
    serials: {},
});

const serialLines = computed(() => props.lines.filter((line) => line.tracks_serial && line.outstanding > 0));

function receive() {
    form.serials = Object.fromEntries(
        Object.entries(serialInputs.value).map(([id, value]) => [
            id,
            value.split('\n').map((serial) => serial.trim()).filter(Boolean),
        ]),
    );

    form.post(`/purchasing/purchase-orders/${props.order.id}/receive`, {
        preserveScroll: true,
        onSuccess: () => {
            showReceive.value = false;
        },
    });
}

function statusTone() {
    if (props.order.status === 'received') return 'brand';
    if (props.order.status === 'cancelled') return 'rose';
    return 'amber';
}
</script>

<template>
    <Head :title="order.number" />

    <AppLayout>
        <PageHeader eyebrow="Purchase order" :title="order.number" :description="`${order.supplier.name} · ordered ${order.ordered_at}`">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Badge :tone="statusTone()" dot>{{ order.status_label }}</Badge>
                    <Button v-if="can.receive" variant="primary" @click="showReceive = !showReceive">
                        <Icon :name="showReceive ? 'x' : 'truck'" :size="16" />
                        {{ showReceive ? 'Close' : 'Receive goods' }}
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div v-if="showReceive && can.receive" class="mb-6">
            <Card title="Receive goods" description="Receiving books every outstanding unit into stock and posts the supplier bill with input VAT.">
                <form class="space-y-4" @submit.prevent="receive">
                    <div class="w-full sm:w-52">
                        <Field label="Received on" for-id="received_at" :error="form.errors.received_at">
                            <TextInput id="received_at" v-model="form.received_at" type="date" />
                        </Field>
                    </div>

                    <div v-for="line in serialLines" :key="line.id" class="rounded-xl border border-amber-200 bg-amber-50/40 p-3">
                        <Field
                            :label="`${line.product} — ${line.outstanding} serial number(s)`"
                            :for-id="`serials-${line.id}`"
                            :error="form.errors[`serials.${line.id}`]"
                            hint="One per line, one per unit received."
                        >
                            <textarea :id="`serials-${line.id}`" v-model="serialInputs[line.id]" rows="3" class="field-input" />
                        </Field>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="secondary" @click="showReceive = false">Cancel</Button>
                        <Button type="submit" :loading="form.processing">
                            <Icon name="check" :size="16" />
                            Receive and post bill
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
                                    <th class="table-heading text-right">Ordered</th>
                                    <th class="table-heading text-right">Received</th>
                                    <th class="table-heading hidden text-right md:table-cell">Unit cost</th>
                                    <th class="table-heading text-right">Line total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="line in lines" :key="line.id">
                                    <td class="table-cell">
                                        <p class="font-medium text-slate-900">{{ line.product }}</p>
                                        <p class="text-[12px] text-slate-500">{{ line.sku }}<span v-if="line.tracks_serial"> · serial tracked</span></p>
                                    </td>
                                    <td class="table-cell text-right tabular-nums">{{ line.quantity }}</td>
                                    <td class="table-cell text-right tabular-nums" :class="line.outstanding > 0 ? 'text-amber-700' : 'text-brand-700'">
                                        {{ line.quantity_received }}
                                    </td>
                                    <td class="table-cell hidden text-right tabular-nums text-slate-500 md:table-cell">{{ line.unit_cost }}</td>
                                    <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ line.total }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50/70">
                                <tr>
                                    <td class="table-cell font-semibold text-slate-900" colspan="4">Subtotal</td>
                                    <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ order.subtotal }}</td>
                                </tr>
                                <tr>
                                    <td class="table-cell text-slate-500" colspan="4">VAT</td>
                                    <td class="table-cell text-right tabular-nums text-slate-600">{{ order.vat }}</td>
                                </tr>
                                <tr>
                                    <td class="table-cell font-semibold text-slate-900" colspan="4">Total</td>
                                    <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ order.total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>

                <Card v-if="bills.length" title="Supplier bills" :padded="false">
                    <div class="divide-y divide-slate-100">
                        <div v-for="bill in bills" :key="bill.id" class="flex items-center justify-between gap-3 px-5 py-3">
                            <div>
                                <Link :href="`/purchasing/bills/${bill.id}`" class="font-medium text-slate-900 hover:text-brand-700">{{ bill.number }}</Link>
                                <p class="text-[12px] text-slate-500">Due {{ bill.due_date }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium tabular-nums text-slate-900">{{ bill.total }}</p>
                                <Badge :tone="bill.status === 'paid' ? 'brand' : 'amber'">{{ bill.status }}</Badge>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Supplier">
                    <p class="font-medium text-slate-900">{{ order.supplier.name }}</p>
                    <p class="text-[13px] text-slate-500">{{ order.supplier.code }}</p>
                    <dl class="mt-4 space-y-2 text-[13px]">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="text-slate-700">{{ order.supplier.email ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="text-slate-700">{{ order.supplier.phone ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Terms</dt><dd class="text-slate-700">{{ order.supplier.terms }} days</dd></div>
                    </dl>
                </Card>

                <Card title="Order information">
                    <dl class="space-y-2 text-[13px]">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Expected</dt><dd class="text-slate-700">{{ order.expected_at ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Received</dt><dd class="text-slate-700">{{ order.received_at ?? 'Not yet received' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Raised by</dt><dd class="text-slate-700">{{ order.author ?? 'System' }}</dd></div>
                    </dl>
                    <p v-if="order.notes" class="mt-4 border-t border-slate-100 pt-4 text-[13px] text-slate-500">{{ order.notes }}</p>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
