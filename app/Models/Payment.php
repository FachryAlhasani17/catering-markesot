<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'payment_number', 'type', 'amount',
        'payment_method', 'bank_name', 'account_name',
        'transfer_date', 'proof_image', 'status',
        'verified_by', 'verified_at', 'rejection_reason', 'notes',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'transfer_date' => 'date',
        'verified_at'   => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPending():  bool { return $this->status === 'pending'; }
    public function isVerified(): bool { return $this->status === 'verified'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}