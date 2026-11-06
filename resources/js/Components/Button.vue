<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    href: { type: String, default: null },
    method: { type: String, default: 'get' },
    type: { type: String, default: 'button' },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const variants = {
    primary: 'bg-brand-600 text-white hover:bg-brand-700 focus-visible:outline-brand-600 shadow-sm',
    secondary: 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus-visible:outline-slate-400',
    ghost: 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
    danger: 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:outline-rose-600 shadow-sm',
    dangerSoft: 'bg-white text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50',
};

const sizes = {
    sm: 'px-2.5 py-1.5 text-[13px] gap-1.5 rounded-lg',
    md: 'px-3.5 py-2 text-sm gap-2 rounded-lg',
};

const classes = computed(() => [
    'inline-flex items-center justify-center font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
    variants[props.variant] ?? variants.primary,
    sizes[props.size] ?? sizes.md,
]);
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :method="method"
        as="button"
        :class="classes"
        :disabled="disabled || loading"
    >
        <slot />
    </Link>
    <button v-else :type="type" :class="classes" :disabled="disabled || loading">
        <slot />
    </button>
</template>
