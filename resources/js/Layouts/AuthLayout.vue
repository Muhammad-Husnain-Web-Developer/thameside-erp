<script setup>
import Icon from '../Components/Icon.vue';

const highlights = [
    { icon: 'box', title: 'Stock that adds up', copy: 'Every movement is posted, never overwritten.' },
    { icon: 'banknotes', title: 'Books that balance', copy: 'Invoices post straight to the nominal ledger.' },
    { icon: 'history', title: 'A trail you can trust', copy: 'Who changed what, and when — always on record.' },
];
</script>

<template>
    <div class="flex min-h-screen bg-slate-50">
        <aside class="relative hidden w-[46%] max-w-xl overflow-hidden bg-ink-950 lg:block">
            <div class="pointer-events-none absolute inset-0 opacity-70">
                <div class="absolute -left-24 top-1/4 h-[28rem] w-[28rem] rounded-full bg-brand-600/25 blur-[120px]" />
                <div class="absolute -right-20 bottom-0 h-[24rem] w-[24rem] rounded-full bg-amber-400/10 blur-[110px]" />
            </div>
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.16]"
                style="background-image: linear-gradient(to right, #ffffff 1px, transparent 1px), linear-gradient(to bottom, #ffffff 1px, transparent 1px); background-size: 56px 56px; mask-image: radial-gradient(ellipse at 30% 30%, black, transparent 72%)"
            />

            <div class="relative flex h-full flex-col justify-between p-12">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-500 text-ink-950">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" aria-hidden="true">
                            <path d="M4 6.5h16M12 6.5V19" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                        </svg>
                    </span>
                    <div class="leading-tight">
                        <p class="text-base font-semibold tracking-tight text-white">Thameside</p>
                        <p class="text-[9px] font-medium uppercase tracking-[0.24em] text-slate-500">Kitchen Supply</p>
                    </div>
                </div>

                <div class="max-w-md">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-brand-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-400" />
                        Wholesale distribution · London
                    </p>
                    <h1 class="text-[32px] font-semibold leading-[1.15] tracking-tight text-white">
                        Kitchen appliances,<br />
                        run end to end.
                    </h1>
                    <p class="mt-4 text-sm leading-relaxed text-slate-400">
                        Purchasing, stock, sales and the ledger in one workspace for the Thameside team.
                    </p>

                    <ul class="mt-10 space-y-5">
                        <li v-for="item in highlights" :key="item.title" class="flex gap-3">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/[0.06] text-brand-300 ring-1 ring-inset ring-white/10">
                                <Icon :name="item.icon" :size="16" />
                            </span>
                            <div>
                                <p class="text-[13px] font-medium text-white">{{ item.title }}</p>
                                <p class="text-[12px] text-slate-500">{{ item.copy }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <p class="text-[11px] text-slate-600">
                    Bankside Wharf, Mill Lane, London SE1 2QT · Demo data
                </p>
            </div>
        </aside>

        <main class="flex flex-1 items-center justify-center px-5 py-12 sm:px-8">
            <div class="w-full max-w-[26rem]">
                <slot />
            </div>
        </main>
    </div>
</template>
