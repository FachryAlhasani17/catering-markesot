<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Order;

class PaymentObserver
{
    public function creating(Payment $payment): void
    {
        $payment->payment_number = 'PAY-' . date('Ymd') . '-'
            . str_pad(Payment::count() + 1, 4, '0', STR_PAD_LEFT);
    }

    public function updated(Payment $payment): void
    {
        // Jika baru diverifikasi, update status order
        if ($payment->isDirty('status') && $payment->status === 'verified') {
            $order = $payment->order;

            // Kurangi remaining_amount
            $order->remaining_amount -= $payment->amount;

            if ($payment->type === 'dp' && $order->status === Order::STATUS_PENDING) {
                $order->status         = Order::STATUS_DP_PAID;
                $order->payment_status = 'dp_paid';
            }

            if ($order->remaining_amount <= 0) {
                $order->remaining_amount = 0;
                $order->payment_status   = 'fully_paid';
                $order->status           = Order::STATUS_CONFIRMED;
                $order->confirmed_at     = now();
            }

            $order->save();
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
}
