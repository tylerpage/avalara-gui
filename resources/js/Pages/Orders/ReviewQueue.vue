<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { reviewStatusClass, reviewStatusLabel } from '@/lib/reviewStatus.js';
import { Link } from '@inertiajs/vue3';

defineProps({
    reviews: {
        type: Array,
        default: () => [],
    },
    counts: {
        type: Object,
        default: () => ({ due: 0, today: 0, overdue: 0 }),
    },
});

function formatReviewDate(value) {
    if (!value) return '—';
    return new Date(`${value}T12:00:00`).toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div>
                <Link href="/orders" class="text-sm text-sky-400 hover:underline">← Back to orders</Link>
                <h1 class="mt-2 text-2xl font-semibold">Review Queue</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Orders scheduled for review today or earlier — typically after Authorize.net settlement runs overnight.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                <div class="rounded-lg border border-slate-800 bg-slate-900/40 px-4 py-3">
                    <div class="text-slate-400">Due now</div>
                    <div class="text-2xl font-semibold text-sky-300">{{ counts.due }}</div>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-900/40 px-4 py-3">
                    <div class="text-slate-400">Due today</div>
                    <div class="text-2xl font-semibold text-amber-300">{{ counts.today }}</div>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-900/40 px-4 py-3">
                    <div class="text-slate-400">Overdue</div>
                    <div class="text-2xl font-semibold text-red-300">{{ counts.overdue }}</div>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-900/40 px-4 py-3">
                    <div class="text-slate-400">Needs work</div>
                    <div class="text-2xl font-semibold text-orange-300">{{ counts.needs_work ?? 0 }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-900/80 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Order #</th>
                            <th class="px-4 py-3 font-medium">Review Date</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in reviews"
                            :key="item.orderId"
                            class="border-t border-slate-800 hover:bg-slate-900/50"
                        >
                            <td class="px-4 py-3">
                                <Link :href="`/orders/${item.orderId}`" class="text-sky-400 hover:underline">
                                    {{ item.orderNumber }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ formatReviewDate(item.reviewDate) }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs', reviewStatusClass(item.status)]">
                                    {{ reviewStatusLabel(item.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ item.notes || '—' }}</td>
                        </tr>
                        <tr v-if="reviews.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                No orders due for review. Set a review date on an order detail page after processing a return.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
