<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const page = usePage();
const toasts = ref([]);
let nextId = 1;

function push(type, message) {
    if (!message) {
        return;
    }

    const id = nextId++;
    toasts.value.push({ id, type, message });

    window.setTimeout(() => dismiss(id), 5000);
}

function dismiss(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    ([success, error]) => {
        push('success', success);
        push('error', error);
    },
    { immediate: true },
);
</script>

<template>
    <div class="pointer-events-none fixed right-4 top-4 z-[60] flex w-[min(22rem,calc(100vw-2rem))] flex-col gap-2">
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="translate-y-1 opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-3.5 shadow-float"
                :class="toast.type === 'success' ? 'border-brand-200' : 'border-rose-200'"
            >
                <span
                    class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                    :class="toast.type === 'success' ? 'bg-brand-50 text-brand-600' : 'bg-rose-50 text-rose-600'"
                >
                    <Icon :name="toast.type === 'success' ? 'check' : 'warning'" :size="14" :stroke-width="2" />
                </span>
                <p class="flex-1 text-[13px] leading-relaxed text-slate-700">{{ toast.message }}</p>
                <button type="button" class="text-slate-400 transition hover:text-slate-600" @click="dismiss(toast.id)">
                    <Icon name="x" :size="14" />
                    <span class="sr-only">Dismiss</span>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
