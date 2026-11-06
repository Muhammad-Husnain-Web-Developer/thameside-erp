<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import CustomerForm from './Partials/CustomerForm.vue';

defineProps({ suggestedCode: { type: String, default: '' } });

const form = useForm({
    code: '',
    name: '',
    contact_name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    postcode: '',
    credit_limit: '',
    payment_terms_days: 30,
    notes: '',
    is_active: true,
});

form.transform((data) => ({ ...data, code: data.code || null }));

function submit() {
    form.post('/sales/customers');
}
</script>

<template>
    <Head title="Add customer" />

    <AppLayout>
        <PageHeader
            eyebrow="Sales"
            title="Add a customer"
            description="Trade customers buying from Thameside. Payment terms and credit limits drive invoicing behaviour."
        />

        <CustomerForm :form="form" submit-label="Add customer" @submit="submit" />
    </AppLayout>
</template>
