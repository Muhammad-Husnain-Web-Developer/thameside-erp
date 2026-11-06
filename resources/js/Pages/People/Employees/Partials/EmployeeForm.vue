<script setup>
import { Link } from '@inertiajs/vue3';
import Button from '../../../../Components/Button.vue';
import Card from '../../../../Components/Card.vue';
import Field from '../../../../Components/Field.vue';
import Icon from '../../../../Components/Icon.vue';
import SelectInput from '../../../../Components/SelectInput.vue';
import TextInput from '../../../../Components/TextInput.vue';

defineProps({
    form: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
    contractTypes: { type: Object, default: () => ({}) },
    submitLabel: { type: String, default: 'Save employee' },
});

const emit = defineEmits(['submit']);
</script>

<template>
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <Card title="Employee details" description="Payroll uses the annual salary divided by twelve, with PAYE, NI and pension deducted.">
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="First name" for-id="first_name" :error="form.errors.first_name" required>
                    <TextInput id="first_name" v-model="form.first_name" :invalid="!!form.errors.first_name" required />
                </Field>

                <Field label="Last name" for-id="last_name" :error="form.errors.last_name" required>
                    <TextInput id="last_name" v-model="form.last_name" :invalid="!!form.errors.last_name" required />
                </Field>

                <Field label="Employee number" for-id="employee_number" :error="form.errors.employee_number" hint="Leave blank to use the next number.">
                    <TextInput id="employee_number" v-model="form.employee_number" :invalid="!!form.errors.employee_number" />
                </Field>

                <Field label="Payroll reference" for-id="payroll_reference" :error="form.errors.payroll_reference">
                    <TextInput id="payroll_reference" v-model="form.payroll_reference" :invalid="!!form.errors.payroll_reference" />
                </Field>

                <Field label="Job title" for-id="job_title" :error="form.errors.job_title" required>
                    <TextInput id="job_title" v-model="form.job_title" :invalid="!!form.errors.job_title" required />
                </Field>

                <Field label="Department" for-id="department_id" :error="form.errors.department_id">
                    <SelectInput id="department_id" v-model="form.department_id" :options="departments.map((d) => ({ value: d.id, label: d.name }))" placeholder="No department" />
                </Field>

                <Field label="Contract" for-id="contract_type" :error="form.errors.contract_type" required>
                    <SelectInput id="contract_type" v-model="form.contract_type" :options="Object.entries(contractTypes).map(([value, label]) => ({ value, label }))" :invalid="!!form.errors.contract_type" />
                </Field>

                <Field label="Annual salary" for-id="annual_salary" :error="form.errors.annual_salary" required>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                        <TextInput id="annual_salary" v-model="form.annual_salary" class="pl-7 tabular-nums" inputmode="decimal" :invalid="!!form.errors.annual_salary" required />
                    </div>
                </Field>

                <Field label="Started on" for-id="started_on" :error="form.errors.started_on" required>
                    <TextInput id="started_on" v-model="form.started_on" type="date" :invalid="!!form.errors.started_on" required />
                </Field>

                <Field label="Ended on" for-id="ended_on" :error="form.errors.ended_on" hint="Leave blank while employed.">
                    <TextInput id="ended_on" v-model="form.ended_on" type="date" :invalid="!!form.errors.ended_on" />
                </Field>

                <Field label="Email" for-id="email" :error="form.errors.email">
                    <TextInput id="email" v-model="form.email" type="email" :invalid="!!form.errors.email" />
                </Field>

                <Field label="Phone" for-id="phone" :error="form.errors.phone">
                    <TextInput id="phone" v-model="form.phone" :invalid="!!form.errors.phone" />
                </Field>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm text-slate-600">
                <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                Active employee — included in the next payroll run
            </label>
        </Card>

        <div class="flex items-center justify-end gap-3">
            <Link href="/people/employees" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</Link>
            <Button type="submit" :loading="form.processing">
                <Icon name="check" :size="16" />
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
