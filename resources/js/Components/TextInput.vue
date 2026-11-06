<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    invalid: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <input
        v-bind="$attrs"
        :type="type"
        :value="modelValue"
        :class="['field-input', invalid ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-500/20' : '']"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
