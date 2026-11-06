<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Button from '../../../../Components/Button.vue';
import Card from '../../../../Components/Card.vue';
import Field from '../../../../Components/Field.vue';
import Icon from '../../../../Components/Icon.vue';
import TextInput from '../../../../Components/TextInput.vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    staff: { type: Object, default: null },
});

const isEdit = computed(() => !!props.staff);

const form = useForm({
    name: props.staff?.name ?? '',
    email: props.staff?.email ?? '',
    job_title: props.staff?.job_title ?? '',
    phone: props.staff?.phone ?? '',
    password: '',
    password_confirmation: '',
    roles: props.staff?.roles ?? [],
    is_active: props.staff?.is_active ?? true,
});

const roleDescriptions = {
    admin: 'Full access including staff accounts and the audit trail.',
    accountant: 'Ledger, journals and financial reporting.',
    sales: 'Customers, quotes, sales orders and invoices.',
    warehouse: 'Goods in, stock movements and dispatch.',
};

function toggleRole(role) {
    form.roles = form.roles.includes(role)
        ? form.roles.filter((item) => item !== role)
        : [...form.roles, role];
}

function submit() {
    if (isEdit.value) {
        form.put(`/settings/users/${props.staff.id}`, { preserveScroll: true });
        return;
    }

    form.post('/settings/users');
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <Card title="Personal details" description="How this person appears across the ERP.">
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="Full name" for-id="name" :error="form.errors.name" required>
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" autocomplete="name" required />
                </Field>

                <Field label="Work email" for-id="email" :error="form.errors.email" required>
                    <TextInput id="email" v-model="form.email" type="email" :invalid="!!form.errors.email" autocomplete="email" required />
                </Field>

                <Field label="Job title" for-id="job_title" :error="form.errors.job_title">
                    <TextInput id="job_title" v-model="form.job_title" :invalid="!!form.errors.job_title" placeholder="e.g. Sales Manager" />
                </Field>

                <Field label="Phone" for-id="phone" :error="form.errors.phone">
                    <TextInput id="phone" v-model="form.phone" :invalid="!!form.errors.phone" placeholder="+44 7700 900000" />
                </Field>
            </div>
        </Card>

        <Card title="Role" description="Roles decide which parts of the ERP this person can use.">
            <p v-if="form.errors.roles" class="field-error mb-3">{{ form.errors.roles }}</p>

            <div class="grid gap-2.5 sm:grid-cols-2">
                <button
                    v-for="role in roles"
                    :key="role"
                    type="button"
                    class="flex items-start gap-3 rounded-lg border p-3 text-left transition"
                    :class="form.roles.includes(role)
                        ? 'border-brand-500 bg-brand-50/50 ring-1 ring-brand-500/30'
                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                    @click="toggleRole(role)"
                >
                    <span
                        class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border transition"
                        :class="form.roles.includes(role) ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white'"
                    >
                        <Icon v-if="form.roles.includes(role)" name="check" :size="10" :stroke-width="3" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[13px] font-medium capitalize text-slate-900">{{ role }}</span>
                        <span class="mt-0.5 block text-[12px] leading-snug text-slate-500">{{ roleDescriptions[role] ?? 'ERP access.' }}</span>
                    </span>
                </button>
            </div>
        </Card>

        <Card title="Security" :description="isEdit ? 'Set a new password only if it needs changing.' : 'They can change this after their first sign-in.'">
            <div class="grid gap-4 sm:grid-cols-2">
                <Field label="Password" for-id="password" :error="form.errors.password" :required="!isEdit" :hint="isEdit ? 'Leave blank to keep the current password.' : 'At least 8 characters.'">
                    <TextInput id="password" v-model="form.password" type="password" autocomplete="new-password" :invalid="!!form.errors.password" />
                </Field>

                <Field label="Confirm password" for-id="password_confirmation">
                    <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                </Field>
            </div>

            <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 transition hover:bg-slate-50">
                <input v-model="form.is_active" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30" />
                <span>
                    <span class="block text-[13px] font-medium text-slate-900">Account is active</span>
                    <span class="mt-0.5 block text-[12px] text-slate-500">Deactivated staff keep their history but cannot sign in.</span>
                </span>
            </label>
        </Card>

        <div class="flex items-center justify-end gap-2">
            <Button href="/settings/users" variant="secondary">Cancel</Button>
            <Button type="submit" :loading="form.processing">
                <Icon name="check" :size="15" />
                {{ isEdit ? 'Save changes' : 'Create account' }}
            </Button>
        </div>
    </form>
</template>
