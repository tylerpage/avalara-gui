<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    webhooks: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    counts: {
        type: Object,
        required: true,
    },
    webhookUrl: {
        type: String,
        required: true,
    },
    reviewCounts: {
        type: Object,
        default: () => ({ due: 0 }),
    },
});

const orderNumber = ref(props.filters.orderNumber ?? '');
const returnOnly = ref(props.filters.returnOnly ?? false);

function search() {
    router.get('/webhooks', {
        orderNumber: orderNumber.value || undefined,
        returnOnly: returnOnly.value ? 1 : undefined,
        page: 1,
    }, { preserveState: true });
}

function goToPage(page) {
    router.get('/webhooks', {
        orderNumber: props.filters.orderNumber || undefined,
        returnOnly: props.filters.returnOnly ? 1 : undefined,
        page,
    }, { preserveState: true });
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

const totalPages = () => Math.max(1, Math.ceil((props.webhooks.total || 0) / 25));
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Webhooks</h1>
                    <p class="mt-1 text-sm text-slate-400">
                        Incoming Shopware webhooks are stored here. Return-related events are highlighted for quick review.
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        Receiver URL: <code class="text-sky-300">{{ webhookUrl }}</code>
                    </p>
                </div>

                <form class="flex flex-wrap gap-2 items-end" @submit.prevent="search">
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input v-model="returnOnly" type="checkbox" class="rounded border-slate-600" />
                        Return-related only
                    </label>
                    <input
                        v-model="orderNumber"
                        type="search"
                        placeholder="Order number"
                        class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    />
                    <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm hover:bg-sky-500">
                        Filter
                    </button>
                </form>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 text-sm">
                <div class="rounded-lg border border-slate-800 bg-slate-900/40 px-4 py-3">
                    <span class="text-slate-400">Total received</span>
                    <p class="text-xl font-semibold text-sky-300">{{ counts.total }}</p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-900/40 px-4 py-3">
                    <span class="text-slate-400">Return-related</span>
                    <p class="text-xl font-semibold text-amber-300">{{ counts.return_related }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-900/80 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Received</th>
                            <th class="px-4 py-3 font-medium">Event</th>
                            <th class="px-4 py-3 font-medium">Order</th>
                            <th class="px-4 py-3 font-medium">Return</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="webhook in webhooks.data"
                            :key="webhook.id"
                            class="border-t border-slate-800 hover:bg-slate-900/50"
                        >
                            <td class="px-4 py-3 text-slate-400">{{ formatDate(webhook.received_at) }}</td>
                            <td class="px-4 py-3">
                                <Link :href="`/webhooks/${webhook.id}`" class="text-sky-400 hover:underline">
                                    {{ webhook.event_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <Link
                                    v-if="webhook.shopware_order_id"
                                    :href="`/orders/${webhook.shopware_order_id}`"
                                    class="text-sky-400 hover:underline"
                                >
                                    {{ webhook.shopware_order_number || webhook.shopware_order_id }}
                                </Link>
                                <span v-else class="text-slate-600">—</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-400">
                                {{ webhook.shopware_return_id || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="webhook.is_return_related"
                                    class="rounded-full bg-amber-900/60 text-amber-200 px-2 py-0.5 text-xs"
                                >
                                    Return
                                </span>
                                <span v-else class="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">
                                    Other
                                </span>
                            </td>
                        </tr>
                        <tr v-if="webhooks.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                No webhooks received yet. Register the receiver URL in Shopware Admin.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="webhooks.total > 25" class="flex items-center justify-between text-sm">
                <span class="text-slate-400">{{ webhooks.total }} webhooks total</span>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded border border-slate-700 px-3 py-1 disabled:opacity-40"
                        :disabled="filters.page <= 1"
                        @click="goToPage(filters.page - 1)"
                    >
                        Previous
                    </button>
                    <span class="px-2 py-1 text-slate-400">Page {{ filters.page }} of {{ totalPages() }}</span>
                    <button
                        type="button"
                        class="rounded border border-slate-700 px-3 py-1 disabled:opacity-40"
                        :disabled="filters.page >= totalPages()"
                        @click="goToPage(filters.page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
