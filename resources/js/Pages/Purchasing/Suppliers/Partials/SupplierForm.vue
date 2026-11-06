<script setup>
import { Link } from '@inertiajs/vue3';
import Button from '../../../../Components/Button.vue';
import Card from '../../../../Components/Card.vue';
import Field from '../../../../Components/Field.vue';
import Icon from '../../../../Components/Icon.vue';
import TextInput from '../../../../Components/TextInput.vue';

defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'Save supplier' },
});

const emit = defineEmits(['submit']);

function submit() {
    emit('submit');
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <Card title="Supplier details" description="Trading details as they appear on the supplier's invoices.">
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="Supplier name" for-id="name" :error="form.errors.name" required class="sm:col-span-2">
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" required />
                </Field>

                <Field label="Account code" for-id="code" :error="form.errors.code" hint="Leave blank to use the next code.">
                    <TextInput id="code" v-model="form.code" :invalid="!!form.errors.code" />
                </Field>

                <Field label="VAT number" for-id="vat_number" :error="form.errors.vat_number">
                    <TextInput id="vat_number" v-model="form.vat_number" :invalid="!!form.errors.vat_number" placeholder="GB 123 4567 89" />
                </Field>

                <Field label="Contact name" for-id="contact_name" :error="form.errors.contact_name">
                    <TextInput id="contact_name" v-model="form.contact_name" :invalid="!!form.errors.contact_name" />
                </Field>

                <Field label="Email" for-id="email" :error="form.errors.email">
                    <TextInput id="email" v-model="form.email" type="email" :invalid="!!form.errors.email" />
                </Field>

                <Field label="Phone" for-id="phone" :error="form.errors.phone">
                    <TextInput id="phone" v-model="form.phone" :invalid="!!form.errors.phone" />
                </Field>

                <Field label="Payment terms" for-id="payment_terms_days" :error="form.errors.payment_terms_days" hint="Days from bill date to due date." required>
                    <TextInput id="payment_terms_days" v-model="form.payment_terms_days" type="number" min="0" max="120" :invalid="!!form.errors.payment_terms_days" required />
                </Field>

                <Field label="Address" for-id="address" :error="form.errors.address" class="sm:col-span-2">
                    <TextInput id="address" v-model="form.address" :invalid="!!form.errors.address" />
                </Field>

                <Field label="City" for-id="city" :error="form.errors.city">
                    <TextInput id="city" v-model="form.city" :invalid="!!form.errors.city" />
                </Field>

                <Field label="Postcode" for-id="postcode" :error="form.errors.postcode">
                    <TextInput id="postcode" v-model="form.postcode" :invalid="!!form.errors.postcode" />
                </Field>

                <Field label="Notes" for-id="notes" :error="form.errors.notes" class="sm:col-span-2">
                    <textarea id="notes" v-model="form.notes" rows="3" class="field-input" />
                </Field>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm text-slate-600">
                <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                Active supplier — available for new purchase orders
            </label>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Link href="/purchasing/suppliers" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</Link>
            <Button type="submit" :loading="form.processing">
                <Icon name="check" :size="16" />
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
