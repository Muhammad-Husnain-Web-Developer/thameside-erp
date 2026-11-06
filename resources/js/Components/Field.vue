<script setup>
defineProps({
    label: { type: String, default: null },
    forId: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    required: { type: Boolean, default: false },
});
</script>

<template>
    <div>
        <label v-if="label" :for="forId" class="field-label">
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>
        <slot />
        <p v-if="error" class="field-error">{{ error }}</p>
        <p v-else-if="hint" class="mt-1.5 text-[12px] text-slate-500">{{ hint }}</p>
    </div>
</template>
