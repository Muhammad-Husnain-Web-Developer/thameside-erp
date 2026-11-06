<script setup>
defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    padded: { type: Boolean, default: true },
});
</script>

<template>
    <section class="card overflow-hidden">
        <header v-if="title || $slots.header || $slots.actions" class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">{{ title }}</h2>
                <p v-if="description" class="mt-0.5 text-[13px] text-slate-500">{{ description }}</p>
                <slot name="header" />
            </div>
            <slot name="actions" />
        </header>
        <div :class="padded ? 'p-5' : ''">
            <slot />
        </div>
    </section>
</template>
