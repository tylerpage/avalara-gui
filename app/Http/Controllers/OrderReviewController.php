<?php

namespace App\Http\Controllers;

use App\Models\OrderReview;
use App\Services\OrderReviewService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderReviewController extends Controller
{
    public function queue(OrderReviewService $reviews): Response
    {
        $today = today();

        return Inertia::render('Orders/ReviewQueue', [
            'reviews' => $reviews->dueQueue()->map(function (OrderReview $review) use ($today) {
                $scheduleStatus = null;
                if ($review->review_date !== null) {
                    $scheduleStatus = $review->review_date->lt($today) ? 'overdue' : 'due_today';
                }

                return [
                    'orderId' => $review->shopware_order_id,
                    'orderNumber' => $review->shopware_order_number,
                    'reviewDate' => $review->review_date?->toDateString(),
                    'notes' => $review->notes,
                    'reviewOutcome' => $review->review_outcome,
                    'status' => $review->review_outcome ?? $scheduleStatus ?? 'due_today',
                ];
            })->values()->all(),
            'counts' => $reviews->counts(),
        ]);
    }

    public function update(Request $request, string $orderId, OrderReviewService $reviews): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'review_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reviews->setReview(
            $orderId,
            $validated['order_number'],
            Carbon::parse($validated['review_date']),
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Review date saved.');
    }

    public function updateOutcome(Request $request, string $orderId, OrderReviewService $reviews): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'review_outcome' => ['required', Rule::in([
                OrderReview::OUTCOME_PASS,
                OrderReview::OUTCOME_NEEDS_WORK,
                OrderReview::OUTCOME_DEFUNCT,
            ])],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf(fn () => in_array($request->input('review_outcome'), [
                    OrderReview::OUTCOME_NEEDS_WORK,
                    OrderReview::OUTCOME_DEFUNCT,
                ], true)),
            ],
        ]);

        $reviews->setOutcome(
            $orderId,
            $validated['order_number'],
            $validated['review_outcome'],
            $validated['notes'] ?? null,
        );

        $labels = [
            OrderReview::OUTCOME_PASS => 'Pass',
            OrderReview::OUTCOME_NEEDS_WORK => 'Needs work',
            OrderReview::OUTCOME_DEFUNCT => 'Defunct',
        ];

        return back()->with('success', 'Review marked as '.($labels[$validated['review_outcome']] ?? $validated['review_outcome']).'.');
    }

    public function setTomorrow(Request $request, string $orderId, OrderReviewService $reviews): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reviews->setReview(
            $orderId,
            $validated['order_number'],
            today()->addDay(),
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Order scheduled for review tomorrow.');
    }

    public function destroy(string $orderId, OrderReviewService $reviews): RedirectResponse
    {
        $reviews->clearReview($orderId);

        return back()->with('success', 'Review cleared.');
    }

    public function markDoNotReview(Request $request, string $orderId, OrderReviewService $reviews): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reviews->markDoNotReview(
            $orderId,
            $validated['order_number'],
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Order marked as do not review.');
    }
}
