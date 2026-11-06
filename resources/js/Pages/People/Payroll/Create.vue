<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import Field from '../../../Components/Field.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import TextInput from '../../../Components/TextInput.vue';

const props = defineProps({
    month: { type: String, required: true },
    employees: { type: Array, default: () => [] },
    estimatedGross: { type: String, default: '£0.00' },
});

const form = useForm({
    month: props.month,
    pay_date: '',
});

function submit() {
    form.post('/people/payroll');
}
</script>

<template>
    <Head title="Run payroll" />

    <AppLayout>
        <PageHeader
            eyebrow="People"
            title="Run payroll"
            description="Payroll calculates PAYE at 20% and NI at 8% above the monthly allowance, plus a 5% pension contribution."
        >
            <template #actions>
                <Button href="/people/payroll" variant="secondary">
                    <Icon name="arrow-left" :size="16" />
                    Back to payroll
                </Button>
            </template>
        </PageHeader>

        <form class="space-y-6" @submit.prevent="submit">
            <Card title="Pay period">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field label="Month" for-id="month" :error="form.errors.month" hint="Payroll can only be run once per month." required>
                        <TextInput id="month" v-model="form.month" type="month" :invalid="!!form.errors.month" required />
                    </Field>

                    <Field label="Pay date" for-id="pay_date" :error="form.errors.pay_date" hint="Defaults to the last day of the month.">
                        <TextInput id="pay_date" v-model="form.pay_date" type="date" />
                    </Field>
                </div>
            </Card>

            <Card :padded="false">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    <p class="text-[13px] font-semibold text-slate-900">Employees in this run</p>
                    <p class="text-[13px] text-slate-500">Estimated gross <span class="font-semibold text-slate-900">{{ estimatedGross }}</span></p>
                </div>

                <ul class="divide-y divide-slate-100">
                    <li v-for="employee in employees" :key="employee.name" class="flex items-center justify-between gap-3 px-5 py-2.5">
                        <div>
                            <p class="text-[13px] font-medium text-slate-900">{{ employee.name }}</p>
                            <p class="text-[12px] text-slate-500">{{ employee.department ?? 'No department' }}</p>
                        </div>
                        <p class="text-[13px] tabular-nums text-slate-700">{{ employee.gross }}</p>
                    </li>
                </ul>
            </Card>

            <div class="flex items-center justify-end gap-2">
                <Link href="/people/payroll" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</Link>
                <Button type="submit" :loading="form.processing">
                    <Icon name="check" :size="16" />
                    Run payroll
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
