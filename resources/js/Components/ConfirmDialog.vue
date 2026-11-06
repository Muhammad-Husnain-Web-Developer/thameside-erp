<script setup>
import { onMounted, onUnmounted } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Are you sure?' },
    message: { type: String, default: null },
    confirmLabel: { type: String, default: 'Confirm' },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'close']);

function onKeydown(event) {
    if (event.key === 'Escape' && props.show) {
        emit('close');
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-ink-950/50 backdrop-blur-[2px]" @click="emit('close')" />
                <div class="relative w-full max-w-md rounded-xl bg-white p-5 shadow-float">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                            <Icon name="warning" :size="20" />
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
                            <p v-if="message" class="mt-1 text-[13px] leading-relaxed text-slate-500">{{ message }}</p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg bg-white px-3.5 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50"
                            :disabled="processing"
                            @click="emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-60"
                            :disabled="processing"
                            @click="emit('confirm')"
                        >
                            {{ processing ? 'Working…' : confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
