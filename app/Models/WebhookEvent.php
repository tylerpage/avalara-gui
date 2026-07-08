<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    use BelongsToDashboard;

    protected $fillable = [
        'dashboard_id',
        'shopware_event_id',
        'event_name',
        'source_url',
        'shopware_shop_id',
        'is_return_related',
        'shopware_order_id',
        'shopware_order_number',
        'shopware_return_id',
        'payload',
        'headers',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'is_return_related' => 'boolean',
            'payload' => 'array',
            'headers' => 'array',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeReturnRelated(Builder $query): Builder
    {
        return $query->where('is_return_related', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForOrder(Builder $query, string $orderId): Builder
    {
        return $query->where('shopware_order_id', $orderId);
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
