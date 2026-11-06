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
    bill: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
});

const form = useForm({
    amount: (props.bill.outstanding_pence / 100).toFixed(2),
    paid_at: new Date().toISOString().slice(0, 10),
    reference: '',
});

function pay() {
    form.post(`/purchasing/bills/${props.bill.id}/pay`, { preserveScroll: true });
}

function statusTone() {
    return props.bill.status === 'paid' ? 'brand' : 'amber';
}
</script>

<template>
    <Head :title="bill.number" />

    <AppLayout>
        <PageHeader eyebrow="Supplier bill" :title="bill.number" :description="`${bill.supplier.name} · bill dated ${bill.bill_date}`">
            <template #actions>
                <Badge :tone="statusTone()" dot>{{ bill.status.replace('_', ' ') }}</Badge>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card :padded="false">
                    <div class="border-b border-slate-100 px-5 py-3.5">
                        <p class="text-[13px] font-semibold text-slate-900">Bill summary</p>
                        <p class="text-[12px] text-slate-500">Posted from the goods receipt — the underlying document cannot be edited.</p>
                    </div>
                    <dl class="divide-y divide-slate-100 text-sm">
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <dt class="text-slate-500">Net goods</dt>
                            <dd class="font-medium tabular-nums text-slate-900">{{ bill.subtotal }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <dt class="text-slate-500">Input VAT</dt>
                            <dd class="font-medium tabular-nums text-slate-900">{{ bill.vat }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <dt class="font-semibold text-slate-900">Total</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">{{ bill.total }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <dt class="text-slate-500">Paid to date</dt>
                            <dd class="tabular-nums text-slate-700">{{ bill.paid }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <dt class="text-slate-500">Outstanding</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">{{ bill.outstanding }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card v-if="can.pay" title="Record a payment" description="Paying a bill debits trade creditors and credits the bank account.">
                    <form class="grid gap-4 sm:grid-cols-3" @submit.prevent="pay">
                        <Field label="Amount" for-id="amount" :error="form.errors.amount" required>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                                <TextInput id="amount" v-model="form.amount" class="pl-7 tabular-nums" inputmode="decimal" :invalid="!!form.errors.amount" required />
                            </div>
                        </Field>

                        <Field label="Paid on" for-id="paid_at" :error="form.errors.paid_at">
                            <TextInput id="paid_at" v-model="form.paid_at" type="date" />
                        </Field>

                        <Field label="Reference" for-id="reference" :error="form.errors.reference">
                            <TextInput id="reference" v-model="form.reference" placeholder="BACS ref" />
                        </Field>

                        <div class="sm:col-span-3 flex justify-end">
                            <Button type="submit" :loading="form.processing">
                                <Icon name="check" :size="16" />
                                Post payment
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Supplier">
                    <p class="font-medium text-slate-900">{{ bill.supplier.name }}</p>
                    <p class="text-[13px] text-slate-500">{{ bill.supplier.code }}</p>
                    <p class="mt-3 text-[13px] text-slate-500">{{ bill.supplier.email ?? '' }}</p>
                </Card>

                <Card title="Links">
                    <ul class="space-y-2 text-[13px]">
                        <li v-if="bill.order">
                            <Link :href="`/purchasing/purchase-orders/${bill.order.id}`" class="font-medium text-brand-700 hover:text-brand-800">
                                {{ bill.order.number }} (purchase order)
                            </Link>
                        </li>
                        <li v-if="bill.journal">
                            <Link :href="`/accounting/journals/${bill.journal.id}`" class="font-medium text-brand-700 hover:text-brand-800">
                                {{ bill.journal.number }} (journal)
                            </Link>
                        </li>
                        <li class="text-slate-500">Due {{ bill.due_date }}<span v-if="bill.reference"> · ref {{ bill.reference }}</span></li>
                    </ul>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
