<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Badge from '../../../Components/Badge.vue';
import Button from '../../../Components/Button.vue';
import Card from '../../../Components/Card.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Icon from '../../../Components/Icon.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatCard from '../../../Components/StatCard.vue';

const props = defineProps({
    entries: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.q ?? '');
let searchTimer = null;

watch(search, () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
        router.get(
            '/accounting/journals',
            { q: search.value.trim() || undefined },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});
</script>

<template>
    <Head title="Journals" />

    <AppLayout>
        <PageHeader
            eyebrow="Accounting"
            title="General ledger"
            description="Every document in Thameside posts a balanced journal here. The ledger is the single record of what the business earned, spent and owes."
        >
            <template #actions>
                <Button v-if="can.create" href="/accounting/journals/create">
                    <Icon name="plus" :size="16" />
                    Post journal
                </Button>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="stat in stats"
                :key="stat.key"
                :label="stat.label"
                :value="stat.value"
                :meta="stat.meta"
                :icon="stat.icon"
            />
        </div>

        <Card :padded="false">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-3.5">
                <div class="relative w-full max-w-xs">
                    <Icon name="search" :size="15" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="search" type="search" placeholder="Search number or description" class="field-input pl-9" />
                </div>

                <p class="ml-auto text-[12px] text-slate-500">
                    {{ entries.total }} {{ entries.total === 1 ? 'journal' : 'journals' }}
                </p>
            </div>

            <EmptyState
                v-if="entries.data.length === 0"
                icon="book"
                title="No journals yet"
                :description="filters.q ? 'Nothing matches that search.' : 'Invoices, bills and manual journals will appear here as they are posted.'"
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">Number</th>
                            <th class="table-heading">Date</th>
                            <th class="table-heading">Description</th>
                            <th class="table-heading hidden lg:table-cell">Source</th>
                            <th class="table-heading hidden text-right sm:table-cell">Lines</th>
                            <th class="table-heading text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="entry in entries.data" :key="entry.id" class="transition hover:bg-slate-50/60">
                            <td class="table-cell whitespace-nowrap">
                                <Link :href="`/accounting/journals/${entry.id}`" class="font-medium text-slate-900 hover:text-brand-700">
                                    {{ entry.number }}
                                </Link>
                            </td>
                            <td class="table-cell whitespace-nowrap text-[13px] text-slate-500">{{ entry.date }}</td>
                            <td class="table-cell">
                                <p class="font-medium text-slate-900">{{ entry.description }}</p>
                                <p class="text-[12px] text-slate-500">Posted by {{ entry.author }}</p>
                            </td>
                            <td class="table-cell hidden lg:table-cell">
                                <Badge :tone="entry.source === 'Manual journal' ? 'slate' : 'brand'">{{ entry.source }}</Badge>
                            </td>
                            <td class="table-cell hidden text-right tabular-nums text-slate-500 sm:table-cell">{{ entry.lines }}</td>
                            <td class="table-cell text-right font-medium tabular-nums text-slate-900">{{ entry.total }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="entries.data.length > 0" :paginator="entries" />
        </Card>
    </AppLayout>
</template>
