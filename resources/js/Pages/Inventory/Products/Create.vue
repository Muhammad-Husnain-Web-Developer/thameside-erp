<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import ProductForm from './Partials/ProductForm.vue';

defineProps({
    categories: { type: Array, default: () => [] },
    suggestedSku: { type: String, default: '' },
});
</script>

<template>
    <Head title="New product" />

    <AppLayout>
        <PageHeader
            eyebrow="Inventory · Catalogue"
            title="Add a product"
            description="Create the catalogue line, then receive stock into the ledger."
        />

        <div class="max-w-4xl">
            <ProductForm :categories="categories" :suggested-sku="suggestedSku" />
        </div>
    </AppLayout>
</template>
