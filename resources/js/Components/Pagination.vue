<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const props = defineProps({
    paginator: { type: Object, required: true },
});

const links = computed(() => props.paginator.links ?? []);

function labelFor(link) {
    if (link.label.includes('Previous')) {
        return 'previous';
    }

    if (link.label.includes('Next')) {
        return 'next';
    }

    return link.label;
}
</script>

<template>
    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3" aria-label="Pagination">
        <p class="text-[13px] text-slate-500">
            <template v-if="paginator.total > 0">
                Showing <span class="font-medium text-slate-700">{{ paginator.from }}</span>–<span class="font-medium text-slate-700">{{ paginator.to }}</span>
                of <span class="font-medium text-slate-700">{{ paginator.total }}</span>
            </template>
            <template v-else>No records</template>
        </p>

        <div class="flex items-center gap-1">
            <template v-for="(link, index) in links" :key="index">
                <span
                    v-if="!link.url"
                    class="inline-flex h-8 items-center rounded-md px-2.5 text-[13px] text-slate-400"
                >
                    <Icon v-if="labelFor(link) === 'previous'" name="chevron-left" :size="16" />
                    <Icon v-else-if="labelFor(link) === 'next'" name="chevron-right" :size="16" />
                    <template v-else>{{ link.label }}</template>
                </span>
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="inline-flex h-8 items-center rounded-md px-2.5 text-[13px] font-medium transition"
                    :class="link.active
                        ? 'bg-brand-600 text-white shadow-sm'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    :aria-current="link.active ? 'page' : undefined"
                >
                    <Icon v-if="labelFor(link) === 'previous'" name="chevron-left" :size="16" />
                    <Icon v-else-if="labelFor(link) === 'next'" name="chevron-right" :size="16" />
                    <template v-else>{{ link.label }}</template>
                </Link>
            </template>
        </div>
    </nav>
</template>
