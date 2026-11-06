<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import Field from '../../../Components/Field.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import SelectInput from '../../../Components/SelectInput.vue';
import TextInput from '../../../Components/TextInput.vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    today: { type: String, required: true },
});

function blankLine() {
    return { nominal_account_id: '', description: '', debit: '', credit: '' };
}

const form = useForm({
    date: props.today,
    description: '',
    lines: [blankLine(), blankLine()],
});

function addLine() {
    form.lines.push(blankLine());
}

function removeLine(index) {
    if (form.lines.length <= 2) {
        return;
    }

    form.lines.splice(index, 1);
}

const toPence = (value) => Math.round((parseFloat(value) || 0) * 100);

const totalDebit = computed(() => form.lines.reduce((sum, line) => sum + toPence(line.debit), 0));
const totalCredit = computed(() => form.lines.reduce((sum, line) => sum + toPence(line.credit), 0));
const difference = computed(() => totalDebit.value - totalCredit.value);

const money = (pence) =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(pence / 100);

const canPost = computed(
    () => form.description.trim() !== '' && totalDebit.value > 0 && difference.value === 0,
);

function submit() {
    form.post('/accounting/journals', { preserveScroll: true });
}

function error(key) {
    return form.errors[key] ?? null;
}
</script>

<template>
    <Head title="Post journal" />

    <AppLayout>
        <PageHeader
            eyebrow="Accounting"
            title="Post a journal"
            description="Use a manual journal for opening balances, accruals and corrections. Debits must equal credits before it can be posted."
        >
            <template #actions>
                <Button href="/accounting/journals" variant="secondary">
                    <Icon name="arrow-left" :size="16" />
                    Back to ledger
                </Button>
            </template>
        </PageHeader>

        <form class="space-y-6" @submit.prevent="submit">
            <Card title="Journal details">
                <div class="grid gap-4 sm:grid-cols-3">
                    <Field label="Date" for-id="date" :error="error('date')" required>
                        <TextInput id="date" v-model="form.date" type="date" :invalid="!!error('date')" required />
                    </Field>

                    <Field label="Description" for-id="description" :error="error('description')" class="sm:col-span-2" required>
                        <TextInput
                            id="description"
                            v-model="form.description"
                            :invalid="!!error('description')"
                            placeholder="e.g. Opening balances brought forward"
                            required
                        />
                    </Field>
                </div>
            </Card>

            <Card title="Journal lines" description="Each line hits one nominal account. Enter a debit or a credit — never both.">
                <div class="space-y-3">
                    <div
                        v-for="(line, index) in form.lines"
                        :key="index"
                        class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-12"
                    >
                        <div class="sm:col-span-4">
                            <Field label="Account" :for-id="`account-${index}`" :error="error(`lines.${index}.nominal_account_id`)">
                                <SelectInput
                                    :id="`account-${index}`"
                                    v-model="line.nominal_account_id"
                                    :options="accounts"
                                    placeholder="Choose an account"
                                    :invalid="!!error(`lines.${index}.nominal_account_id`)"
                                />
                            </Field>
                        </div>

                        <div class="sm:col-span-3">
                            <Field label="Narrative" :for-id="`narrative-${index}`" :error="error(`lines.${index}.description`)">
                                <TextInput :id="`narrative-${index}`" v-model="line.description" placeholder="Optional" />
                            </Field>
                        </div>

                        <div class="sm:col-span-2">
                            <Field label="Debit" :for-id="`debit-${index}`" :error="error(`lines.${index}.debit`)">
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                                    <TextInput
                                        :id="`debit-${index}`"
                                        v-model="line.debit"
                                        class="pl-7 text-right tabular-nums"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                    />
                                </div>
                            </Field>
                        </div>

                        <div class="sm:col-span-2">
                            <Field label="Credit" :for-id="`credit-${index}`" :error="error(`lines.${index}.credit`)">
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">£</span>
                                    <TextInput
                                        :id="`credit-${index}`"
                                        v-model="line.credit"
                                        class="pl-7 text-right tabular-nums"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                    />
                                </div>
                            </Field>
                        </div>

                        <div class="flex items-end justify-end sm:col-span-1">
                            <button
                                type="button"
                                class="rounded-md p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30"
                                :disabled="form.lines.length <= 2"
                                title="Remove line"
                                @click="removeLine(index)"
                            >
                                <Icon name="trash" :size="16" />
                                <span class="sr-only">Remove line</span>
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="error('lines')" class="field-error mt-3">{{ error('lines') }}</p>

                <div class="mt-4">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800"
                        @click="addLine"
                    >
                        <Icon name="plus" :size="15" />
                        Add another line
                    </button>
                </div>

                <div class="mt-5 flex flex-col gap-2 border-t border-slate-100 pt-4 text-sm sm:flex-row sm:justify-end sm:gap-8">
                    <p class="text-slate-500">Debits <span class="font-semibold tabular-nums text-slate-900">{{ money(totalDebit) }}</span></p>
                    <p class="text-slate-500">Credits <span class="font-semibold tabular-nums text-slate-900">{{ money(totalCredit) }}</span></p>
                    <p :class="difference === 0 ? 'text-brand-700' : 'text-rose-600'">
                        {{ difference === 0 ? 'Balanced' : `Out by ${money(Math.abs(difference))}` }}
                    </p>
                </div>
            </Card>

            <div class="flex items-center justify-end gap-2">
                <Link href="/accounting/journals" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</Link>
                <Button type="submit" :loading="form.processing" :disabled="!canPost">
                    <Icon name="check" :size="16" />
                    Post journal
                </Button>
            </div>
        </form>
    </AppLayout>
</template>
