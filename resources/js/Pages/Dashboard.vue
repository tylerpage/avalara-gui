<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const connection = computed(() => page.props.connection ?? {});
const reviewCounts = computed(() => page.props.reviewCounts ?? { due: 0, today: 0, overdue: 0 });
const webhookCounts = computed(() => page.props.webhookCounts ?? { total: 0, return_related: 0 });
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <div>
                <h1 class="text-3xl font-semibold">Order Transactions Debug</h1>
                <p class="mt-2 text-slate-400 max-w-2xl">
                    Inspect Shopware orders alongside Avalara and Authorize.net transaction data for returns debugging.
                    Configure your API connections to get started.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Link
                    href="/orders/review-queue"
                    class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 hover:border-amber-700 transition"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-medium text-amber-300">Review Queue</h2>
                        <span
                            v-if="reviewCounts.due > 0"
                            class="rounded-full bg-amber-500 text-black text-sm font-medium px-2 py-0.5"
                        >
                            {{ reviewCounts.due }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-400">
                        Orders due for review after Authorize.net settlement — {{ reviewCounts.today }} today,
                        {{ reviewCounts.overdue }} overdue.
                    </p>
                </Link>

                <Link
                    href="/orders"
                    class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 hover:border-sky-700 transition"
                >
                    <h2 class="text-lg font-medium text-sky-300">Browse Orders</h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Search Shopware orders and drill into line items, returns, and Avalara transaction data.
                    </p>
                </Link>

                <Link
                    href="/webhooks"
                    class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 hover:border-amber-700 transition"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-medium text-amber-300">Webhooks</h2>
                        <span
                            v-if="webhookCounts.return_related > 0"
                            class="rounded-full bg-amber-500 text-black text-sm font-medium px-2 py-0.5"
                        >
                            {{ webhookCounts.return_related }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-400">
                        {{ webhookCounts.total }} received — monitor Shopware return events and inspect payloads.
                    </p>
                </Link>

                <Link
                    href="/settings"
                    class="rounded-xl border border-slate-800 bg-slate-900/60 p-6 hover:border-sky-700 transition"
                >
                    <h2 class="text-lg font-medium text-sky-300">Connection Settings</h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Store Shopware OAuth credentials and Avalara API keys used for live transaction lookups.
                    </p>
                </Link>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
                <h2 class="text-lg font-medium mb-4">Connection Status</h2>
                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    <div class="flex justify-between gap-4 border-b border-slate-800 pb-2">
                        <dt class="text-slate-400">Shopware Admin API</dt>
                        <dd :class="connection.shopwareConfigured ? 'text-emerald-400' : 'text-amber-400'">
                            {{ connection.shopwareConfigured ? 'Configured' : 'Not configured' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-800 pb-2">
                        <dt class="text-slate-400">Avalara REST API</dt>
                        <dd :class="connection.avalaraConfigured ? 'text-emerald-400' : 'text-amber-400'">
                            {{ connection.avalaraConfigured ? 'Configured' : 'Optional — for live transaction lookup' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-800 pb-2">
                        <dt class="text-slate-400">Authorize.net API</dt>
                        <dd :class="connection.authnetConfigured ? 'text-emerald-400' : 'text-amber-400'">
                            {{ connection.authnetConfigured ? 'Configured' : 'Optional — for live payment lookup' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </AppLayout>
</template>
