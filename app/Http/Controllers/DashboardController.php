<?php

namespace App\Http\Controllers;

use App\Services\DashboardContext;
use App\Services\OrderReviewService;
use App\Services\WebhookStorageService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(OrderReviewService $reviews, WebhookStorageService $webhooks, DashboardContext $dashboards): Response
    {
        return Inertia::render('Dashboard', [
            'reviewCounts' => $reviews->counts(),
            'webhookCounts' => $webhooks->counts(),
            'webhookUrl' => $dashboards->current()->webhookUrl(),
            'dashboardName' => $dashboards->current()->name,
        ]);
    }
}
