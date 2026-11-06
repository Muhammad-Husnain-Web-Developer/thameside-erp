<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import EmployeeForm from './Partials/EmployeeForm.vue';

const props = defineProps({
    employee: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
    contractTypes: { type: Object, default: () => ({}) },
});

const form = useForm({
    employee_number: props.employee.employee_number,
    first_name: props.employee.first_name,
    last_name: props.employee.last_name,
    email: props.employee.email ?? '',
    phone: props.employee.phone ?? '',
    department_id: props.employee.department_id ?? '',
    job_title: props.employee.job_title,
    contract_type: props.employee.contract_type,
    started_on: props.employee.started_on,
    ended_on: props.employee.ended_on ?? '',
    annual_salary: props.employee.annual_salary,
    payroll_reference: props.employee.payroll_reference ?? '',
    is_active: props.employee.is_active,
});

function submit() {
    form.put(`/people/employees/${props.employee.id}`);
}
</script>

<template>
    <Head :title="`Edit ${employee.first_name} ${employee.last_name}`" />

    <AppLayout>
        <PageHeader
            eyebrow="People"
            :title="`${employee.first_name} ${employee.last_name}`"
            :description="`${employee.job_title} · ${employee.employee_number}`"
        />

        <EmployeeForm
            :form="form"
            :departments="departments"
            :contract-types="contractTypes"
            submit-label="Save changes"
            @submit="submit"
        />
    </AppLayout>
</template>
