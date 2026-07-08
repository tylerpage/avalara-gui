<?php

namespace App\Http\Controllers;

use App\Services\ConnectionConfig;
use App\Services\OrderReviewService;
use App\Services\WebhookStorageService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(OrderReviewService $reviews, WebhookStorageService $webhooks): Response
    {
        return Inertia::render('Dashboard', [
            'reviewCounts' => $reviews->counts(),
            'webhookCounts' => $webhooks->counts(),
            'webhookUrl' => url('/webhooks/shopware'),
        ]);
    }
}
