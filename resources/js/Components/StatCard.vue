<script setup>
import Icon from './Icon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    meta: { type: String, default: null },
    icon: { type: String, default: 'chart' },
    tone: { type: String, default: 'brand' },
});

const tones = {
    brand: 'bg-brand-50 text-brand-700 ring-brand-100',
    slate: 'bg-slate-100 text-slate-600 ring-slate-200',
    amber: 'bg-amber-50 text-amber-700 ring-amber-100',
    violet: 'bg-violet-50 text-violet-700 ring-violet-100',
};
</script>

<template>
    <div class="card p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[13px] font-medium text-slate-500">{{ label }}</p>
                <p class="mt-2 text-[28px] font-semibold leading-none tracking-tight text-slate-900 tabular-nums">{{ value }}</p>
            </div>
            <span class="flex h-9 w-9 items-center justify-center rounded-lg ring-1 ring-inset" :class="tones[tone] ?? tones.brand">
                <Icon :name="icon" :size="18" />
            </span>
        </div>
        <p v-if="meta" class="mt-3 truncate text-xs text-slate-500">{{ meta }}</p>
    </div>
</template>
