<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function index(SettingService $settingService)
    {
        $menus = MenuItem::with('category')->where('is_available', true)->get()->map(function ($q) {
            $notes = json_decode($q->notes, true) ?? [];
            return [
                'id'      => $q->id,
                'name'    => $q->name,
                'emoji'   => $notes['emoji'] ?? '🍽️',
                'price'   => $q->price,
                'cat'     => str_contains(strtolower($q->category?->name ?? ''), 'minuman') ? 'drink' : 'food',
                'harga'   => $notes['harga']   ?? rand(3, 5),
                'rasa'    => $notes['rasa']    ?? rand(3, 5),
                'sehat'   => $notes['sehat']   ?? rand(3, 5),
                'kenyang' => $notes['kenyang'] ?? rand(3, 5),
                'tags'    => $notes['tags']    ?? ['Enak', 'Segar'],
                'desc'    => $q->description   ?? 'Nikmati hidangan lezat dan segar...',
                'image'   => $q->image ? asset('storage/' . $q->image) : null,
            ];
        });

        $dpPercentage = $settingService->dpPercentage();

        return view('landing.index', compact('menus', 'dpPercentage'));
    }

    public function store(Request $request, SettingService $settingService)
    {
        $validated = $request->validate([
            'customer_name'    => 'required|min:3',
            'customer_phone'   => 'required',
            'customer_address' => 'required|min:5',
            'event_date'       => 'required|date|after_or_equal:today',
            'payment_method'   => 'required|in:cash,bank',
            'payment_proof'    => 'required_if:payment_method,bank|nullable|image|max:5120',
            'items'            => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty'      => 'required|integer|min:1',
        ]);

        $isCash = ($validated['payment_method'] === 'cash');

        try {
            DB::beginTransaction();

            $order = new Order();
            $order->customer_name    = $validated['customer_name'];
            $order->customer_phone   = $validated['customer_phone'];
            $order->customer_address = $validated['customer_address'];
            $order->event_date       = $validated['event_date'];
            $order->status           = 'pending';
            // Cash = bayar full (100%) → dp_percentage 100 agar observer menghitung dp_amount = total
            // Bank = bayar DP sesuai setting
            $order->dp_percentage  = $isCash ? 100 : $settingService->dpPercentage();
            $order->total_amount   = 0;
            $order->save(); // Observer auto-generate order_number

            $total = 0;
            foreach ($validated['items'] as $item) {
                $menu     = MenuItem::findOrFail($item['menu_item_id']);
                $subtotal = $menu->price * $item['qty'];
                $total   += $subtotal;

                $order->orderItems()->create([
                    'menu_item_id' => $menu->id,
                    'item_name'    => $menu->name,
                    'item_price'   => $menu->price,
                    'qty'          => $item['qty'],
                    'subtotal'     => $subtotal,
                ]);
            }

            $order->total_amount = $total;
            $order->save(); // Observer recalculates dp_amount & remaining_amount

            if ($isCash) {
                // Cash: catat ekspektasi pembayaran penuh saat pengambilan, tanpa bukti
                $order->payments()->create([
                    'type'           => 'dp',          // admin akan konfirmasi saat cash diterima
                    'amount'         => $total,         // full amount karena dp_percentage = 100
                    'payment_method' => 'cash',
                    'transfer_date'  => today(),
                    'proof_image'    => null,
                    'status'         => 'pending',
                ]);
            } else {
                // Transfer bank: simpan bukti DP
                if ($request->hasFile('payment_proof')) {
                    $path = $request->file('payment_proof')->store('payments', 'public');
                    $order->payments()->create([
                        'type'           => 'dp',
                        'amount'         => $order->dp_amount,
                        'payment_method' => 'transfer',
                        'transfer_date'  => today(),
                        'proof_image'    => $path,
                        'status'         => 'pending',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'order_number'   => $order->order_number,
                'payment_method' => $validated['payment_method'],
                'total_amount'   => $total,
                'dp_amount'      => $order->dp_amount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function bankInfo(SettingService $settingService)
    {
        return response()->json($settingService->bankInfo());
    }
}
