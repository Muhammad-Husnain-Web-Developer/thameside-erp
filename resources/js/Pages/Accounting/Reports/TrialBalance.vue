<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import Field from '../../../Components/Field.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import TextInput from '../../../Components/TextInput.vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
    balanced: { type: Boolean, default: true },
    filters: { type: Object, default: () => ({}) },
});

const to = ref(props.filters.to ?? '');

function apply() {
    router.get('/accounting/reports/trial-balance', { to: to.value || undefined }, { preserveState: true, replace: true });
}

function clear() {
    to.value = '';
    apply();
}
</script>

<template>
    <Head title="Trial balance" />

    <AppLayout>
        <PageHeader
            eyebrow="Accounting"
            title="Trial balance"
        >
            <template #actions>
                <Badge :tone="balanced ? 'brand' : 'rose'" dot>{{ balanced ? 'Debits equal credits' : 'Out of balance' }}</Badge>
            </template>
        </PageHeader>

        <Card class="mb-6">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
                <div class="w-full sm:w-52">
                    <Field label="As at" for-id="to" hint="Leave empty for the live position.">
                        <TextInput id="to" v-model="to" type="date" />
                    </Field>
                </div>
                <Button type="submit" :disabled="false">
                    <Icon name="filter" :size="15" />
                    Apply
                </Button>
                <button v-if="filters.to" type="button" class="text-sm font-medium text-slate-500 hover:text-slate-700" @click="clear">
                    Clear
                </button>
            </form>
        </Card>

        <Card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Code</th>
                            <th class="table-heading">Account</th>
                            <th class="table-heading hidden md:table-cell">Type</th>
                            <th class="table-heading text-right">Debit</th>
                            <th class="table-heading text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="row in rows" :key="row.code" class="transition hover:bg-slate-50/60">
                            <td class="table-cell whitespace-nowrap font-medium tabular-nums text-slate-900">{{ row.code }}</td>
                            <td class="table-cell">{{ row.name }}</td>
                            <td class="table-cell hidden text-[13px] text-slate-500 md:table-cell">{{ row.type }}</td>
                            <td class="table-cell text-right tabular-nums text-slate-900">{{ row.debit ?? '' }}</td>
                            <td class="table-cell text-right tabular-nums text-slate-900">{{ row.credit ?? '' }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-50/70">
                        <tr>
                            <td class="table-cell font-semibold text-slate-900" colspan="3">Totals</td>
                            <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ totals.debit }}</td>
                            <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ totals.credit }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Card>

        <p class="mt-4 text-[12px] text-slate-500">
            Balances are derived from posted journal lines — there is no separate balance file to reconcile.
        </p>
    </AppLayout>
</template>
