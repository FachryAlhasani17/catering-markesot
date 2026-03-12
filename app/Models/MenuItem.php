<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id','name','description','price',
        'unit','image','is_available','is_featured','min_order_qty','notes',
    ];

    protected $casts = [
        'price'         => 'decimal:2',
        'is_available'  => 'boolean',
        'is_featured'   => 'boolean',
        'min_order_qty' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

}
