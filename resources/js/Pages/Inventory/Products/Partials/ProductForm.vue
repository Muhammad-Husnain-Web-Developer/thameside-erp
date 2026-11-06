<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '../../../../Components/Button.vue';
import Card from '../../../../Components/Card.vue';
import Field from '../../../../Components/Field.vue';
import Icon from '../../../../Components/Icon.vue';
import SelectInput from '../../../../Components/SelectInput.vue';
import TextInput from '../../../../Components/TextInput.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    product: { type: Object, default: null },
    suggestedSku: { type: String, default: '' },
});

const isEdit = computed(() => !!props.product);

const form = useForm({
    sku: props.product?.sku ?? props.suggestedSku ?? '',
    name: props.product?.name ?? '',
    description: props.product?.description ?? '',
    product_category_id: props.product?.product_category_id ?? '',
    brand: props.product?.brand ?? '',
    unit: props.product?.unit ?? 'each',
    cost_price: props.product?.cost_price ?? '',
    sell_price: props.product?.sell_price ?? '',
    reorder_level: props.product?.reorder_level ?? 10,
    tracks_serial: props.product?.tracks_serial ?? false,
    is_active: props.product?.is_active ?? true,
    opening_stock: 0,
    opening_serials: '',
});

const categoryOptions = computed(() =>
    props.categories.map((category) => ({ value: category.id, label: category.name })),
);

const marginPreview = computed(() => {
    const cost = Number.parseFloat(form.cost_price || 0);
    const sell = Number.parseFloat(form.sell_price || 0);

    if (!Number.isFinite(cost) || !Number.isFinite(sell) || sell <= 0) {
        return null;
    }

    return (((sell - cost) / sell) * 100).toFixed(1);
});

const needsOpeningSerials = computed(
    () => !isEdit.value && form.tracks_serial && Number(form.opening_stock) > 0,
);

function submit() {
    if (isEdit.value) {
        form.put(`/inventory/products/${props.product.id}`, { preserveScroll: true });
        return;
    }

    form.post('/inventory/products');
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <Card title="Product details" description="How this line is identified on orders, receipts and invoices.">
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="SKU" for-id="sku" :error="form.errors.sku" required hint="Unique stock keeping unit.">
                    <TextInput id="sku" v-model="form.sku" :invalid="!!form.errors.sku" required />
                </Field>

                <Field label="Product name" for-id="name" :error="form.errors.name" required>
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" placeholder="e.g. Range cooker, 90cm dual fuel" required />
                </Field>

                <Field label="Category" for-id="product_category_id" :error="form.errors.product_category_id">
                    <SelectInput
                        id="product_category_id"
                        v-model="form.product_category_id"
                        :options="categoryOptions"
                        placeholder="Uncategorised"
                        :invalid="!!form.errors.product_category_id"
                    />
                </Field>

                <Field label="Brand" for-id="brand" :error="form.errors.brand">
                    <TextInput id="brand" v-model="form.brand" :invalid="!!form.errors.brand" placeholder="e.g. Beaumont" />
                </Field>

                <Field label="Unit" for-id="unit" :error="form.errors.unit" required>
                    <SelectInput
                        id="unit"
                        v-model="form.unit"
                        :options="['each', 'set', 'pair', 'pack', 'metre']"
                        :invalid="!!form.errors.unit"
                    />
                </Field>

                <Field label="Reorder level" for-id="reorder_level" :error="form.errors.reorder_level" required hint="Flagged as low stock at or below this quantity.">
                    <TextInput id="reorder_level" v-model="form.reorder_level" type="number" min="0" :invalid="!!form.errors.reorder_level" required />
                </Field>
            </div>

            <div class="mt-4">
                <Field label="Description" for-id="description" :error="form.errors.description">
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="field-input"
                        placeholder="Specification, finish, dimensions…"
                    />
                </Field>
            </div>
        </Card>

        <Card title="Pricing" description="Stored as pence. VAT is applied at the rate held on the product.">
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="Cost price" for-id="cost_price" :error="form.errors.cost_price" required hint="What Thameside pays the supplier, ex VAT.">
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                        <TextInput id="cost_price" v-model="form.cost_price" class="pl-7" inputmode="decimal" :invalid="!!form.errors.cost_price" placeholder="0.00" required />
                    </div>
                </Field>

                <Field label="Sell price" for-id="sell_price" :error="form.errors.sell_price" required hint="Trade price to the customer, ex VAT.">
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                        <TextInput id="sell_price" v-model="form.sell_price" class="pl-7" inputmode="decimal" :invalid="!!form.errors.sell_price" placeholder="0.00" required />
                    </div>
                </Field>
            </div>

            <p v-if="marginPreview !== null" class="mt-3 text-[12px] text-slate-500">
                Gross margin <span class="font-semibold text-slate-800">{{ marginPreview }}%</span> · VAT at 20% is added on invoice.
            </p>
        </Card>

        <Card title="Tracking" description="Serial numbers are captured on receipt and again when a unit is sold.">
            <div class="space-y-3">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 transition hover:bg-slate-50">
                    <input v-model="form.tracks_serial" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30" />
                    <span>
                        <span class="block text-[13px] font-medium text-slate-900">Track serial numbers</span>
                        <span class="mt-0.5 block text-[12px] text-slate-500">Every receipt and sale must name the individual units.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 transition hover:bg-slate-50">
                    <input v-model="form.is_active" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30" />
                    <span>
                        <span class="block text-[13px] font-medium text-slate-900">Available to sell</span>
                        <span class="mt-0.5 block text-[12px] text-slate-500">Inactive products stay on historic documents but are hidden from new orders.</span>
                    </span>
                </label>
            </div>
        </Card>

        <Card v-if="!isEdit" title="Opening stock" description="Optional. Recorded as the first movement in the ledger, never as a quantity field.">
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="Units on hand" for-id="opening_stock" :error="form.errors.opening_stock">
                    <TextInput id="opening_stock" v-model="form.opening_stock" type="number" min="0" :invalid="!!form.errors.opening_stock" />
                </Field>

                <Field
                    v-if="needsOpeningSerials"
                    label="Serial numbers"
                    for-id="opening_serials"
                    :error="form.errors.opening_serials ?? form.errors['opening_serials.0']"
                    hint="One per line — one for each unit."
                >
                    <textarea id="opening_serials" v-model="form.opening_serials" rows="3" class="field-input" placeholder="SN-0001&#10;SN-0002" />
                </Field>
            </div>
        </Card>

        <div class="flex items-center justify-end gap-2">
            <Button :href="isEdit ? `/inventory/products/${product.id}` : '/inventory/products'" variant="secondary">Cancel</Button>
            <Button type="submit" :loading="form.processing">
                <Icon name="check" :size="15" />
                {{ isEdit ? 'Save changes' : 'Add product' }}
            </Button>
        </div>
    </form>
</template>
