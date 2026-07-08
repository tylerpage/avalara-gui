<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    webhook: {
        type: Object,
        required: true,
    },
    shopwareReturn: {
        type: Object,
        default: null,
    },
    orderReturns: {
        type: Array,
        default: () => [],
    },
    relatedWebhooks: {
        type: Array,
        default: () => [],
    },
    shopwareError: {
        type: String,
        default: null,
    },
    reviewCounts: {
        type: Object,
        default: () => ({ due: 0 }),
    },
});

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

function formatMoney(amount, currency = 'USD') {
    if (amount == null) return '—';
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amount);
}

function prettyJson(value) {
    return JSON.stringify(value, null, 2);
}

function isNotifiedReturn(returnItem) {
    if (!props.webhook.shopwareReturnId) {
        return false;
    }

    return returnItem.id === props.webhook.shopwareReturnId;
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <div>
                <Link href="/webhooks" class="text-sm text-sky-400 hover:underline">← Back to webhooks</Link>
                <h1 class="mt-2 text-2xl font-semibold">{{ webhook.eventName }}</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Received {{ formatDate(webhook.receivedAt) }}
                    <span v-if="webhook.shopwareEventId" class="ml-2 font-mono text-xs">
                        event {{ webhook.shopwareEventId }}
                    </span>
                </p>
            </div>

            <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                <h2 class="text-lg font-medium text-sky-300">Webhook Summary</h2>
                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    <div class="flex justify-between gap-4 border-b border-slate-800 pb-2">
                        <dt class="text-slate-400">Return-related</dt>
                        <dd :class="webhook.isReturnRelated ? 'text-amber-300' : 'text-slate-300'">
                            {{ webhook.isReturnRelated ? 'Yes' : 'No' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-800 pb-2">
                        <dt class="text-slate-400">Shop source</dt>
                        <dd class="text-slate-300 truncate">{{ webhook.sourceUrl || '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-800 pb-2">
                        <dt class="text-slate-400">Order</dt>
                        <dd>
                            <Link
                                v-if="webhook.shopwareOrderId"
                                :href="`/orders/${webhook.shopwareOrderId}`"
                                class="text-sky-400 hover:underline"
                            >
                                {{ webhook.shopwareOrderNumber || webhook.shopwareOrderId }}
                            </Link>
                            <span v-else class="text-slate-600">—</span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-800 pb-2">
                        <dt class="text-slate-400">Return ID</dt>
                        <dd class="font-mono text-xs text-slate-300">{{ webhook.shopwareReturnId || '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section
                v-if="webhook.isReturnRelated"
                class="rounded-xl border border-amber-800/60 bg-amber-950/20 p-6 space-y-4"
            >
                <div>
                    <h2 class="text-lg font-medium text-amber-300">Shopware Return Data</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Live return data from Shopware for what this webhook notified us about.
                    </p>
                </div>

                <div v-if="shopwareError" class="rounded-lg border border-red-800 bg-red-900/30 px-4 py-3 text-red-200 text-sm">
                    {{ shopwareError }}
                </div>

                <div v-else-if="shopwareReturn" class="space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <h3 class="text-base font-medium">
                            Return {{ shopwareReturn.returnNumber }}
                        </h3>
                        <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs">{{ shopwareReturn.state }}</span>
                    </div>
                    <p class="text-sm text-slate-400">Requested {{ formatDate(shopwareReturn.requestedAt) }}</p>

                    <table class="min-w-full text-sm border border-slate-800 rounded-lg overflow-hidden">
                        <thead class="bg-slate-900/80 text-left text-slate-400">
                            <tr>
                                <th class="px-4 py-2 font-medium">Product</th>
                                <th class="px-4 py-2 font-medium">Qty</th>
                                <th class="px-4 py-2 font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="line in shopwareReturn.lineItems"
                                :key="line.id"
                                class="border-t border-slate-800"
                            >
                                <td class="px-4 py-2">{{ line.label || line.productNumber }}</td>
                                <td class="px-4 py-2">{{ line.quantity }}</td>
                                <td class="px-4 py-2">{{ formatMoney(line.totalPrice) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else-if="orderReturns.length > 0" class="space-y-3">
                    <p class="text-sm text-slate-400">
                        Could not load a specific return from the webhook payload. Showing all returns on the order:
                    </p>
                    <div
                        v-for="returnItem in orderReturns"
                        :key="returnItem.id"
                        class="rounded-lg border border-slate-800 bg-slate-950/40 p-4"
                        :class="{ 'border-amber-700': isNotifiedReturn(returnItem) }"
                    >
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ returnItem.returnNumber }}</span>
                            <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs">{{ returnItem.state }}</span>
                            <span
                                v-if="isNotifiedReturn(returnItem)"
                                class="rounded-full bg-amber-900/60 text-amber-200 px-2 py-0.5 text-xs"
                            >
                                Notified by webhook
                            </span>
                        </div>
                    </div>
                </div>

                <p v-else class="text-sm text-slate-500">
                    No Shopware return data available. The webhook may reference an order-level state change.
                </p>
            </section>

            <section v-if="relatedWebhooks.length > 0" class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                <h2 class="text-lg font-medium text-sky-300">Other Webhooks for This Order</h2>
                <ul class="space-y-2 text-sm">
                    <li
                        v-for="related in relatedWebhooks"
                        :key="related.id"
                        class="flex flex-wrap items-center gap-3 border-b border-slate-800 pb-2"
                    >
                        <span class="text-slate-400">{{ formatDate(related.receivedAt) }}</span>
                        <Link :href="`/webhooks/${related.id}`" class="text-sky-400 hover:underline">
                            {{ related.eventName }}
                        </Link>
                        <span
                            v-if="related.isReturnRelated"
                            class="rounded-full bg-amber-900/60 text-amber-200 px-2 py-0.5 text-xs"
                        >
                            Return
                        </span>
                    </li>
                </ul>
            </section>

            <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                <h2 class="text-lg font-medium text-sky-300">Raw Payload</h2>
                <pre class="overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-300">{{ prettyJson(webhook.payload) }}</pre>
            </section>

            <section v-if="webhook.headers" class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                <h2 class="text-lg font-medium text-sky-300">Request Headers</h2>
                <pre class="overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-300">{{ prettyJson(webhook.headers) }}</pre>
            </section>
        </div>
    </AppLayout>
</template>
