<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import CustomerForm from './Partials/CustomerForm.vue';

const props = defineProps({ customer: { type: Object, required: true } });

const form = useForm({
    code: props.customer.code,
    name: props.customer.name,
    contact_name: props.customer.contact_name ?? '',
    email: props.customer.email ?? '',
    phone: props.customer.phone ?? '',
    address: props.customer.address ?? '',
    city: props.customer.city ?? '',
    postcode: props.customer.postcode ?? '',
    credit_limit: props.customer.credit_limit,
    payment_terms_days: props.customer.payment_terms_days,
    notes: props.customer.notes ?? '',
    is_active: props.customer.is_active,
});

function submit() {
    form.put(`/sales/customers/${props.customer.id}`);
}
</script>

<template>
    <Head :title="`Edit ${customer.name}`" />

    <AppLayout>
        <PageHeader
            eyebrow="Sales"
            :title="customer.name"
            :description="`Account ${customer.code}. Credit limit and terms apply to future orders and invoices.`"
        />

        <CustomerForm :form="form" submit-label="Save changes" @submit="submit" />
    </AppLayout>
</template>
