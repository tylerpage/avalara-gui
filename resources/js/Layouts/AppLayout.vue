<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const connection = computed(() => page.props.connection ?? {});
const reviewCounts = computed(() => page.props.reviewCounts ?? { due: 0 });
const passcodeRequired = computed(() => page.props.passcode?.required ?? false);
const dashboard = computed(() => page.props.dashboard ?? { current: null, all: [] });
const switcherOpen = ref(false);

function logout() {
    router.post('/passcode/logout');
}

function switchDashboard(id) {
    switcherOpen.value = false;
    router.post(`/dashboards/${id}/switch`);
}
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <Link href="/" class="font-semibold text-lg text-sky-400 shrink-0">Order Transactions Debug</Link>
                    <div v-if="dashboard.current" class="relative min-w-0">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm hover:bg-slate-900 max-w-full"
                            @click="switcherOpen = !switcherOpen"
                        >
                            <span class="truncate">{{ dashboard.current.name }}</span>
                            <span class="text-slate-500">▾</span>
                        </button>
                        <div
                            v-if="switcherOpen"
                            class="absolute left-0 top-full z-50 mt-1 min-w-[14rem] rounded-lg border border-slate-700 bg-slate-950 py-1 shadow-xl"
                        >
                            <button
                                v-for="item in dashboard.all"
                                :key="item.id"
                                type="button"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-slate-900 flex items-center justify-between gap-2"
                                :class="item.id === dashboard.current.id ? 'text-sky-300' : 'text-slate-300'"
                                @click="switchDashboard(item.id)"
                            >
                                <span class="truncate">{{ item.name }}</span>
                                <span v-if="item.id === dashboard.current.id" class="text-xs text-emerald-400">●</span>
                            </button>
                            <div class="border-t border-slate-800 mt-1 pt-1">
                                <Link
                                    href="/dashboards"
                                    class="block px-3 py-2 text-sm text-slate-400 hover:bg-slate-900 hover:text-sky-300"
                                    @click="switcherOpen = false"
                                >
                                    Manage dashboards…
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <nav class="flex items-center gap-4 text-sm shrink-0">
                    <Link href="/" class="hover:text-sky-400">Home</Link>
                    <Link href="/orders" class="hover:text-sky-400">Orders</Link>
                    <Link href="/webhooks" class="hover:text-sky-400 inline-flex items-center gap-1.5">
                        Webhooks
                        <span
                            v-if="(page.props.webhookCounts?.return_related ?? 0) > 0"
                            class="rounded-full bg-amber-500 text-black text-xs font-medium px-1.5 py-0.5 min-w-[1.25rem] text-center"
                        >
                            {{ page.props.webhookCounts.return_related }}
                        </span>
                    </Link>
                    <Link href="/orders/review-queue" class="hover:text-sky-400 inline-flex items-center gap-1.5">
                        Review Queue
                        <span
                            v-if="reviewCounts.due > 0"
                            class="rounded-full bg-amber-500 text-black text-xs font-medium px-1.5 py-0.5 min-w-[1.25rem] text-center"
                        >
                            {{ reviewCounts.due }}
                        </span>
                    </Link>
                    <Link href="/settings" class="hover:text-sky-400">Settings</Link>
                    <button
                        v-if="passcodeRequired"
                        type="button"
                        class="hover:text-sky-400"
                        @click="logout"
                    >
                        Log out
                    </button>
                </nav>
            </div>
            <div
                v-if="!connection.shopwareConfigured"
                class="bg-amber-900/40 border-t border-amber-800 text-amber-100 text-center py-2 text-sm"
            >
                Shopware credentials are not configured for <strong>{{ dashboard.current?.name }}</strong>.
                <Link href="/settings" class="underline ml-1">Add them in Settings</Link>
            </div>
        </header>

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 py-8">
            <div
                v-if="page.props.flash?.success"
                class="mb-4 rounded-lg bg-emerald-900/50 border border-emerald-700 px-4 py-3 text-emerald-200"
            >
                {{ page.props.flash.success }}
            </div>
            <div
                v-if="page.props.flash?.error"
                class="mb-4 rounded-lg bg-red-900/50 border border-red-700 px-4 py-3 text-red-200"
            >
                {{ page.props.flash.error }}
            </div>
            <slot />
        </main>
    </div>
</template>
