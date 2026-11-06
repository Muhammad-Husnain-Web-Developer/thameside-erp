<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import Field from '../../../Components/Field.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import SelectInput from '../../../Components/SelectInput.vue';
import TextInput from '../../../Components/TextInput.vue';

const props = defineProps({
    customers: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    today: { type: String, required: true },
    suggestedNumber: { type: String, default: '' },
});

function blankLine() {
    return { product_id: '', quantity: 1, unit_price: '', description: '' };
}

const form = useForm({
    customer_id: '',
    ordered_at: props.today,
    notes: '',
    lines: [blankLine()],
});

const money = (value) =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(value || 0);

const subtotal = computed(() =>
    form.lines.reduce((sum, line) => sum + (parseFloat(line.unit_price) || 0) * (parseInt(line.quantity, 10) || 0), 0),
);
const vat = computed(() => Math.round(subtotal.value * 0.2 * 100) / 100);
const total = computed(() => Math.round((subtotal.value + vat.value) * 100) / 100);

function productSelected(line) {
    const product = props.products.find((item) => String(item.value) === String(line.product_id));

    if (product) {
        line.unit_price = product.price;
        line.description = product.label.split(' · ')[1] ?? '';
    }
}

function stockFor(line) {
    return props.products.find((item) => String(item.value) === String(line.product_id))?.stock ?? null;
}

function addLine() {
    form.lines.push(blankLine());
}

function removeLine(index) {
    if (form.lines.length <= 1) return;
    form.lines.splice(index, 1);
}

function submit() {
    form.post('/sales/orders');
}
</script>

<template>
    <Head title="Raise sales order" />

    <AppLayout>
        <PageHeader
            eyebrow="Sales"
            :title="`Raise order ${suggestedNumber}`"
            description="Selling prices are taken from the catalogue. Stock is only released when the order is invoiced."
        >
            <template #actions>
                <Button href="/sales/orders" variant="secondary">
                    <Icon name="arrow-left" :size="16" />
                    Back to orders
                </Button>
            </template>
        </PageHeader>

        <form class="space-y-6" @submit.prevent="submit">
            <Card title="Order details">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field label="Customer" for-id="customer_id" :error="form.errors.customer_id" required>
                        <SelectInput id="customer_id" v-model="form.customer_id" :options="customers" placeholder="Choose a customer" :invalid="!!form.errors.customer_id" />
                    </Field>

                    <Field label="Order date" for-id="ordered_at" :error="form.errors.ordered_at" required>
                        <TextInput id="ordered_at" v-model="form.ordered_at" type="date" :invalid="!!form.errors.ordered_at" required />
                    </Field>

                    <Field label="Notes" for-id="notes" :error="form.errors.notes" class="sm:col-span-2">
                        <TextInput id="notes" v-model="form.notes" :invalid="!!form.errors.notes" placeholder="Delivery instructions, site reference…" />
                    </Field>
                </div>
            </Card>

            <Card title="Lines" description="Prices are net of VAT; 20% is added to the order total.">
                <div class="space-y-3">
                    <div v-for="(line, index) in form.lines" :key="index" class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-12">
                        <div class="sm:col-span-6">
                            <Field label="Product" :for-id="`product-${index}`" :error="form.errors[`lines.${index}.product_id`]">
                                <SelectInput
                                    :id="`product-${index}`"
                                    v-model="line.product_id"
                                    :options="products"
                                    placeholder="Choose a product"
                                    :invalid="!!form.errors[`lines.${index}.product_id`]"
                                    @change="productSelected(line)"
                                />
                            </Field>
                            <p v-if="stockFor(line) !== null" class="mt-1 text-[12px] text-slate-500">{{ stockFor(line) }} in stock</p>
                        </div>

                        <div class="sm:col-span-2">
                            <Field label="Quantity" :for-id="`quantity-${index}`" :error="form.errors[`lines.${index}.quantity`]">
                                <TextInput :id="`quantity-${index}`" v-model="line.quantity" type="number" min="1" :invalid="!!form.errors[`lines.${index}.quantity`]" />
                            </Field>
                        </div>

                        <div class="sm:col-span-2">
                            <Field label="Unit price" :for-id="`price-${index}`" :error="form.errors[`lines.${index}.unit_price`]">
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                                    <TextInput
                                        :id="`price-${index}`"
                                        v-model="line.unit_price"
                                        class="pl-7 text-right tabular-nums"
                                        inputmode="decimal"
                                        :invalid="!!form.errors[`lines.${index}.unit_price`]"
                                    />
                                </div>
                            </Field>
                        </div>

                        <div class="flex items-end justify-end sm:col-span-2">
                            <button
                                type="button"
                                class="rounded-md p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30"
                                :disabled="form.lines.length <= 1"
                                @click="removeLine(index)"
                            >
                                <Icon name="trash" :size="16" />
                                <span class="sr-only">Remove line</span>
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="form.errors.lines" class="field-error mt-3">{{ form.errors.lines }}</p>

                <button type="button" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800" @click="addLine">
                    <Icon name="plus" :size="15" />
                    Add another line
                </button>

                <div class="mt-5 space-y-1 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex justify-end gap-8"><span class="text-slate-500">Subtotal</span><span class="w-24 text-right tabular-nums text-slate-900">{{ money(subtotal) }}</span></div>
                    <div class="flex justify-end gap-8"><span class="text-slate-500">VAT 20%</span><span class="w-24 text-right tabular-nums text-slate-900">{{ money(vat) }}</span></div>
                    <div class="flex justify-end gap-8 font-semibold"><span class="text-slate-700">Total</span><span class="w-24 text-right tabular-nums text-slate-900">{{ money(total) }}</span></div>
                </div>
            </Card>

            <div class="flex items-center justify-end gap-2">
                <Link href="/sales/orders" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</Link>
                <Button type="submit" :loading="form.processing">
                    <Icon name="check" :size="16" />
                    Raise order
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
