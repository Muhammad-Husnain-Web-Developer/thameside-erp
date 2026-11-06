<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import EmployeeForm from './Partials/EmployeeForm.vue';

const props = defineProps({
    departments: { type: Array, default: () => [] },
    contractTypes: { type: Object, default: () => ({}) },
    today: { type: String, required: true },
    suggestedNumber: { type: String, default: '' },
});

const form = useForm({
    employee_number: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    department_id: '',
    job_title: '',
    contract_type: 'full_time',
    started_on: props.today,
    ended_on: '',
    annual_salary: '',
    payroll_reference: '',
    is_active: true,
});

form.transform((data) => ({ ...data, employee_number: data.employee_number || null }));

function submit() {
    form.post('/people/employees');
}
</script>

<template>
    <Head title="Add employee" />

    <AppLayout>
        <PageHeader
            eyebrow="People"
            title="Add an employee"
            description="Employees on the payroll are included in monthly runs from their start date."
        />

        <EmployeeForm
            :form="form"
            :departments="departments"
            :contract-types="contractTypes"
            submit-label="Add employee"
            @submit="submit"
        />
    </AppLayout>
</template>
