<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import SupplierForm from './Partials/SupplierForm.vue';

defineProps({
    suggestedCode: { type: String, default: '' },
});

const form = useForm({
    code: '',
    name: '',
    contact_name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    postcode: '',
    vat_number: '',
    payment_terms_days: 30,
    notes: '',
    is_active: true,
});

form.transform((data) => ({ ...data, code: data.code || null }));

function submit() {
    form.post('/purchasing/suppliers');
}
</script>

<template>
    <Head title="Add supplier" />

    <AppLayout>
        <PageHeader
            eyebrow="Purchasing"
            title="Add a supplier"
            description="Suppliers are where Thameside buys its stock. Payment terms decide each bill's due date."
        />

        <SupplierForm :form="form" submit-label="Add supplier" @submit="submit" />
    </AppLayout>
</template>
