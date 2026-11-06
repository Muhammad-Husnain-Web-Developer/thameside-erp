<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import UserForm from './Partials/UserForm.vue';

defineProps({
    staff: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="`Edit ${staff.name}`" />

    <AppLayout>
        <PageHeader
            eyebrow="Administration · Staff"
            :title="staff.name"
            :description="staff.email"
        >
            <template #actions>
                <Badge :tone="staff.is_active ? 'brand' : 'slate'" dot>
                    {{ staff.is_active ? 'Active' : 'Deactivated' }}
                </Badge>
            </template>
        </PageHeader>

        <div class="max-w-3xl">
            <UserForm :roles="roles" :staff="staff" />
        </div>
    </AppLayout>
</template>
