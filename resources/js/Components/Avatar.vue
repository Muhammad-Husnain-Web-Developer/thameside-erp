<script setup>
import { computed } from 'vue';

const props = defineProps({
    initials: { type: String, required: true },
    tone: { type: String, default: 'brand' },
    size: { type: String, default: 'md' },
});

const sizes = {
    sm: 'h-7 w-7 text-[11px]',
    md: 'h-9 w-9 text-xs',
    lg: 'h-11 w-11 text-sm',
};

const tones = {
    brand: 'bg-brand-600 text-white',
    ink: 'bg-ink-800 text-white',
    slate: 'bg-slate-200 text-slate-700',
};

const classes = computed(() => [
    'inline-flex items-center justify-center rounded-full font-semibold tracking-wide',
    sizes[props.size] ?? sizes.md,
    tones[props.tone] ?? tones.brand,
]);
</script>

<template>
    <span :class="classes">{{ initials }}</span>
</template>
