<script setup>
import Icon from './Icon.vue';

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    icon: { type: String, default: 'search' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
            <Icon :name="icon" :size="22" />
        </span>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-[13px] text-slate-500">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>
