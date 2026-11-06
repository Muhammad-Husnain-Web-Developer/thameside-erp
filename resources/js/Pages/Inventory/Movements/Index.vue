<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Field from '../../../Components/Field.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import SelectInput from '../../../Components/SelectInput.vue';
import StatCard from '../../../Components/StatCard.vue';
import TextInput from '../../../Components/TextInput.vue';

const props = defineProps({
    movements: { type: Object, required: true },
    products: { type: Array, default: () => [] },
    types: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const type = ref(props.filters.type ?? '');
const product = ref(props.filters.product ?? '');
const showForm = ref(false);

watch([type, product], () => {
    router.get(
        '/inventory/stock',
        { type: type.value || undefined, product: product.value || undefined },
        { preserveState: true, replace: true, preserveScroll: true },
    );
});

const form = useForm({
    product_id: '',
    type: 'adjustment',
    quantity: 1,
    unit_cost: '',
    reference: '',
    note: '',
    serials: '',
});

const selectedProduct = computed(() => props.products.find((item) => String(item.value) === String(form.product_id)));

const tracksSerial = computed(() => !!selectedProduct.value?.tracks_serial);

const typeOptions = computed(() =>
    Object.entries(props.types)
        .filter(([value]) => !tracksSerial.value || value !== 'adjustment')
        .map(([value, label]) => ({ value, label })),
);

const needsSerials = computed(() => tracksSerial.value && ['receipt', 'return', 'sale', 'write_off'].includes(form.type));

watch(tracksSerial, (tracked) => {
    if (tracked && form.type === 'adjustment') {
        form.type = 'receipt';
    }
});

function submit() {
    form.post('/inventory/stock', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('product_id', 'quantity', 'unit_cost', 'reference', 'note', 'serials');
            showForm.value = false;
        },
    });
}

function movementTone(movement) {
    return movement.quantity > 0 ? 'brand' : 'rose';
}
</script>

<template>
    <Head title="Stock ledger" />

    <AppLayout>
        <PageHeader
            eyebrow="Inventory"
            title="Stock ledger"
            description="Every unit that has moved, why it moved and who moved it. Quantities are never edited directly."
        >
            <template #actions>
                <button
                    v-if="can.adjust"
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-brand-700"
                    @click="showForm = !showForm"
                >
                    <Icon :name="showForm ? 'x' : 'plus'" :size="16" />
                    {{ showForm ? 'Close' : 'Record movement' }}
                </button>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="stat in stats"
                :key="stat.key"
                :label="stat.label"
                :value="stat.value"
                :meta="stat.meta"
                :icon="stat.icon"
            />
        </div>

        <div v-if="showForm && can.adjust" class="mb-6">
            <Card title="Record a stock movement" description="Receipts must be positive; sales and write-offs reduce stock.">
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <Field label="Product" for-id="product_id" :error="form.errors.product_id" required class="sm:col-span-2">
                        <SelectInput
                            id="product_id"
                            v-model="form.product_id"
                            :options="products"
                            placeholder="Choose a product"
                            :invalid="!!form.errors.product_id"
                        />
                    </Field>

                    <Field label="Movement type" for-id="type" :error="form.errors.type" required>
                        <SelectInput id="type" v-model="form.type" :options="typeOptions" :invalid="!!form.errors.type" />
                    </Field>

                    <Field
                        label="Quantity"
                        for-id="quantity"
                        :error="form.errors.quantity"
                        :hint="form.type === 'adjustment' ? 'Negative reduces stock.' : 'Positive number of units.'"
                        required
                    >
                        <TextInput id="quantity" v-model="form.quantity" type="number" :invalid="!!form.errors.quantity" required />
                    </Field>

                    <Field v-if="needsSerials" label="Serial numbers" for-id="serials" :error="form.errors.serials" hint="One per line, one per unit." class="sm:col-span-2">
                        <textarea id="serials" v-model="form.serials" rows="3" class="field-input" placeholder="SN-0001&#10;SN-0002" />
                    </Field>

                    <Field label="Unit cost" for-id="unit_cost" :error="form.errors.unit_cost" hint="Optional — defaults to the product cost.">
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                            <TextInput id="unit_cost" v-model="form.unit_cost" class="pl-7" inputmode="decimal" :invalid="!!form.errors.unit_cost" placeholder="0.00" />
                        </div>
                    </Field>

                    <Field label="Reference" for-id="reference" :error="form.errors.reference">
                        <TextInput id="reference" v-model="form.reference" :invalid="!!form.errors.reference" placeholder="PO / GRN / note" />
                    </Field>

                    <Field label="Note" for-id="note" :error="form.errors.note" class="sm:col-span-2">
                        <textarea id="note" v-model="form.note" rows="2" class="field-input" placeholder="Reason for the movement" />
                    </Field>

                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <Button type="button" variant="secondary" @click="showForm = false">Cancel</Button>
                        <Button type="submit" :loading="form.processing">
                            <Icon name="check" :size="15" />
                            Record movement
                        </Button>
                    </div>
                </form>
            </Card>
        </div>

        <Card :padded="false">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3.5">
                <div class="w-full sm:w-56">
                    <SelectInput v-model="product" :options="products" placeholder="All products" />
                </div>

                <div class="w-full sm:w-48">
                    <SelectInput
                        v-model="type"
                        :options="Object.entries(types).map(([value, label]) => ({ value, label }))"
                        placeholder="All movement types"
                    />
                </div>

                <p class="ml-auto text-[12px] text-slate-500">
                    {{ movements.total }} {{ movements.total === 1 ? 'entry' : 'entries' }}
                </p>
            </div>

            <EmptyState
                v-if="movements.data.length === 0"
                icon="history"
                title="No stock movements"
                :description="filters.type || filters.product ? 'Nothing matches those filters.' : 'Record a goods receipt or an adjustment to start the ledger.'"
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">When</th>
                            <th class="table-heading">Product</th>
                            <th class="table-heading">Movement</th>
                            <th class="table-heading text-right">Qty</th>
                            <th class="table-heading hidden text-right md:table-cell">Value</th>
                            <th class="table-heading hidden lg:table-cell">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="movement in movements.data" :key="movement.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell whitespace-nowrap text-[12px] text-slate-500">{{ movement.occurred_at }}</td>
                            <td class="table-cell">
                                <Link v-if="movement.product_id" :href="`/inventory/products/${movement.product_id}`" class="font-medium text-slate-900 hover:text-brand-700">
                                    {{ movement.product }}
                                </Link>
                                <p class="text-[12px] text-slate-500">{{ movement.sku }}</p>
                            </td>
                            <td class="table-cell">
                                <Badge :tone="movementTone(movement)">{{ movement.type_label }}</Badge>
                                <p v-if="movement.reference" class="mt-0.5 truncate text-[12px] text-slate-500">{{ movement.reference }}</p>
                            </td>
                            <td class="table-cell text-right font-semibold tabular-nums" :class="movement.quantity > 0 ? 'text-brand-700' : 'text-rose-600'">
                                {{ movement.signed_quantity }}
                            </td>
                            <td class="table-cell hidden text-right tabular-nums md:table-cell">{{ movement.value }}</td>
                            <td class="table-cell hidden text-[12px] text-slate-500 lg:table-cell">{{ movement.user }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="movements.data.length > 0" :paginator="movements" />
        </Card>
    </AppLayout>
</template>
