<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
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
}
