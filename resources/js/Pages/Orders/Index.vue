<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { reviewBadgeLabel, reviewStatusClass, reviewStatusLabel } from '@/lib/reviewStatus.js';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    error: {
        type: String,
        default: null,
    },
    needsConfiguration: {
        type: Boolean,
        default: false,
    },
    reviewCounts: {
        type: Object,
        default: () => ({ due: 0 }),
    },
});

const orderNumber = ref(props.filters.orderNumber ?? '');

function search() {
    router.get('/orders', {
        orderNumber: orderNumber.value || undefined,
        page: 1,
    }, { preserveState: true });
}

function goToPage(page) {
    router.get('/orders', {
        orderNumber: props.filters.orderNumber || undefined,
        page,
    }, { preserveState: true });
}

function formatMoney(amount, currency) {
    if (amount == null) return '—';
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency || 'USD',
    }).format(amount);
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

function formatReviewDate(value) {
    if (!value) return '—';
    return new Date(`${value}T12:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

const totalPages = () => Math.max(1, Math.ceil((props.orders.total || 0) / 25));
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Orders</h1>
                    <p class="mt-1 text-sm text-slate-400">Search Shopware orders to debug returns and payment transaction data.</p>
                </div>

                <form class="flex gap-2" @submit.prevent="search">
                    <input
                        v-model="orderNumber"
                        type="search"
                        placeholder="Order number"
                        class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                    />
                    <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm hover:bg-sky-500">
                        Search
                    </button>
                    <Link
                        href="/orders/review-queue"
                        class="rounded-lg border border-amber-700 bg-amber-900/30 px-4 py-2 text-sm hover:bg-amber-900/50 inline-flex items-center gap-2"
                    >
                        Review Queue
                        <span
                            v-if="reviewCounts.due > 0"
                            class="rounded-full bg-amber-500 text-black text-xs font-medium px-1.5"
                        >
                            {{ reviewCounts.due }}
                        </span>
                    </Link>
                </form>
            </div>

            <div v-if="needsConfiguration" class="rounded-lg border border-amber-800 bg-amber-900/30 px-4 py-3 text-amber-100">
                Configure Shopware credentials in
                <Link href="/settings" class="underline">Settings</Link>
                before browsing orders.
            </div>

            <div v-else-if="error" class="rounded-lg border border-red-800 bg-red-900/30 px-4 py-3 text-red-200">
                {{ error }}
            </div>

            <div v-else class="overflow-hidden rounded-xl border border-slate-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-900/80 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Order #</th>
                            <th class="px-4 py-3 font-medium">Customer</th>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Total</th>
                            <th class="px-4 py-3 font-medium">State</th>
                            <th class="px-4 py-3 font-medium">Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="border-t border-slate-800 hover:bg-slate-900/50"
                        >
                            <td class="px-4 py-3">
                                <Link :href="`/orders/${order.id}`" class="text-sky-400 hover:underline">
                                    {{ order.orderNumber }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ order.customer || '—' }}</td>
                            <td class="px-4 py-3">{{ formatDate(order.orderDateTime) }}</td>
                            <td class="px-4 py-3">{{ formatMoney(order.amountTotal, order.currency) }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs">{{ order.state || '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="order.review"
                                    :class="['rounded-full px-2 py-0.5 text-xs', reviewStatusClass(order.review.status)]"
                                >
                                    {{ reviewBadgeLabel(order.review) }}
                                </span>
                                <span v-else class="text-slate-600">—</span>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!needsConfiguration && orders.total > 25" class="flex items-center justify-between text-sm">
                <span class="text-slate-400">{{ orders.total }} orders total</span>
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
