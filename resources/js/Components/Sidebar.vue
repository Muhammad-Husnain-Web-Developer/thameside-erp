<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Avatar from './Avatar.vue';
import Icon from './Icon.vue';

const emit = defineEmits(['navigate']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const nav = computed(() => page.props.navigation ?? []);

const currentPath = computed(() => {
    const path = new URL(page.url, window.location.origin).pathname.replace(/\/+$/, '');

    return path === '' ? '/' : path;
});

function isActive(item) {
    return item.match === '/' ? currentPath.value === '/' : currentPath.value.startsWith(item.match);
}

function roleLabel(role) {
    return role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Staff';
}
</script>

<template>
    <div class="flex h-full flex-col bg-ink-950">
        <div class="flex items-center gap-3 px-5 py-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 text-ink-950 shadow-lg shadow-black/40">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" aria-hidden="true">
                    <path d="M4 6.5h16M12 6.5V19" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                </svg>
            </span>
            <div class="leading-tight">
                <p class="text-[15px] font-semibold tracking-tight text-white">Thameside</p>
                <p class="text-[9px] font-medium uppercase tracking-[0.22em] text-slate-500">Kitchen Supply</p>
            </div>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-4 pt-2">
            <div v-for="group in nav" :key="group.label">
                <p class="px-3 pb-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                    {{ group.label }}
                </p>
                <ul class="space-y-0.5">
                    <li v-for="item in group.items" :key="item.href">
                        <Link
                            :href="item.href"
                            class="group relative flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium transition"
                            :class="isActive(item)
                                ? 'bg-white/[0.08] text-white'
                                : 'text-slate-400 hover:bg-white/[0.04] hover:text-slate-100'"
                            @click="emit('navigate')"
                        >
                            <span
                                v-if="isActive(item)"
                                class="absolute inset-y-1.5 left-0 w-[3px] rounded-full bg-brand-400"
                            />
                            <Icon
                                :name="item.icon"
                                :size="17"
                                :class="isActive(item) ? 'text-brand-300' : 'text-slate-500 group-hover:text-slate-300'"
                            />
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <div v-if="user" class="border-t border-white/[0.06] p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <Avatar :initials="user.initials" size="sm" tone="brand" />
                <div class="min-w-0 flex-1 leading-tight">
                    <p class="truncate text-[13px] font-medium text-white">{{ user.name }}</p>
                    <p class="truncate text-[11px] text-slate-500">{{ roleLabel(user.roles?.[0]) }}</p>
                </div>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="rounded-md p-1.5 text-slate-500 transition hover:bg-white/[0.06] hover:text-slate-200"
                    title="Sign out"
                >
                    <Icon name="logout" :size="16" />
                    <span class="sr-only">Sign out</span>
                </Link>
            </div>
        </div>
    </div>
</template>
