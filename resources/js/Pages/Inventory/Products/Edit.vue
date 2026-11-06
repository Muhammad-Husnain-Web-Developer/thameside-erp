<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import ProductForm from './Partials/ProductForm.vue';

defineProps({
    product: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <AppLayout>
        <PageHeader
            eyebrow="Inventory · Catalogue"
            :title="`Edit ${product.name}`"
            :description="`SKU ${product.sku}. Price changes apply to new orders only.`"
        />

        <div class="max-w-4xl">
            <ProductForm :categories="categories" :product="product" />
        </div>
    </AppLayout>
</template>
