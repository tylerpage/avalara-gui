<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const connection = computed(() => page.props.connection ?? {});
const reviewCounts = computed(() => page.props.reviewCounts ?? { due: 0 });
const passcodeRequired = computed(() => page.props.passcode?.required ?? false);

function logout() {
    router.post('/passcode/logout');
}
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
                <Link href="/" class="font-semibold text-lg text-sky-400">Order Transactions Debug</Link>
                <nav class="flex items-center gap-4 text-sm">
                    <Link href="/" class="hover:text-sky-400">Dashboard</Link>
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
                Shopware credentials are not configured.
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
