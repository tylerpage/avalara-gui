<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Services\DashboardContext;
use App\Services\WebhookStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookReceiverController extends Controller
{
    public function receive(
        Request $request,
        string $slug,
        WebhookStorageService $storage,
        DashboardContext $dashboards,
    ): JsonResponse {
        $dashboard = Dashboard::query()->where('slug', $slug)->firstOrFail();
        $dashboards->setCurrent($dashboard);

        $event = $storage->store($request);

        return response()->json([
            'status' => 'received',
            'id' => $event->id,
            'dashboard' => $dashboard->slug,
            'is_return_related' => $event->is_return_related,
        ]);
    }

    public function shopware(
        Request $request,
        WebhookStorageService $storage,
        DashboardContext $dashboards,
    ): JsonResponse {
        $dashboards->setCurrent($dashboards->ensureDefault());

        $event = $storage->store($request);

        return response()->json([
            'status' => 'received',
            'id' => $event->id,
            'dashboard' => $dashboards->slug(),
            'is_return_related' => $event->is_return_related,
        ]);
    }
}
