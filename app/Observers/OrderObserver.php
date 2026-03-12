<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Setting;

class OrderObserver
{
    /**
     * Handle the Order "creating" event.
     * Auto-generate order_number dan hitung DP saat pesanan baru dibuat.
     */
    public function creating(Order $order): void
    {
        // Auto-generate order number: ORD-YYYYMMDD-0001
        $todayCount = Order::withTrashed()
            ->whereDate('created_at', today())
            ->count();

        $order->order_number = 'ORD-' . date('Ymd') . '-' . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        // Snapshot dp_percentage dari setting saat pesanan dibuat
        if (empty($order->dp_percentage)) {
            $order->dp_percentage = (float) Setting::where('key', 'dp_percentage')->value('value') ?? 50;
        }

        // Hitung dp_amount dan remaining_amount
        $order->dp_amount        = round($order->total_amount * ($order->dp_percentage / 100), 2);
        $order->remaining_amount = $order->total_amount - $order->dp_amount;
    }

    /**
     * Handle the Order "updating" event.
     * Recalculate DP jika total_amount berubah.
     */
    public function updating(Order $order): void
    {
        if ($order->isDirty('total_amount')) {
            $order->dp_amount        = round($order->total_amount * ($order->dp_percentage / 100), 2);
            $order->remaining_amount = $order->total_amount - $order->dp_amount;
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
