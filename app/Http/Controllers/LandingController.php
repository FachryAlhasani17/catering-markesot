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
        $bestSellerCount = $settingService->bestSellerCount();
        
        $bestSellerIds = \App\Models\OrderItem::select('menu_item_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('menu_item_id')
            ->orderByDesc('total_qty')
            ->take($bestSellerCount > 0 ? $bestSellerCount : 1)
            ->pluck('menu_item_id')
            ->toArray();

        $menus = MenuItem::with('category')
            ->where('is_available', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->get()->map(function ($q) use ($bestSellerIds) {
            $notes = json_decode($q->notes, true) ?? [];
            return [
                'id'      => $q->id,
                'name'    => $q->name,
                'emoji'   => $notes['emoji'] ?? '🍽️',
                'price'   => $q->price,
                'cat'     => str_contains(strtolower($q->category?->name ?? ''), 'minuman') ? 'drink' : 'food',
                'category_name' => $q->category?->name ?? 'Lainnya',
                'harga'   => $notes['harga']   ?? rand(3, 5),
                'rasa'    => $notes['rasa']    ?? rand(3, 5),
                'sehat'   => $notes['sehat']   ?? rand(3, 5),
                'kenyang' => $notes['kenyang'] ?? rand(3, 5),
                'tags'    => $notes['tags']    ?? ['Enak', 'Segar'],
                'desc'    => $q->description   ?? 'Nikmati hidangan lezat dan segar...',
                'image'   => $q->image ? asset('storage/' . $q->image) : null,
                'is_best_seller' => in_array($q->id, $bestSellerIds),
            ];
        });

        $dpPercentage = $settingService->dpPercentage();

        // Hitung pesanan aktif untuk notifikasi
        $activeOrderCount = 0;
        if (auth()->check()) {
            $activeOrderCount = Order::where('customer_email', auth()->user()->email)
                ->whereIn('status', ['pending', 'dp_paid', 'confirmed'])
                ->count();
        }

        return view('landing.index', compact('menus', 'dpPercentage', 'activeOrderCount'));
    }

    public function store(Request $request, SettingService $settingService)
    {
        $rules = [
            'customer_name'    => 'required|min:3',
            'customer_phone'   => 'required',
            'customer_address' => 'required|min:5',
            'event_date'       => 'required|date|after_or_equal:today',
            'payment_method'   => 'required|in:cash,bank',
            'payment_proof'    => 'required_if:payment_method,bank|nullable|file|mimes:png,jpg,jpeg,heic,webp|max:5120',
            'items'            => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty'      => 'required|integer|min:1',
            'items.*.notes'    => 'nullable|string|max:500',
        ];

        if (!auth()->check()) {
            $rules['email']    = 'required|email';
            $rules['password'] = 'required|min:4';
        }

        $validated = $request->validate($rules);

        if (!auth()->check()) {
            $user = \App\Models\User::where('email', $validated['email'])->first();
            if ($user) {
                if (!\Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
                    return response()->json(['error' => 'Password salah untuk email ini.'], 422);
                }
                $user->update([
                    'phone'   => $validated['customer_phone'],
                    'address' => $validated['customer_address'],
                ]);
            } else {
                $user = \App\Models\User::create([
                    'name'     => $validated['customer_name'],
                    'email'    => $validated['email'],
                    'phone'    => $validated['customer_phone'],
                    'address'  => $validated['customer_address'],
                    'password' => bcrypt($validated['password']),
                    'role'     => 'user',
                ]);
            }
            auth()->login($user);
        } else {
            auth()->user()->update([
                'phone'   => $validated['customer_phone'],
                'address' => $validated['customer_address'],
            ]);
        }

        $isCash = ($validated['payment_method'] === 'cash');

        try {
            DB::beginTransaction();

            $order = new Order();
            $order->customer_name    = $validated['customer_name'];
            $order->customer_phone   = $validated['customer_phone'];
            $order->customer_address = $validated['customer_address'];
            $order->customer_email   = auth()->user()->email;
            
            $datetime = \Carbon\Carbon::parse($validated['event_date']);
            $order->event_date       = $datetime->toDateString();
            $order->event_time       = $datetime->toTimeString();
            
            $order->status           = 'pending';
            // Cash = bayar full (100%)
            // Bank = bayar DP sesuai setting, atau full jika bank_pay_full
            $bankPayFull = $request->input('bank_pay_full') === '1';
            $order->dp_percentage  = ($isCash || $bankPayFull) ? 100 : $settingService->dpPercentage();
            $order->total_amount   = 0;
            $order->save(); // Observer auto-generate order_number

            $total = 0;
            foreach ($validated['items'] as $item) {
                $menu     = MenuItem::findOrFail($item['menu_item_id']);
                $subtotal = $menu->price * $item['qty'];
                $total   += $subtotal;

                $order->orderItems()->create([
                    'menu_item_id' => $menu->id,
                    'menu_name'    => $menu->name,
                    'menu_price'   => $menu->price,
                    'quantity'     => $item['qty'],
                    'subtotal'     => $subtotal,
                    'notes'        => $item['notes'] ?? null,
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

    public function myOrders()
    {
        $allOrders = Order::with(['orderItems', 'payments'])
            ->where('customer_email', auth()->user()->email)
            ->orderBy('created_at', 'desc')
            ->get();

        // "Dalam Proses" = belum selesai dan belum dibatalkan
        $activeOrders = $allOrders->whereIn('status', ['pending', 'dp_paid', 'confirmed']);

        // "Riwayat" = sudah selesai atau dibatalkan
        $historyOrders = $allOrders->whereIn('status', ['completed', 'cancelled']);

        return view('landing.my_orders', compact('activeOrders', 'historyOrders'));
    }

    public function cancelOrder(Request $request, $id, \App\Services\OrderService $orderService)
    {
        $order = Order::where('customer_email', auth()->user()->email)
            ->where('id', $id)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return back()->with('error', 'Pesanan tidak bisa dibatalkan karena sudah diproses.');
        }

        $payment = $order->payments()->latest()->first();
        if ($payment && $payment->payment_method !== 'cash') {
            return back()->with('error', 'Pembatalan hanya berlaku untuk metode pembayaran Tunai.');
        }

        $request->validate([
            'reason' => 'required|string',
            'other_reason' => 'required_if:reason,Lainnya|nullable|string|max:255',
        ]);

        $reason = $request->reason === 'Lainnya' ? $request->other_reason : $request->reason;
        
        $orderService->cancel($order, $reason);

        return back()->with('success', 'Pesanan berhasil dibatalkan.');
    }
}
