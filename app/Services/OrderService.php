<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;

class OrderService
{
    /**
     * Hitung ulang subtotal dari semua item pesanan,
     * lalu update subtotal, total_amount, dp_amount, remaining_amount.
     */
    public function recalculate(Order $order): void
    {
        $subtotal = $order->orderItems()->sum('subtotal');

        $order->subtotal         = $subtotal;
        $order->discount_amount  = $order->discount_amount ?? 0;
        $order->total_amount     = $subtotal - $order->discount_amount;
        $order->dp_amount        = round($order->total_amount * ($order->dp_percentage / 100), 2);
        $order->remaining_amount = $order->total_amount - $order->dp_amount;

        $order->save();
    }

    /**
     * Konfirmasi pesanan secara manual oleh admin.
     */
    public function confirm(Order $order): void
    {
        $order->update([
            'status'       => Order::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Selesaikan pesanan.
     */
    public function complete(Order $order): void
    {
        $order->update([
            'status'       => Order::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Batalkan pesanan dengan alasan.
     */
    public function cancel(Order $order, string $reason = ''): void
    {
        $order->update([
            'status'               => Order::STATUS_CANCELLED,
            'cancellation_reason'  => $reason,
            'cancelled_at'         => now(),
        ]);
    }
}
