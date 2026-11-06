<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Avatar from '../Components/Avatar.vue';
import FlashMessages from '../Components/FlashMessages.vue';
import Icon from '../Components/Icon.vue';
import Sidebar from '../Components/Sidebar.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const company = computed(() => page.props.company ?? {});

const mobileOpen = ref(false);
const userMenuOpen = ref(false);

function roleLabel(role) {
    return role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Staff';
}

const today = computed(() =>
    new Intl.DateTimeFormat('en-GB', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date()),
);
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Desktop sidebar -->
        <div class="fixed inset-y-0 left-0 z-30 hidden w-[264px] lg:block">
            <Sidebar />
        </div>

        <!-- Mobile sidebar -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileOpen" class="fixed inset-0 z-40 lg:hidden">
                <div class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm" @click="mobileOpen = false" />
                <div class="relative h-full w-[272px] shadow-float">
                    <Sidebar @navigate="mobileOpen = false" />
                    <button
                        type="button"
                        class="absolute -right-11 top-4 rounded-lg bg-white/10 p-2 text-white backdrop-blur transition hover:bg-white/20"
                        @click="mobileOpen = false"
                    >
                        <Icon name="x" :size="18" />
                        <span class="sr-only">Close navigation</span>
                    </button>
                </div>
            </div>
        </Transition>

        <div class="lg:pl-[264px]">
            <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/85 backdrop-blur">
                <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
                            @click="mobileOpen = true"
                        >
                            <Icon name="menu" :size="20" />
                            <span class="sr-only">Open navigation</span>
                        </button>
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-slate-700">{{ company.name }}</p>
                            <p class="truncate text-[11px] text-slate-400">{{ today }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <span class="hidden items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600 md:inline-flex">
                            <span class="h-1.5 w-1.5 rounded-full bg-brand-500" />
                            VAT {{ company.vat_rate_label }}
                        </span>
                        <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600 sm:inline-flex">
                            {{ company.currency }}
                        </span>

                        <div class="relative">
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-slate-100"
                                @click="userMenuOpen = !userMenuOpen"
                            >
                                <Avatar :initials="user?.initials ?? '?'" size="sm" tone="ink" />
                                <span class="hidden text-left leading-tight sm:block">
                                    <span class="block text-[13px] font-medium text-slate-800">{{ user?.name }}</span>
                                    <span class="block text-[11px] text-slate-500">{{ roleLabel(user?.roles?.[0]) }}</span>
                                </span>
                                <Icon name="chevron-down" :size="14" class="text-slate-400" />
                            </button>

                            <div v-if="userMenuOpen" class="fixed inset-0 z-30" @click="userMenuOpen = false" />

                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="translate-y-1 opacity-0"
                                leave-active-class="transition duration-100 ease-in"
                                leave-to-class="translate-y-1 opacity-0"
                            >
                                <div
                                    v-if="userMenuOpen"
                                    class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-float"
                                >
                                    <div class="border-b border-slate-100 px-4 py-3">
                                        <p class="text-[13px] font-semibold text-slate-900">{{ user?.name }}</p>
                                        <p class="truncate text-[12px] text-slate-500">{{ user?.email }}</p>
                                        <p v-if="user?.job_title" class="mt-1 text-[11px] text-slate-400">{{ user.job_title }}</p>
                                    </div>
                                    <div class="px-2 py-2">
                                        <button
                                            v-for="role in user?.roles ?? []"
                                            :key="role"
                                            type="button"
                                            class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-[12px] text-slate-600"
                                        >
                                            <Icon name="shield" :size="14" class="text-slate-400" />
                                            {{ roleLabel(role) }}
                                        </button>
                                    </div>
                                    <div class="border-t border-slate-100 p-2">
                                        <Link
                                            href="/logout"
                                            method="post"
                                            as="button"
                                            class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-[13px] font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                        >
                                            <Icon name="logout" :size="15" class="text-slate-400" />
                                            Sign out
                                        </Link>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <slot />
            </main>

            <footer class="border-t border-slate-200/70 px-4 py-5 sm:px-6 lg:px-8">
                <p class="text-[11px] text-slate-400">
                    {{ company.name }} · {{ company.address_line_1 }}, {{ company.city }} {{ company.postcode }} · VAT {{ company.vat_number }}
                </p>
            </footer>
        </div>

        <FlashMessages />
    </div>
</template>
