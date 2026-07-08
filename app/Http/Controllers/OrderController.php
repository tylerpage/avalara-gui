<?php

namespace App\Http\Controllers;

use App\Services\AuthorizeNetApiService;
use App\Services\AvalaraApiService;
use App\Services\ConnectionConfig;
use App\Services\OrderReviewService;
use App\Services\ShopwareAdminApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderController extends Controller
{
    public function index(Request $request, ShopwareAdminApiService $shopware, OrderReviewService $reviews): Response
    {
        $orderNumber = $request->string('orderNumber')->trim()->toString();
        $page = max(1, (int) $request->query('page', 1));
        $error = null;
        $orders = ['total' => 0, 'data' => []];

        if ($shopware->isConfigured()) {
            try {
                $orders = $shopware->searchOrders(
                    $orderNumber !== '' ? $orderNumber : null,
                    $page,
                    25,
                );

                $orderIds = collect($orders['data'])->pluck('id')->filter()->all();
                $reviewMap = $reviews->getForOrders($orderIds);

                $orders['data'] = collect($orders['data'])->map(function (array $order) use ($reviews, $reviewMap) {
                    $order['review'] = $reviews->serialize($reviewMap[$order['id']] ?? null);

                    return $order;
                })->all();
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'orderNumber' => $orderNumber,
                'page' => $page,
            ],
            'error' => $error,
            'needsConfiguration' => ! $shopware->isConfigured(),
            'reviewCounts' => $reviews->counts(),
        ]);
    }

    public function show(
        string $orderId,
        ShopwareAdminApiService $shopware,
        AvalaraApiService $avalara,
        AuthorizeNetApiService $authnet,
        OrderReviewService $reviews,
    ): Response {
        $error = null;
        $order = null;
        $returnsAvalara = [];
        $returnsAuthnet = [];
        $remainingRefundable = null;
        $avalaraTransaction = null;
        $authnetTransaction = null;
        $avalaraError = null;
        $authnetError = null;
        $review = $reviews->serialize($reviews->getForOrder($orderId));

        if (! $shopware->isConfigured()) {
            return Inertia::render('Orders/Show', [
                'orderId' => $orderId,
                'needsConfiguration' => true,
                'order' => null,
                'review' => $review,
                'returnsAvalara' => [],
                'returnsAuthnet' => [],
                'remainingRefundable' => null,
                'avalaraTransaction' => null,
                'authnetTransaction' => null,
                'avalaraConfigured' => false,
                'error' => null,
                'avalaraError' => null,
                'authnetError' => null,
            ]);
        }

        try {
            $order = $shopware->getOrder($orderId);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }

        $avalaraConfigured = $avalara->isConfigured();

        if ($order !== null) {
            try {
                $returnsAvalara = $shopware->getReturnsAvalaraForOrder($orderId);
            } catch (RuntimeException $e) {
                $returnsAvalara = [];
                $avalaraError = $e->getMessage();
            }

            try {
                $returnsAuthnet = $shopware->getReturnsAuthnetForOrder($orderId);
            } catch (RuntimeException $e) {
                $returnsAuthnet = [];
                $authnetError = $e->getMessage();
            }

            $remainingRefundable = $shopware->getRemainingRefundable($orderId);

            $companyCode = $returnsAvalara[0]['avalara']['companyCode'] ?? ConnectionConfig::avalaraCompanyCode();

            if ($avalaraConfigured && filled($order['orderNumber'])) {
                $returnsAvalara = $avalara->enrichReturnsWithLiveAvalara(
                    $returnsAvalara,
                    $order['orderNumber'],
                    $companyCode,
                );

                try {
                    $avalaraTransaction = $avalara->getTransaction($order['orderNumber'], $companyCode);
                } catch (RuntimeException $e) {
                    $avalaraError = $e->getMessage();
                }
            }

            $transId = $order['authnetTransId'] ?? null;
            if ($authnet->isConfigured() && filled($transId)) {
                try {
                    $authnetTransaction = $authnet->getTransactionDetails($transId);
                } catch (RuntimeException $e) {
                    $authnetError = $e->getMessage();
                }
            }
        }

        return Inertia::render('Orders/Show', [
            'orderId' => $orderId,
            'needsConfiguration' => false,
            'order' => $order,
            'review' => $review,
            'returnsAvalara' => $returnsAvalara,
            'returnsAuthnet' => $returnsAuthnet,
            'remainingRefundable' => $remainingRefundable,
            'avalaraTransaction' => $avalaraTransaction,
            'authnetTransaction' => $authnetTransaction,
            'avalaraConfigured' => $avalaraConfigured ?? false,
            'error' => $error,
            'avalaraError' => $avalaraError,
            'authnetError' => $authnetError,
        ]);
    }
}
