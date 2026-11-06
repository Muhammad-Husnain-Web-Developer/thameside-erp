<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: null },
    invalid: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <select
        v-bind="$attrs"
        :value="modelValue"
        :class="['field-input pr-8', invalid ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-500/20' : '']"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <option v-if="placeholder" value="">{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value ?? option" :value="option.value ?? option">
            {{ option.label ?? option }}
        </option>
    </select>
</template>
