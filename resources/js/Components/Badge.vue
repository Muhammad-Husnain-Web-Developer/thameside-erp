<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: { type: String, default: 'slate' },
    dot: { type: Boolean, default: false },
});

const tones = {
    slate: 'bg-slate-100 text-slate-700 ring-slate-200',
    brand: 'bg-brand-50 text-brand-700 ring-brand-200',
    amber: 'bg-amber-50 text-amber-700 ring-amber-200',
    rose: 'bg-rose-50 text-rose-700 ring-rose-200',
    sky: 'bg-sky-50 text-sky-700 ring-sky-200',
    violet: 'bg-violet-50 text-violet-700 ring-violet-200',
};

const dots = {
    slate: 'bg-slate-400',
    brand: 'bg-brand-500',
    amber: 'bg-amber-500',
    rose: 'bg-rose-500',
    sky: 'bg-sky-500',
    violet: 'bg-violet-500',
};

const classes = computed(() => [
    'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset',
    tones[props.tone] ?? tones.slate,
]);
</script>

<template>
    <span :class="classes">
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dots[tone] ?? dots.slate" />
        <slot />
    </span>
</template>
