<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import UserForm from './Partials/UserForm.vue';

defineProps({
    roles: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Invite staff" />

    <AppLayout>
        <PageHeader
            eyebrow="Administration · Staff"
            title="Invite a staff member"
            description="Create an account and assign the role that matches their work."
        />

        <div class="max-w-3xl">
            <UserForm :roles="roles" />
        </div>
    </AppLayout>
</template>
