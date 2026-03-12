<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number','customer_name','customer_phone','customer_email',
        'customer_address','event_date','event_time','total_pax',
        'subtotal','discount_amount','total_amount',
        'dp_percentage','dp_amount','remaining_amount',
        'status','payment_status','notes','admin_notes',
        'cancellation_reason','confirmed_at','completed_at','cancelled_at',
    ];

    protected $casts = [
        'event_date'       => 'date',
        'subtotal'         => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'dp_percentage'    => 'decimal:2',
        'dp_amount'        => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'confirmed_at'     => 'datetime',
        'completed_at'     => 'datetime',
        'cancelled_at'     => 'datetime',
    ];
    // Status constants
    const STATUS_PENDING   = 'pending';
    const STATUS_DP_PAID   = 'dp_paid';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Helper label status
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'Menunggu Pembayaran DP',
            'dp_paid'   => 'DP Dibayar - Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst($this->status),
        };
    }

    public function isPending(): bool   { return $this->status === self::STATUS_PENDING; }
    public function isConfirmed(): bool { return $this->status === self::STATUS_CONFIRMED; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
}
