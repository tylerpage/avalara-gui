<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use App\Services\OrderReviewService;
use App\Services\ShopwareAdminApiService;
use App\Services\WebhookStorageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class WebhookController extends Controller
{
    public function index(Request $request, WebhookStorageService $webhooks, OrderReviewService $reviews): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $returnOnly = $request->boolean('returnOnly', false);
        $orderNumber = $request->string('orderNumber')->trim()->toString();

        $query = WebhookEvent::query()->orderByDesc('received_at');

        if ($returnOnly) {
            $query->returnRelated();
        }

        if ($orderNumber !== '') {
            $query->where('shopware_order_number', 'like', '%'.$orderNumber.'%');
        }

        $events = $query->paginate(25, ['*'], 'page', $page)->withQueryString();

        return Inertia::render('Webhooks/Index', [
            'webhooks' => $events,
            'filters' => [
                'returnOnly' => $returnOnly,
                'orderNumber' => $orderNumber,
                'page' => $page,
            ],
            'counts' => $webhooks->counts(),
            'webhookUrl' => url('/webhooks/shopware'),
            'reviewCounts' => $reviews->counts(),
        ]);
    }

    public function show(
        WebhookEvent $webhook,
        ShopwareAdminApiService $shopware,
        OrderReviewService $reviews,
    ): Response {
        $shopwareReturn = null;
        $orderReturns = [];
        $shopwareError = null;

        if ($webhook->is_return_related && $shopware->isConfigured()) {
            try {
                if ($webhook->shopware_return_id) {
                    $shopwareReturn = $shopware->getOrderReturn($webhook->shopware_return_id);
                }

                if ($webhook->shopware_order_id) {
                    $order = $shopware->getOrder($webhook->shopware_order_id);
                    $orderReturns = $order['returns'] ?? [];
                }
            } catch (RuntimeException $e) {
                $shopwareError = $e->getMessage();
            }
        }

        $relatedWebhooks = WebhookEvent::query()
            ->when(
                $webhook->shopware_order_id,
                fn ($query) => $query->forOrder($webhook->shopware_order_id),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->whereKeyNot($webhook->id)
            ->orderByDesc('received_at')
            ->limit(20)
            ->get()
            ->map(fn (WebhookEvent $event) => $this->serializeWebhookSummary($event))
            ->all();

        return Inertia::render('Webhooks/Show', [
            'webhook' => $this->serializeWebhookDetail($webhook),
            'shopwareReturn' => $shopwareReturn,
            'orderReturns' => $orderReturns,
            'relatedWebhooks' => $relatedWebhooks,
            'shopwareError' => $shopwareError,
            'reviewCounts' => $reviews->counts(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeWebhookSummary(WebhookEvent $webhook): array
    {
        return [
            'id' => $webhook->id,
            'eventName' => $webhook->event_name,
            'isReturnRelated' => $webhook->is_return_related,
            'shopwareOrderId' => $webhook->shopware_order_id,
            'shopwareOrderNumber' => $webhook->shopware_order_number,
            'shopwareReturnId' => $webhook->shopware_return_id,
            'receivedAt' => $webhook->received_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeWebhookDetail(WebhookEvent $webhook): array
    {
        return [
            ...$this->serializeWebhookSummary($webhook),
            'shopwareEventId' => $webhook->shopware_event_id,
            'sourceUrl' => $webhook->source_url,
            'shopwareShopId' => $webhook->shopware_shop_id,
            'payload' => $webhook->payload,
            'headers' => $webhook->headers,
        ];
    }
}
