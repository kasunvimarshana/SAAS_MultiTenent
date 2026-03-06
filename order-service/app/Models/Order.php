<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_number',
        'status',
        'payment_status',
        'payment_id',
        'total_amount',
        'currency',
        'shipping_address',
        'billing_address',
        'notes',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
}
