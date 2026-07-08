<?php

namespace App\Http\Controllers;

use App\Services\WebhookStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookReceiverController extends Controller
{
    public function shopware(Request $request, WebhookStorageService $storage): JsonResponse
    {
        $event = $storage->store($request);

        return response()->json([
            'status' => 'received',
            'id' => $event->id,
            'is_return_related' => $event->is_return_related,
        ]);
    }
}
