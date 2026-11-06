<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Field from '../../../Components/Field.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import SelectInput from '../../../Components/SelectInput.vue';
import StatCard from '../../../Components/StatCard.vue';
import TextInput from '../../../Components/TextInput.vue';

const props = defineProps({
    product: { type: Object, required: true },
    stock: { type: Object, required: true },
    movements: { type: Array, default: () => [] },
    serials: { type: Array, default: () => [] },
    movementTypes: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const form = useForm({
    product_id: props.product.id,
    type: props.product.tracks_serial ? 'receipt' : 'adjustment',
    quantity: 1,
    unit_cost: '',
    reference: '',
    note: '',
    serials: '',
});

const typeOptions = computed(() =>
    Object.entries(props.movementTypes)
        .filter(([value]) => !props.product.tracks_serial || value !== 'adjustment')
        .map(([value, label]) => ({ value, label })),
);

const needsSerials = computed(() => props.product.tracks_serial && form.type !== 'adjustment');

const stats = computed(() => [
    {
        key: 'on-hand',
        label: 'On hand',
        value: props.stock.on_hand,
        meta: props.stock.is_low ? `At or below reorder level of ${props.stock.reorder_level}` : `Reorder level ${props.stock.reorder_level}`,
        icon: 'box',
    },
    {
        key: 'value',
        label: 'Stock at cost',
        value: props.stock.value,
        meta: 'Cost price × quantity on hand',
        icon: 'banknotes',
    },
    {
        key: 'retail',
        label: 'Retail value',
        value: props.stock.retail_value,
        meta: 'Sell price × quantity on hand',
        icon: 'chart',
    },
    {
        key: props.product.tracks_serial ? 'serials' : 'margin',
        label: props.product.tracks_serial ? 'Serials in stock' : 'Gross margin',
        value: props.product.tracks_serial ? props.stock.serials_in_stock : `${product.margin}%`,
        meta: props.product.tracks_serial ? 'Units available to sell' : 'Sell price against cost',
        icon: props.product.tracks_serial ? 'identification' : 'chart',
    },
]);

function submitMovement() {
    form.post('/inventory/stock', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('quantity', 'reference', 'note', 'serials', 'unit_cost');
        },
    });
}

function serialTone(status) {
    if (status === 'in_stock') {
        return 'brand';
    }

    return status === 'sold' ? 'slate' : 'amber';
}

function serialLabel(status) {
    return status === 'in_stock' ? 'In stock' : status === 'sold' ? 'Sold' : 'Written off';
}
</script>

<template>
    <Head :title="product.name" />

    <AppLayout>
        <PageHeader
            eyebrow="Inventory · Product"
            :title="product.name"
            :description="[product.sku, product.brand, product.category].filter(Boolean).join(' · ')"
        >
            <template #actions>
                <Button href="/inventory/products" variant="secondary">Catalogue</Button>
                <Button v-if="can.update" :href="`/inventory/products/${product.id}/edit`">
                    <Icon name="pencil" :size="15" />
                    Edit product
                </Button>
            </template>
        </PageHeader>

        <div class="mb-6 flex flex-wrap items-center gap-2">
            <Badge :tone="stock.on_hand <= 0 ? 'rose' : stock.is_low ? 'amber' : 'brand'" dot>
                {{ stock.on_hand <= 0 ? 'Out of stock' : stock.is_low ? 'Low stock' : 'In stock' }}
            </Badge>
            <Badge v-if="product.tracks_serial" tone="violet">Serial tracked</Badge>
            <Badge v-if="!product.is_active" tone="slate">Not for sale</Badge>
            <Badge tone="slate">VAT {{ product.vat_rate }}%</Badge>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="(stat, index) in stats"
                :key="stat.key"
                :label="stat.label"
                :value="stat.value"
                :meta="stat.meta"
                :icon="stat.icon"
                :tone="index === 0 && stock.is_low ? 'amber' : 'brand'"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <Card title="Details" description="Pricing is held in pence; VAT is applied when an invoice is raised.">
                    <dl class="grid gap-x-8 gap-y-4 text-[13px] sm:grid-cols-2">
                        <div>
                            <dt class="text-slate-500">SKU</dt>
                            <dd class="mt-0.5 font-medium text-slate-900">{{ product.sku }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Unit</dt>
                            <dd class="mt-0.5 font-medium capitalize text-slate-900">{{ product.unit }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Cost price</dt>
                            <dd class="mt-0.5 font-medium tabular-nums text-slate-900">{{ product.cost_price }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Sell price</dt>
                            <dd class="mt-0.5 font-medium tabular-nums text-slate-900">{{ product.sell_price }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500">Description</dt>
                            <dd class="mt-0.5 leading-relaxed text-slate-700">{{ product.description || 'No description recorded.' }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card title="Stock ledger" description="Permanent record of every movement for this product." :padded="false">
                    <EmptyState
                        v-if="movements.length === 0"
                        icon="history"
                        title="No movements yet"
                        description="Receive or adjust stock to open the ledger for this product."
                    />

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50/70">
                                <tr>
                                    <th class="table-heading">When</th>
                                    <th class="table-heading">Movement</th>
                                    <th class="table-heading text-right">Qty</th>
                                    <th class="table-heading hidden text-right sm:table-cell">Value</th>
                                    <th class="table-heading hidden lg:table-cell">By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="movement in movements" :key="movement.id">
                                    <td class="table-cell whitespace-nowrap text-[12px] text-slate-500">{{ movement.occurred_at }}</td>
                                    <td class="table-cell">
                                        <p class="font-medium text-slate-900">{{ movement.type_label }}</p>
                                        <p v-if="movement.reference || movement.note" class="truncate text-[12px] text-slate-500">
                                            {{ movement.reference || movement.note }}
                                        </p>
                                    </td>
                                    <td class="table-cell text-right">
                                        <span
                                            class="font-semibold tabular-nums"
                                            :class="movement.quantity > 0 ? 'text-brand-700' : 'text-rose-600'"
                                        >
                                            {{ movement.signed_quantity }}
                                        </span>
                                    </td>
                                    <td class="table-cell hidden text-right tabular-nums sm:table-cell">{{ movement.value }}</td>
                                    <td class="table-cell hidden text-[12px] text-slate-500 lg:table-cell">{{ movement.user ?? 'System' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card v-if="product.tracks_serial" title="Serial register" description="Each unit is tracked from receipt to sale." :padded="false">
                    <EmptyState
                        v-if="serials.length === 0"
                        icon="identification"
                        title="No serial numbers yet"
                        description="Receiving stock for this product requires a serial number per unit."
                    />

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50/70">
                                <tr>
                                    <th class="table-heading">Serial</th>
                                    <th class="table-heading">Status</th>
                                    <th class="table-heading hidden sm:table-cell">Received</th>
                                    <th class="table-heading hidden sm:table-cell">Sold</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="serial in serials" :key="serial.id">
                                    <td class="table-cell font-medium text-slate-900">{{ serial.serial_number }}</td>
                                    <td class="table-cell">
                                        <Badge :tone="serialTone(serial.status)" dot>{{ serialLabel(serial.status) }}</Badge>
                                    </td>
                                    <td class="table-cell hidden text-[12px] text-slate-500 sm:table-cell">{{ serial.received_at ?? '—' }}</td>
                                    <td class="table-cell hidden text-[12px] text-slate-500 sm:table-cell">{{ serial.sold_at ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <div class="space-y-6">
                <Card v-if="can.adjust" title="Record a movement" description="Stock only ever changes through this form.">
                    <form class="space-y-4" @submit.prevent="submitMovement">
                        <Field label="Movement type" for-id="type" :error="form.errors.type" required>
                            <SelectInput id="type" v-model="form.type" :options="typeOptions" :invalid="!!form.errors.type" />
                        </Field>

                        <Field
                            label="Quantity"
                            for-id="quantity"
                            :error="form.errors.quantity"
                            :hint="form.type === 'adjustment' ? 'Use a negative number to reduce stock.' : 'Units in this movement.'"
                            required
                        >
                            <TextInput id="quantity" v-model="form.quantity" type="number" :invalid="!!form.errors.quantity" required />
                        </Field>

                        <Field v-if="needsSerials" label="Serial numbers" for-id="serials" :error="form.errors.serials" hint="One per line — one for each unit.">
                            <textarea id="serials" v-model="form.serials" rows="3" class="field-input" placeholder="SN-0001&#10;SN-0002" />
                        </Field>

                        <Field label="Reference" for-id="reference" :error="form.errors.reference">
                            <TextInput id="reference" v-model="form.reference" :invalid="!!form.errors.reference" placeholder="PO, GRN or note reference" />
                        </Field>

                        <Field label="Reason / note" for-id="note" :error="form.errors.note">
                            <textarea id="note" v-model="form.note" rows="2" class="field-input" placeholder="Why did stock move?" />
                        </Field>

                        <Button type="submit" class="w-full" :loading="form.processing">
                            <Icon name="check" :size="15" />
                            Record movement
                        </Button>
                    </form>
                </Card>

                <Card title="At a glance">
                    <dl class="space-y-3 text-[13px]">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">On hand</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">{{ stock.on_hand }} {{ product.unit }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Reorder level</dt>
                            <dd class="font-medium tabular-nums text-slate-900">{{ stock.reorder_level }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Stock value</dt>
                            <dd class="font-medium tabular-nums text-slate-900">{{ stock.value }}</dd>
                        </div>
                        <div v-if="product.tracks_serial" class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Serials in stock</dt>
                            <dd class="font-medium tabular-nums text-slate-900">{{ stock.serials_in_stock }}</dd>
                        </div>
                    </dl>

                    <Link
                        href="/inventory/stock"
                        class="mt-4 inline-flex items-center gap-1.5 text-[13px] font-medium text-brand-700 hover:text-brand-800"
                    >
                        Open the full ledger
                        <Icon name="arrow-right" :size="14" />
                    </Link>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
