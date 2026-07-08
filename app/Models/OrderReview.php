<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDashboard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReview extends Model
{
    use BelongsToDashboard;

    public const OUTCOME_PASS = 'pass';

    public const OUTCOME_NEEDS_WORK = 'needs_work';

    public const OUTCOME_DEFUNCT = 'defunct';

    protected $fillable = [
        'dashboard_id',
        'shopware_order_id',
        'shopware_order_number',
        'review_date',
        'do_not_review',
        'review_outcome',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'do_not_review' => 'boolean',
        ];
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
