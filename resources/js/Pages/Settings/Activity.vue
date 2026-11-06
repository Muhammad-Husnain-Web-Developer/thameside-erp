<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Badge from '../../Components/Badge.vue';
import Card from '../../Components/Card.vue';
import EmptyState from '../../Components/EmptyState.vue';
import Icon from '../../Components/Icon.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import SelectInput from '../../Components/SelectInput.vue';

const props = defineProps({
    activities: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    events: { type: Array, default: () => [] },
});

const search = ref(props.filters.q ?? '');
const event = ref(props.filters.event ?? '');

function apply(overrides = {}) {
    router.get(
        '/settings/activity',
        { q: search.value, event: event.value, ...overrides },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

function reset() {
    search.value = '';
    event.value = '';
    router.get('/settings/activity', {}, { preserveState: true, replace: true });
}

const toneFor = (eventName) =>
    ({ created: 'brand', updated: 'amber', deleted: 'rose', login: 'sky' })[eventName] ?? 'slate';
</script>

<template>
    <Head title="Activity log" />

    <AppLayout>
        <PageHeader
            eyebrow="Administration"
            title="Activity log"
            description="An immutable trail of who changed what across the ERP."
        />

        <Card :padded="false">
            <div class="flex flex-wrap items-end gap-3 border-b border-slate-100 px-5 py-3.5">
                <form class="relative w-full max-w-xs" @submit.prevent="apply()">
                    <Icon name="search" :size="15" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input v-model="search" type="search" placeholder="Search description, causer or subject" class="field-input pl-9" />
                </form>

                <div class="w-40">
                    <SelectInput v-model="event" :options="events" placeholder="All events" @change="apply()" />
                </div>

                <button
                    v-if="filters.q || filters.event"
                    type="button"
                    class="inline-flex items-center gap-1.5 pb-2 text-[12px] font-medium text-slate-500 transition hover:text-slate-800"
                    @click="reset"
                >
                    <Icon name="x" :size="13" />
                    Clear filters
                </button>

                <p class="ml-auto pb-2 text-[12px] text-slate-500">
                    {{ activities.total }} {{ activities.total === 1 ? 'entry' : 'entries' }}
                </p>
            </div>

            <EmptyState
                v-if="activities.data.length === 0"
                icon="history"
                title="Nothing recorded yet"
                description="Activity is captured automatically when staff accounts and documents change."
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <th class="table-heading">When</th>
                            <th class="table-heading">Event</th>
                            <th class="table-heading">Record</th>
                            <th class="table-heading hidden md:table-cell">Changes</th>
                            <th class="table-heading">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="activity in activities.data" :key="activity.id" class="align-top transition hover:bg-slate-50/60">
                            <td class="table-cell whitespace-nowrap">
                                <p class="text-[13px] text-slate-700">{{ activity.created_human }}</p>
                                <p class="text-[11px] text-slate-400">
                                    {{ activity.created_at ? new Date(activity.created_at).toLocaleString('en-GB', { dateStyle: 'short', timeStyle: 'short' }) : '' }}
                                </p>
                            </td>
                            <td class="table-cell">
                                <Badge :tone="toneFor(activity.event)">{{ activity.event ?? activity.log }}</Badge>
                            </td>
                            <td class="table-cell">
                                <p class="text-[13px] font-medium text-slate-800">{{ activity.subject ?? 'System' }}</p>
                                <p class="text-[11px] text-slate-400">{{ activity.subject_id ? `#${activity.subject_id}` : activity.log }}</p>
                            </td>
                            <td class="table-cell hidden md:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="field in activity.changes"
                                        :key="field"
                                        class="rounded bg-slate-50 px-1.5 py-0.5 font-mono text-[10.5px] text-slate-600 ring-1 ring-inset ring-slate-200"
                                    >
                                        {{ field }}
                                    </span>
                                    <span v-if="!activity.changes.length" class="text-[12px] text-slate-300">—</span>
                                </div>
                            </td>
                            <td class="table-cell whitespace-nowrap">
                                <span class="text-[13px] text-slate-700">{{ activity.causer ?? 'System' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="activities.data.length > 0" :paginator="activities" />
        </Card>
    </AppLayout>
</template>
