<?php

namespace App\Services;

use App\Models\OrderReview;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrderReviewService
{
    public function getForOrder(string $orderId): ?OrderReview
    {
        return OrderReview::query()
            ->where('shopware_order_id', $orderId)
            ->first();
    }

    /**
     * @param  list<string>  $orderIds
     * @return array<string, OrderReview>
     */
    public function getForOrders(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        return OrderReview::query()
            ->whereIn('shopware_order_id', $orderIds)
            ->get()
            ->keyBy('shopware_order_id')
            ->all();
    }

    public function setReview(
        string $orderId,
        string $orderNumber,
        Carbon $reviewDate,
        ?string $notes = null,
    ): OrderReview {
        return OrderReview::query()->updateOrCreate(
            ['shopware_order_id' => $orderId],
            [
                'shopware_order_number' => $orderNumber,
                'review_date' => $reviewDate->toDateString(),
                'do_not_review' => false,
                'review_outcome' => null,
                'notes' => filled($notes) ? trim($notes) : null,
            ],
        );
    }

    public function setOutcome(
        string $orderId,
        string $orderNumber,
        string $outcome,
        ?string $notes = null,
    ): OrderReview {
        $payload = [
            'shopware_order_number' => $orderNumber,
            'review_outcome' => $outcome,
            'do_not_review' => false,
            'notes' => filled($notes) ? trim($notes) : null,
        ];

        if (in_array($outcome, [OrderReview::OUTCOME_PASS, OrderReview::OUTCOME_DEFUNCT], true)) {
            $payload['review_date'] = null;
        }

        return OrderReview::query()->updateOrCreate(
            ['shopware_order_id' => $orderId],
            $payload,
        );
    }

    public function markDoNotReview(
        string $orderId,
        string $orderNumber,
        ?string $notes = null,
    ): OrderReview {
        return OrderReview::query()->updateOrCreate(
            ['shopware_order_id' => $orderId],
            [
                'shopware_order_number' => $orderNumber,
                'review_date' => null,
                'do_not_review' => true,
                'review_outcome' => null,
                'notes' => filled($notes) ? trim($notes) : null,
            ],
        );
    }

    public function clearReview(string $orderId): void
    {
        OrderReview::query()
            ->where('shopware_order_id', $orderId)
            ->delete();
    }

    /**
     * @return Collection<int, OrderReview>
     */
    public function dueQueue(): Collection
    {
        return $this->activeReviewQuery()
            ->orderByRaw('CASE WHEN review_outcome = ? THEN 0 ELSE 1 END', [OrderReview::OUTCOME_NEEDS_WORK])
            ->orderBy('review_date')
            ->orderBy('shopware_order_number')
            ->get();
    }

    /**
     * @return array{due: int, today: int, overdue: int, needs_work: int}
     */
    public function counts(): array
    {
        $today = today();
        $base = $this->activeReviewQuery();

        return [
            'due' => (clone $base)->count(),
            'today' => (clone $base)->where(function (Builder $query) use ($today): void {
                $query->whereDate('review_date', $today)
                    ->orWhere('review_outcome', OrderReview::OUTCOME_NEEDS_WORK);
            })->count(),
            'overdue' => (clone $base)->whereDate('review_date', '<', $today)
                ->where(function (Builder $query): void {
                    $query->whereNull('review_outcome')
                        ->orWhere('review_outcome', '!=', OrderReview::OUTCOME_NEEDS_WORK);
                })
                ->count(),
            'needs_work' => OrderReview::query()
                ->where('review_outcome', OrderReview::OUTCOME_NEEDS_WORK)
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function serialize(?OrderReview $review): ?array
    {
        if ($review === null) {
            return null;
        }

        if ($review->do_not_review) {
            return [
                'reviewDate' => null,
                'notes' => $review->notes,
                'reviewOutcome' => null,
                'scheduleStatus' => null,
                'status' => 'do_not_review',
                'doNotReview' => true,
                'updatedAt' => $review->updated_at?->toDateTimeString(),
            ];
        }

        $reviewOutcome = $review->review_outcome;
        $scheduleStatus = $this->resolveScheduleStatus($review);

        if ($reviewOutcome !== null) {
            return [
                'reviewDate' => $review->review_date?->toDateString(),
                'notes' => $review->notes,
                'reviewOutcome' => $reviewOutcome,
                'scheduleStatus' => $scheduleStatus,
                'status' => $reviewOutcome,
                'doNotReview' => false,
                'updatedAt' => $review->updated_at?->toDateTimeString(),
            ];
        }

        if ($review->review_date === null) {
            return null;
        }

        return [
            'reviewDate' => $review->review_date->toDateString(),
            'notes' => $review->notes,
            'reviewOutcome' => null,
            'scheduleStatus' => $scheduleStatus,
            'status' => $scheduleStatus,
            'doNotReview' => false,
            'updatedAt' => $review->updated_at?->toDateTimeString(),
        ];
    }

    private function resolveScheduleStatus(OrderReview $review): ?string
    {
        if ($review->review_date === null) {
            return null;
        }

        $today = today();
        $reviewDate = $review->review_date;

        if ($reviewDate->lt($today)) {
            return 'overdue';
        }

        if ($reviewDate->isSameDay($today)) {
            return 'due_today';
        }

        return 'scheduled';
    }

    /**
     * @return Builder<OrderReview>
     */
    private function activeReviewQuery(): Builder
    {
        return OrderReview::query()
            ->where('do_not_review', false)
            ->where(function (Builder $query): void {
                $query->whereNull('review_outcome')
                    ->orWhere('review_outcome', OrderReview::OUTCOME_NEEDS_WORK);
            })
            ->where(function (Builder $query): void {
                $query->where('review_outcome', OrderReview::OUTCOME_NEEDS_WORK)
                    ->orWhere(function (Builder $inner): void {
                        $inner->whereNotNull('review_date')
                            ->whereDate('review_date', '<=', today());
                    });
            });
    }
}
