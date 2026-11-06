<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';

defineProps({
    entry: { type: Object, required: true },
    lines: { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="entry.number" />

    <AppLayout>
        <PageHeader eyebrow="Accounting" :title="entry.number" :description="entry.description">
            <template #actions>
                <Button href="/accounting/journals" variant="secondary">
                    <Icon name="arrow-left" :size="16" />
                    Back to ledger
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Card :padded="false">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <p class="text-[13px] font-semibold text-slate-900">Journal lines</p>
                            <p class="text-[12px] text-slate-500">Posted {{ entry.posted_at }} by {{ entry.author }}</p>
                        </div>
                        <Badge :tone="entry.balanced ? 'brand' : 'rose'" dot>
                            {{ entry.balanced ? 'Balanced' : 'Not balanced' }}
                        </Badge>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50/70">
                                <tr>
                                    <th class="table-heading">Account</th>
                                    <th class="table-heading hidden md:table-cell">Narrative</th>
                                    <th class="table-heading text-right">Debit</th>
                                    <th class="table-heading text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="line in lines" :key="line.id">
                                    <td class="table-cell">
                                        <p class="font-medium text-slate-900">{{ line.account }}</p>
                                        <p class="text-[12px] text-slate-500">{{ line.code }} · {{ line.type }}</p>
                                    </td>
                                    <td class="table-cell hidden text-[13px] text-slate-500 md:table-cell">{{ line.description ?? '—' }}</td>
                                    <td class="table-cell text-right tabular-nums text-slate-900">{{ line.debit ?? '' }}</td>
                                    <td class="table-cell text-right tabular-nums text-slate-900">{{ line.credit ?? '' }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50/70">
                                <tr>
                                    <td class="table-cell font-semibold text-slate-900" colspan="2">Total</td>
                                    <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ entry.total }}</td>
                                    <td class="table-cell text-right font-semibold tabular-nums text-slate-900">{{ entry.total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Entry details">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Date</dt>
                            <dd class="font-medium text-slate-900">{{ entry.date }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Source</dt>
                            <dd class="font-medium text-slate-900">{{ entry.source }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Lines</dt>
                            <dd class="font-medium text-slate-900">{{ lines.length }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Posted</dt>
                            <dd class="font-medium text-slate-900">{{ entry.posted_at }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card title="Where this feeds through">
                    <p class="text-[13px] leading-relaxed text-slate-500">
                        These lines roll up into the
                        <Link href="/accounting/reports/trial-balance" class="font-medium text-brand-700 hover:text-brand-800">trial balance</Link>
                        and, for VAT accounts, the
                        <Link href="/accounting/reports/vat-return" class="font-medium text-brand-700 hover:text-brand-800">VAT return</Link>.
                        Posted journals cannot be edited; post a reversing entry instead.
                    </p>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
