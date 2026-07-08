<script setup>
import { computed } from 'vue';

const props = defineProps({
    transaction: {
        type: Object,
        default: null,
    },
    lookup: {
        type: Object,
        default: null,
    },
    configured: {
        type: Boolean,
        default: true,
    },
    emptyMessage: {
        type: String,
        default: 'No Avalara transaction found for this document code.',
    },
});

const resolvedTransaction = computed(() => props.transaction ?? props.lookup?.transaction ?? null);

const documentCode = computed(() => props.lookup?.documentCode ?? props.transaction?.code ?? null);

function formatMoney(amount, currency = 'USD') {
    if (amount == null) return '—';
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amount);
}
</script>

<template>
    <div class="space-y-3">
        <div v-if="!configured" class="text-sm text-slate-500">
            Avalara credentials are not configured.
        </div>

        <div v-else-if="lookup?.error" class="text-sm text-red-300">
            {{ lookup.error }}
        </div>

        <div v-else-if="lookup?.notFound" class="text-sm text-slate-500">
            {{ emptyMessage }}
            <span v-if="documentCode" class="block mt-1 font-mono text-xs text-slate-400">
                Document code: {{ documentCode }}
            </span>
        </div>

        <div v-else-if="!resolvedTransaction" class="text-sm text-slate-500">
            {{ emptyMessage }}
        </div>

        <template v-else>
            <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                <div>
                    <dt class="text-slate-400">Code</dt>
                    <dd class="font-mono text-xs">{{ resolvedTransaction.code }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Type</dt>
                    <dd>{{ resolvedTransaction.type || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Status</dt>
                    <dd>{{ resolvedTransaction.status }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Total Amount</dt>
                    <dd>{{ formatMoney(resolvedTransaction.totalAmount, resolvedTransaction.currencyCode) }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Total Tax</dt>
                    <dd>{{ formatMoney(resolvedTransaction.totalTax, resolvedTransaction.currencyCode) }}</dd>
                </div>
            </dl>

            <div v-if="resolvedTransaction.lines?.length" class="overflow-hidden rounded-lg border border-slate-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-900/80 text-left text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Line</th>
                            <th class="px-4 py-3">Item Code</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Tax</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="line in resolvedTransaction.lines"
                            :key="line.lineNumber"
                            class="border-t border-slate-800"
                        >
                            <td class="px-4 py-3">{{ line.lineNumber }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ line.itemCode }}</td>
                            <td class="px-4 py-3">{{ line.description }}</td>
                            <td class="px-4 py-3">{{ line.quantity }}</td>
                            <td class="px-4 py-3">
                                {{ formatMoney(line.lineAmount, resolvedTransaction.currencyCode) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatMoney(line.taxCalculated ?? line.tax, resolvedTransaction.currencyCode) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
