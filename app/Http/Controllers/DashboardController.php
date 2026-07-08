<?php

namespace App\Http\Controllers;

use App\Services\OrderReviewService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(OrderReviewService $reviews): Response
    {
        return Inertia::render('Dashboard', [
            'reviewCounts' => $reviews->counts(),
        ]);
    }
}
