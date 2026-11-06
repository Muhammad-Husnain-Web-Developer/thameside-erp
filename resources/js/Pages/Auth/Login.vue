<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../Layouts/AuthLayout.vue';
import Button from '../../Components/Button.vue';
import Field from '../../Components/Field.vue';
import Icon from '../../Components/Icon.vue';
import TextInput from '../../Components/TextInput.vue';

defineProps({
    status: { type: String, default: null },
    demoAccounts: { type: Array, default: () => [] },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}

function fillDemo(email) {
    form.email = email;
    form.password = 'password';
    form.clearErrors();
}
</script>

<template>
    <Head title="Sign in" />

    <AuthLayout>
        <div class="mb-8 flex items-center gap-3 lg:hidden">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" aria-hidden="true">
                    <path d="M4 6.5h16M12 6.5V19" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                </svg>
            </span>
            <div class="leading-tight">
                <p class="text-[15px] font-semibold text-slate-900">Thameside</p>
                <p class="text-[9px] font-medium uppercase tracking-[0.22em] text-slate-400">Kitchen Supply</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-card sm:p-7">
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Sign in to the ERP</h1>
            <p class="mt-1 text-[13px] text-slate-500">Use your Thameside staff account.</p>

            <div v-if="status" class="mt-4 rounded-lg bg-brand-50 px-3 py-2 text-[13px] font-medium text-brand-700">
                {{ status }}
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <Field label="Work email" for-id="email" :error="form.errors.email">
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        placeholder="you@thamesidekitchen.co.uk"
                        :invalid="!!form.errors.email"
                        autofocus
                        required
                    />
                </Field>

                <Field label="Password" for-id="password" :error="form.errors.password">
                    <div class="relative">
                        <TextInput
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            :invalid="!!form.errors.password"
                            class="pr-10"
                            required
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600"
                            @click="showPassword = !showPassword"
                        >
                            <Icon :name="showPassword ? 'lock' : 'key'" :size="16" />
                            <span class="sr-only">{{ showPassword ? 'Hide password' : 'Show password' }}</span>
                        </button>
                    </div>
                </Field>

                <label class="flex items-center gap-2 text-[13px] text-slate-600">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
                    />
                    Keep me signed in
                </label>

                <Button type="submit" class="w-full" :loading="form.processing">
                    {{ form.processing ? 'Signing in…' : 'Sign in' }}
                </Button>
            </form>
        </div>

        <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white/60 p-4">
            <p class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                <Icon name="identification" :size="13" />
                Demo accounts · password “password”
            </p>
            <div class="grid gap-1">
                <button
                    v-for="account in demoAccounts"
                    :key="account.email"
                    type="button"
                    class="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 text-left text-[12px] transition hover:bg-slate-100"
                    @click="fillDemo(account.email)"
                >
                    <span class="font-medium text-slate-700">{{ account.role }}</span>
                    <span class="truncate text-slate-400">{{ account.email }}</span>
                </button>
            </div>
        </div>

        <p class="mt-5 text-center text-[11px] text-slate-400">
            Accounts are provisioned by an administrator. Contact Amelia Hart for access.
        </p>
    </AuthLayout>
</template>
