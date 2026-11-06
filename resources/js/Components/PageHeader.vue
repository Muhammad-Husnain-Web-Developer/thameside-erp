<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    eyebrow: { type: String, default: null },
});
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p v-if="eyebrow" class="mb-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-700">{{ eyebrow }}</p>
            <h1 class="text-[22px] font-semibold leading-tight tracking-tight text-slate-900">{{ title }}</h1>
            <p v-if="description" class="mt-1 max-w-2xl text-sm text-slate-500">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
