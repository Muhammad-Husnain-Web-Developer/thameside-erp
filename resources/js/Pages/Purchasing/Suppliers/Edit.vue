<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import SupplierForm from './Partials/SupplierForm.vue';

const props = defineProps({
    supplier: { type: Object, required: true },
});

const form = useForm({
    code: props.supplier.code,
    name: props.supplier.name,
    contact_name: props.supplier.contact_name ?? '',
    email: props.supplier.email ?? '',
    phone: props.supplier.phone ?? '',
    address: props.supplier.address ?? '',
    city: props.supplier.city ?? '',
    postcode: props.supplier.postcode ?? '',
    vat_number: props.supplier.vat_number ?? '',
    payment_terms_days: props.supplier.payment_terms_days,
    notes: props.supplier.notes ?? '',
    is_active: props.supplier.is_active,
});

function submit() {
    form.put(`/purchasing/suppliers/${props.supplier.id}`);
}
</script>

<template>
    <Head :title="`Edit ${supplier.name}`" />

    <AppLayout>
        <PageHeader
            eyebrow="Purchasing"
            :title="supplier.name"
            :description="`Account ${supplier.code}. Changes apply to future orders; posted bills keep their original values.`"
        />

        <SupplierForm :form="form" submit-label="Save changes" @submit="submit" />
    </AppLayout>
</template>
