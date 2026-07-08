<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReview extends Model
{
    public const OUTCOME_PASS = 'pass';

    public const OUTCOME_NEEDS_WORK = 'needs_work';

    public const OUTCOME_DEFUNCT = 'defunct';

    protected $fillable = [
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
}
