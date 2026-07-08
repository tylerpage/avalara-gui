<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AvalaraTransactionPanel from '@/Components/AvalaraTransactionPanel.vue';
import { reviewStatusClass, reviewStatusLabel } from '@/lib/reviewStatus.js';
import { Link, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    orderId: {
        type: String,
        required: true,
    },
    order: {
        type: Object,
        default: null,
    },
    review: {
        type: Object,
        default: null,
    },
    returnsAvalara: {
        type: Array,
        default: () => [],
    },
    returnsAuthnet: {
        type: Array,
        default: () => [],
    },
    remainingRefundable: {
        type: Object,
        default: null,
    },
    avalaraTransaction: {
        type: Object,
        default: null,
    },
    authnetTransaction: {
        type: Object,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
    avalaraError: {
        type: String,
        default: null,
    },
    authnetError: {
        type: String,
        default: null,
    },
    needsConfiguration: {
        type: Boolean,
        default: false,
    },
    avalaraConfigured: {
        type: Boolean,
        default: false,
    },
    webhooks: {
        type: Array,
        default: () => [],
    },
});

const reviewForm = useForm({
    order_number: props.order?.orderNumber ?? '',
    review_date: props.review?.reviewDate ?? '',
    notes: props.review?.notes ?? '',
});

const outcomeForm = useForm({
    order_number: props.order?.orderNumber ?? '',
    review_outcome: props.review?.reviewOutcome ?? '',
    notes: props.review?.notes ?? '',
});

watch(
    () => [props.review, props.order?.orderNumber],
    () => {
        reviewForm.order_number = props.order?.orderNumber ?? '';
        reviewForm.review_date = props.review?.reviewDate ?? '';
        reviewForm.notes = props.review?.notes ?? '';

        outcomeForm.order_number = props.order?.orderNumber ?? '';
        outcomeForm.review_outcome = props.review?.reviewOutcome ?? '';
        outcomeForm.notes = props.review?.notes ?? '';
    },
);

function saveReview() {
    reviewForm.order_number = props.order?.orderNumber ?? '';
    reviewForm.put(`/orders/${props.orderId}/review`, { preserveScroll: true });
}

function scheduleTomorrow() {
    router.post(`/orders/${props.orderId}/review/tomorrow`, {
        order_number: props.order?.orderNumber ?? '',
        notes: reviewForm.notes || undefined,
    }, { preserveScroll: true });
}

function clearReview() {
    router.delete(`/orders/${props.orderId}/review`, { preserveScroll: true });
}

function markDoNotReview() {
    router.post(`/orders/${props.orderId}/review/do-not-review`, {
        order_number: props.order?.orderNumber ?? '',
        notes: reviewForm.notes || undefined,
    }, { preserveScroll: true });
}

function saveOutcome() {
    outcomeForm.order_number = props.order?.orderNumber ?? '';
    outcomeForm.put(`/orders/${props.orderId}/review/outcome`, { preserveScroll: true });
}

function setOutcome(outcome) {
    outcomeForm.review_outcome = outcome;

    if (outcome === 'pass' || (outcomeForm.notes && outcomeForm.notes.trim() !== '')) {
        outcomeForm.order_number = props.order?.orderNumber ?? '';
        outcomeForm.put(`/orders/${props.orderId}/review/outcome`, { preserveScroll: true });
    }
}

const isDoNotReview = () => props.review?.doNotReview === true;
const hasFinalOutcome = () => ['pass', 'defunct'].includes(props.review?.reviewOutcome);

function formatReviewDate(value) {
    if (!value) return '—';
    return new Date(`${value}T12:00:00`).toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatMoney(amount, currency = 'USD') {
    if (amount == null) return '—';
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amount);
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

function statusClass(status) {
    const map = {
        pending: 'bg-slate-700 text-slate-200',
        verified: 'bg-blue-900/60 text-blue-200',
        submitted: 'bg-emerald-900/60 text-emerald-200',
        failed: 'bg-red-900/60 text-red-200',
        passed: 'bg-emerald-900/60 text-emerald-200',
    };

    return map[status] ?? 'bg-slate-700 text-slate-200';
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <div>
                <Link href="/orders" class="text-sm text-sky-400 hover:underline">← Back to orders</Link>
                <h1 v-if="order" class="mt-2 text-2xl font-semibold">
                    Order {{ order.orderNumber }}
                </h1>
                <h1 v-else class="mt-2 text-2xl font-semibold">Order Detail</h1>
            </div>

            <div v-if="needsConfiguration" class="rounded-lg border border-amber-800 bg-amber-900/30 px-4 py-3">
                Configure credentials in <Link href="/settings" class="underline">Settings</Link>.
            </div>

            <div v-else-if="error" class="rounded-lg border border-red-800 bg-red-900/30 px-4 py-3 text-red-200">
                {{ error }}
            </div>

            <template v-else-if="order">
                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-medium text-sky-300">Review Schedule</h2>
                            <p class="mt-1 text-sm text-slate-400">
                                Set when to re-check this order after Authorize.net settlement runs overnight.
                            </p>
                        </div>
                        <span
                            v-if="review"
                            :class="['rounded-full px-2 py-0.5 text-xs', reviewStatusClass(review.status)]"
                        >
                            {{ reviewStatusLabel(review.status) }}
                        </span>
                    </div>

                    <div v-if="isDoNotReview()" class="rounded-lg border border-slate-700 bg-slate-950/60 px-4 py-3 text-sm text-slate-300">
                        This order is excluded from the review queue.
                        <span v-if="review?.notes" class="block mt-1 text-slate-400">{{ review.notes }}</span>
                    </div>

                    <div v-else class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium hover:bg-amber-500"
                            @click="scheduleTomorrow"
                        >
                            Review tomorrow
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-slate-600 px-4 py-2 text-sm hover:bg-slate-800"
                            @click="markDoNotReview"
                        >
                            Do not review
                        </button>
                        <button
                            v-if="review"
                            type="button"
                            class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800"
                            @click="clearReview"
                        >
                            Clear
                        </button>
                    </div>

                    <form v-if="!isDoNotReview()" class="grid gap-4 sm:grid-cols-2" @submit.prevent="saveReview">
                        <label class="block space-y-1">
                            <span class="text-sm text-slate-300">Review date</span>
                            <input
                                v-model="reviewForm.review_date"
                                type="date"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                            />
                        </label>
                        <label class="block space-y-1 sm:col-span-2">
                            <span class="text-sm text-slate-300">Notes</span>
                            <input
                                v-model="reviewForm.notes"
                                type="text"
                                placeholder="e.g. Waiting for Auth.net refund to settle"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2"
                            />
                        </label>
                        <div class="sm:col-span-2 flex items-center gap-3">
                            <button
                                type="submit"
                                class="rounded-lg bg-sky-600 px-4 py-2 text-sm hover:bg-sky-500 disabled:opacity-50"
                                :disabled="reviewForm.processing || !reviewForm.review_date"
                            >
                                Save review date
                            </button>
                            <span v-if="review?.reviewDate" class="text-sm text-slate-400">
                                Currently scheduled: {{ formatReviewDate(review.reviewDate) }}
                            </span>
                        </div>
                    </form>

                    <div v-else class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800"
                            @click="clearReview"
                        >
                            Remove do not review
                        </button>
                    </div>
                </section>

                <section v-if="!isDoNotReview()" class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-medium text-sky-300">Review Outcome</h2>
                            <p class="mt-1 text-sm text-slate-400">
                                Record the result after checking Shopware, Avalara, and Authorize.net data.
                            </p>
                        </div>
                        <span
                            v-if="review?.reviewOutcome"
                            :class="['rounded-full px-2 py-0.5 text-xs', reviewStatusClass(review.reviewOutcome)]"
                        >
                            {{ reviewStatusLabel(review.reviewOutcome) }}
                        </span>
                    </div>

                    <div v-if="hasFinalOutcome()" class="rounded-lg border border-slate-700 bg-slate-950/60 px-4 py-3 text-sm">
                        <p class="text-slate-300">Marked as <strong>{{ reviewStatusLabel(review.reviewOutcome) }}</strong>.</p>
                        <p v-if="review?.notes" class="mt-2 text-slate-400">{{ review.notes }}</p>
                    </div>

                    <form class="space-y-4" @submit.prevent="saveOutcome">
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium hover:bg-emerald-600"
                                @click="setOutcome('pass')"
                            >
                                Pass
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-orange-700 px-4 py-2 text-sm font-medium hover:bg-orange-600"
                                @click="setOutcome('needs_work')"
                            >
                                Needs work
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-red-800 px-4 py-2 text-sm font-medium hover:bg-red-700"
                                @click="setOutcome('defunct')"
                            >
                                Defunct
                            </button>
                        </div>

                        <label class="block space-y-1">
                            <span class="text-sm text-slate-300">Outcome</span>
                            <select
                                v-model="outcomeForm.review_outcome"
                                class="w-full max-w-xs rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                            >
                                <option value="">Select outcome…</option>
                                <option value="pass">Pass</option>
                                <option value="needs_work">Needs work</option>
                                <option value="defunct">Defunct</option>
                            </select>
                        </label>

                        <label class="block space-y-1">
                            <span class="text-sm text-slate-300">Notes</span>
                            <textarea
                                v-model="outcomeForm.notes"
                                rows="3"
                                placeholder="Required for Needs work and Defunct — describe what to fix or why it's defunct"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm"
                            />
                            <p v-if="outcomeForm.errors.notes" class="text-sm text-red-400">{{ outcomeForm.errors.notes }}</p>
                        </label>

                        <button
                            type="submit"
                            class="rounded-lg bg-sky-600 px-4 py-2 text-sm hover:bg-sky-500 disabled:opacity-50"
                            :disabled="outcomeForm.processing || !outcomeForm.review_outcome"
                        >
                            Save outcome
                        </button>
                    </form>
                </section>

                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
                    <h2 class="text-lg font-medium text-sky-300 mb-4">Shopware Order Summary</h2>
                    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                        <div>
                            <dt class="text-slate-400">Customer</dt>
                            <dd>{{ order.customer?.name || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Email</dt>
                            <dd>{{ order.customer?.email || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Order Date</dt>
                            <dd>{{ formatDate(order.orderDateTime) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">State</dt>
                            <dd>{{ order.state || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Total</dt>
                            <dd>{{ formatMoney(order.amountTotal, order.currency) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Net</dt>
                            <dd>{{ formatMoney(order.amountNet, order.currency) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Shipping</dt>
                            <dd>{{ formatMoney(order.shippingTotal, order.currency) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Tax Status</dt>
                            <dd>{{ order.taxStatus || '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section v-if="order.transactions?.length" class="rounded-xl border border-slate-800 overflow-hidden">
                    <div class="border-b border-slate-800 bg-slate-900/80 px-4 py-3">
                        <h2 class="text-lg font-medium text-sky-300">Payment Transactions</h2>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3">State</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Auth.net Trans ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="txn in order.transactions"
                                :key="txn.id"
                                class="border-t border-slate-800"
                            >
                                <td class="px-4 py-3">{{ txn.paymentMethod || '—' }}</td>
                                <td class="px-4 py-3">{{ txn.state || '—' }}</td>
                                <td class="px-4 py-3">{{ formatMoney(txn.amount, order.currency) }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ txn.authnetTransId || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section v-if="remainingRefundable" class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
                    <h2 class="text-lg font-medium text-sky-300 mb-4">Refund Summary</h2>
                    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                        <div>
                            <dt class="text-slate-400">Settled Amount</dt>
                            <dd>{{ formatMoney(remainingRefundable.settledAmount, order.currency) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Refunded Total</dt>
                            <dd>{{ formatMoney(remainingRefundable.refundedTotal, order.currency) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Pending Returns</dt>
                            <dd>{{ formatMoney(remainingRefundable.pendingTotal, order.currency) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Remaining Refundable</dt>
                            <dd>{{ formatMoney(remainingRefundable.remainingRefundable, order.currency) }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-xl border border-slate-800 overflow-hidden">
                    <div class="border-b border-slate-800 bg-slate-900/80 px-4 py-3">
                        <h2 class="text-lg font-medium text-sky-300">Order Line Items</h2>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Unit</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Tax</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="line in order.lineItems"
                                :key="line.id"
                                class="border-t border-slate-800"
                            >
                                <td class="px-4 py-3">{{ line.label }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ line.productNumber || '—' }}</td>
                                <td class="px-4 py-3">{{ line.quantity }}</td>
                                <td class="px-4 py-3">{{ formatMoney(line.unitPrice, order.currency) }}</td>
                                <td class="px-4 py-3">{{ formatMoney(line.totalPrice, order.currency) }}</td>
                                <td class="px-4 py-3">{{ formatMoney(line.taxTotal, order.currency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section v-if="webhooks.length" class="rounded-xl border border-slate-800 overflow-hidden">
                    <div class="border-b border-slate-800 bg-slate-900/80 px-4 py-3 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-medium text-sky-300">Webhooks for This Order</h2>
                        <Link href="/webhooks?returnOnly=1" class="text-sm text-sky-400 hover:underline">
                            View all return webhooks
                        </Link>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-slate-400 bg-slate-900/40">
                            <tr>
                                <th class="px-4 py-3 font-medium">Received</th>
                                <th class="px-4 py-3 font-medium">Event</th>
                                <th class="px-4 py-3 font-medium">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="webhook in webhooks"
                                :key="webhook.id"
                                class="border-t border-slate-800 hover:bg-slate-900/50"
                            >
                                <td class="px-4 py-3 text-slate-400">{{ formatDate(webhook.receivedAt) }}</td>
                                <td class="px-4 py-3">
                                    <Link :href="`/webhooks/${webhook.id}`" class="text-sky-400 hover:underline">
                                        {{ webhook.eventName }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="webhook.isReturnRelated"
                                        class="rounded-full bg-amber-900/60 text-amber-200 px-2 py-0.5 text-xs"
                                    >
                                        Return
                                    </span>
                                    <span v-else class="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">
                                        Other
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section v-if="order.returns?.length" class="rounded-xl border border-slate-800 overflow-hidden">
                    <div class="border-b border-slate-800 bg-slate-900/80 px-4 py-3">
                        <h2 class="text-lg font-medium text-sky-300">Shopware Returns</h2>
                    </div>
                    <div v-for="ret in order.returns" :key="ret.id" class="border-t border-slate-800 p-4 space-y-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-medium">{{ ret.returnNumber }}</span>
                            <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs">{{ ret.state }}</span>
                            <span class="text-sm text-slate-400">{{ formatDate(ret.requestedAt) }}</span>
                        </div>
                        <table v-if="ret.lineItems?.length" class="min-w-full text-sm">
                            <thead class="text-left text-slate-400">
                                <tr>
                                    <th class="py-2 pr-4">Product</th>
                                    <th class="py-2 pr-4">SKU</th>
                                    <th class="py-2 pr-4">Qty</th>
                                    <th class="py-2 pr-4">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="line in ret.lineItems" :key="line.id">
                                    <td class="py-2 pr-4">{{ line.label }}</td>
                                    <td class="py-2 pr-4 font-mono text-xs">{{ line.productNumber || '—' }}</td>
                                    <td class="py-2 pr-4">{{ line.quantity }}</td>
                                    <td class="py-2 pr-4">{{ formatMoney(line.totalPrice, order.currency) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <div>
                        <h2 class="text-lg font-medium text-sky-300">ReturnsAvalara Reconcile Data</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            From Shopware endpoint
                            <code class="text-sky-300">GET /api/_action/returns-avalara/order/{{ orderId }}</code>
                        </p>
                    </div>

                    <div v-if="avalaraError" class="rounded-lg border border-amber-800 bg-amber-900/30 px-4 py-3 text-amber-100 text-sm">
                        {{ avalaraError }}
                    </div>

                    <div v-if="returnsAvalara.length === 0" class="text-sm text-slate-500">
                        No returns found for this order.
                    </div>

                    <div
                        v-for="(entry, index) in returnsAvalara"
                        :key="entry.shopware?.orderReturnId ?? index"
                        class="rounded-lg border border-slate-800 bg-slate-950/60 p-4 space-y-4"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-medium">{{ entry.shopware?.returnNumber }}</span>
                            <span :class="['rounded-full px-2 py-0.5 text-xs', statusClass(entry.avalara?.status)]">
                                Avalara: {{ entry.avalara?.status }}
                            </span>
                            <span class="text-sm text-slate-400">Return state: {{ entry.shopware?.returnState }}</span>
                        </div>

                        <div v-if="entry.avalara?.verification" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                            <div>
                                <dt class="text-slate-400">Verification</dt>
                                <dd>
                                    <span :class="['rounded-full px-2 py-0.5 text-xs', statusClass(entry.avalara.verification.status)]">
                                        {{ entry.avalara.verification.status }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Tax (Shopware)</dt>
                                <dd>{{ entry.avalara.verification.taxShopware ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Tax (Avalara)</dt>
                                <dd>{{ entry.avalara.verification.taxAvalara ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Diff</dt>
                                <dd>{{ entry.avalara.verification.diff ?? '—' }}</dd>
                            </div>
                        </div>

                        <div v-if="entry.avalara?.refund?.transactionCode" class="text-sm">
                            <span class="text-slate-400">Refund transaction:</span>
                            {{ entry.avalara.refund.transactionCode }}
                            <span v-if="entry.avalara.refund.submittedAt" class="text-slate-500 ml-2">
                                ({{ formatDate(entry.avalara.refund.submittedAt) }})
                            </span>
                        </div>

                        <details v-if="entry.avalara?.lastError" class="text-sm">
                            <summary class="cursor-pointer text-red-300">Last error</summary>
                            <pre class="mt-2 overflow-x-auto rounded bg-slate-900 p-3 text-xs">{{ JSON.stringify(entry.avalara.lastError, null, 2) }}</pre>
                        </details>

                        <details class="text-sm">
                            <summary class="cursor-pointer text-slate-400">Raw Avalara payload</summary>
                            <pre class="mt-2 overflow-x-auto rounded bg-slate-900 p-3 text-xs">{{ JSON.stringify(entry.avalara, null, 2) }}</pre>
                        </details>

                        <div class="border-t border-slate-800 pt-4 space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-sky-200">Live Refund Transaction</h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    Live lookup from Avalara REST API (refund document code).
                                </p>
                                <div class="mt-3">
                                    <AvalaraTransactionPanel
                                        :lookup="entry.liveAvalara?.refund"
                                        :configured="avalaraConfigured"
                                        empty-message="No refund transaction found in Avalara for this return."
                                    />
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-sky-200">Live Verify Transaction</h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    Verify (simulated return tax) — may not exist if Avalara did not persist the SalesOrder doc.
                                </p>
                                <div class="mt-3">
                                    <AvalaraTransactionPanel
                                        :lookup="entry.liveAvalara?.verify"
                                        :configured="avalaraConfigured"
                                        empty-message="No verify transaction found in Avalara for this return."
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <div>
                        <h2 class="text-lg font-medium text-sky-300">Avalara Transaction</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            Live lookup by document code (order number) from Avalara REST API.
                        </p>
                    </div>

                    <div v-if="!avalaraTransaction" class="text-sm text-slate-500">
                        No Avalara transaction found for this order number, or Avalara credentials are not configured.
                    </div>

                    <AvalaraTransactionPanel
                        v-else
                        :transaction="avalaraTransaction"
                        :configured="avalaraConfigured"
                        empty-message="No Avalara transaction found for this order number."
                    />
                </section>

                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <div>
                        <h2 class="text-lg font-medium text-sky-300">ReturnsAuthnet Reconcile Data</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            From Shopware endpoint
                            <code class="text-sky-300">GET /api/_action/returns-authnet/order/{{ orderId }}</code>
                        </p>
                    </div>

                    <div v-if="authnetError" class="rounded-lg border border-amber-800 bg-amber-900/30 px-4 py-3 text-amber-100 text-sm">
                        {{ authnetError }}
                    </div>

                    <div v-if="returnsAuthnet.length === 0" class="text-sm text-slate-500">
                        No returns with Authorize.net reconcile data for this order.
                    </div>

                    <div
                        v-for="(entry, index) in returnsAuthnet"
                        :key="entry.shopware?.orderReturnId ?? index"
                        class="rounded-lg border border-slate-800 bg-slate-950/60 p-4 space-y-4"
                    >
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-medium">{{ entry.shopware?.returnNumber }}</span>
                            <span :class="['rounded-full px-2 py-0.5 text-xs', statusClass(entry.authnet?.status)]">
                                Auth.net: {{ entry.authnet?.status }}
                            </span>
                            <span class="text-sm text-slate-400">Return state: {{ entry.shopware?.returnState }}</span>
                        </div>

                        <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                            <div>
                                <dt class="text-slate-400">Trans ID</dt>
                                <dd class="font-mono text-xs">{{ entry.authnet?.authnetTransId || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Refunded Amount</dt>
                                <dd>{{ formatMoney(entry.authnet?.refundedAmount, order.currency) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Capture Refund ID</dt>
                                <dd class="font-mono text-xs break-all">{{ entry.authnet?.captureRefundId || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Submitted At</dt>
                                <dd>{{ formatDate(entry.authnet?.submittedAt) }}</dd>
                            </div>
                        </dl>

                        <div v-if="entry.authnet?.errorMessage" class="text-sm text-red-300">
                            {{ entry.authnet.errorMessage }}
                        </div>

                        <details class="text-sm">
                            <summary class="cursor-pointer text-slate-400">Raw Auth.net payload</summary>
                            <pre class="mt-2 overflow-x-auto rounded bg-slate-900 p-3 text-xs">{{ JSON.stringify(entry.authnet, null, 2) }}</pre>
                        </details>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
                    <div>
                        <h2 class="text-lg font-medium text-sky-300">Authorize.net Transaction</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            Live lookup by trans ID from the order payment transaction custom fields.
                        </p>
                    </div>

                    <div v-if="authnetError && !authnetTransaction" class="rounded-lg border border-amber-800 bg-amber-900/30 px-4 py-3 text-amber-100 text-sm">
                        {{ authnetError }}
                    </div>

                    <div v-if="!authnetTransaction" class="text-sm text-slate-500">
                        No Authorize.net transaction found, or credentials are not configured.
                        <span v-if="order.authnetTransId" class="block mt-1 font-mono text-xs text-slate-400">
                            Trans ID from Shopware: {{ order.authnetTransId }}
                        </span>
                    </div>

                    <template v-else>
                        <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                            <div>
                                <dt class="text-slate-400">Trans ID</dt>
                                <dd class="font-mono text-xs">{{ authnetTransaction.transId }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Status</dt>
                                <dd>{{ authnetTransaction.transactionStatus }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Settled Amount</dt>
                                <dd>{{ formatMoney(authnetTransaction.settleAmount, order.currency) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Auth Amount</dt>
                                <dd>{{ formatMoney(authnetTransaction.authAmount, order.currency) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Submitted (UTC)</dt>
                                <dd>{{ formatDate(authnetTransaction.submitTimeUTC) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Card</dt>
                                <dd>
                                    {{ authnetTransaction.payment?.creditCard?.cardType || '—' }}
                                    {{ authnetTransaction.payment?.creditCard?.cardNumber || '' }}
                                </dd>
                            </div>
                        </dl>

                        <div v-if="authnetTransaction.lineItems?.length" class="overflow-hidden rounded-lg border border-slate-800">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-900/80 text-left text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3">Item</th>
                                        <th class="px-4 py-3">Description</th>
                                        <th class="px-4 py-3">Qty</th>
                                        <th class="px-4 py-3">Unit Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="line in authnetTransaction.lineItems"
                                        :key="line.itemId"
                                        class="border-t border-slate-800"
                                    >
                                        <td class="px-4 py-3">{{ line.name }}</td>
                                        <td class="px-4 py-3">{{ line.description }}</td>
                                        <td class="px-4 py-3">{{ line.quantity }}</td>
                                        <td class="px-4 py-3">{{ formatMoney(line.unitPrice, order.currency) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="authnetTransaction.refunds?.length" class="text-sm">
                            <h3 class="text-slate-300 font-medium mb-2">Prior Refunds on Transaction</h3>
                            <ul class="space-y-1 text-slate-400">
                                <li v-for="refund in authnetTransaction.refunds" :key="refund.refundId">
                                    {{ refund.refundId }} — {{ formatMoney(refund.refundAmount, order.currency) }}
                                    <span v-if="refund.refundDate">({{ refund.refundDate }})</span>
                                </li>
                            </ul>
                        </div>
                    </template>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
