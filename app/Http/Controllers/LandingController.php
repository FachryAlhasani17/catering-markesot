<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\SettingService;
use App\Services\QrisService;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function index(SettingService $settingService)
    {
        $menus = MenuItem::with('category')->where('is_available', true)->get()->map(function ($q) {
            // Mapping default for DSS parameters if needed, or get from notes 
            $notes = json_decode($q->notes, true) ?? [];
            return [
                'id' => $q->id,
                'name' => $q->name,
                'emoji' => $notes['emoji'] ?? '🍽️',
                'price' => $q->price,
                'cat' => str_contains(strtolower($q->category?->name ?? ''), 'minuman') ? 'drink' : 'food',
                'harga' => $notes['harga'] ?? rand(3, 5),
                'rasa' => $notes['rasa'] ?? rand(3, 5),
                'sehat' => $notes['sehat'] ?? rand(3, 5),
                'kenyang' => $notes['kenyang'] ?? rand(3, 5),
                'tags' => $notes['tags'] ?? ['Enak', 'Segar'],
                'desc' => $q->description ?? 'Nikmati hidangan lezat dan segar...',
                'image' => $q->image ? asset('storage/' . $q->image) : null
            ];
        });

        $dpPercentage = $settingService->dpPercentage();

        return view('landing.index', compact('menus', 'dpPercentage'));
    }

    public function store(Request $request, SettingService $settingService)
    {
        $validated = $request->validate([
            'customer_name'  => 'required|min:3',
            'customer_phone' => 'required',
            'payment_method' => 'required|in:qris,bank',
            'payment_proof'  => 'required|image|max:5120',
            'items'          => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty'    => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $order = new Order();
            $order->customer_name = $validated['customer_name'];
            $order->customer_phone = $validated['customer_phone'];
            $order->status = 'pending';
            $order->dp_percentage = $settingService->dpPercentage();
            $order->total_amount = 0; // Temporarily
            $order->save(); // This triggers Observer to autogen order_number

            $total = 0;
            foreach ($validated['items'] as $item) {
                $menu = MenuItem::find($item['menu_item_id']);
                $subtotal = $menu->price * $item['qty'];
                $total += $subtotal;

                $order->orderItems()->create([
                    'menu_item_id' => $menu->id,
                    'item_name'    => $menu->name,
                    'item_price'   => $menu->price,
                    'qty'          => $item['qty'],
                    'subtotal'     => $subtotal,
                ]);
            }

            // Update total and observer recalculates dp
            $order->total_amount = $total;
            $order->save();

            // Store payment proof
            if ($request->hasFile('payment_proof')) {
                $path = $request->file('payment_proof')->store('payments', 'public');
                $order->payments()->create([
                    'type'           => 'dp',
                    'amount'         => $order->dp_amount,
                    'payment_method' => $validated['payment_method'],
                    'transfer_date'  => today(),
                    'proof_image'    => $path,
                    'status'         => 'pending'
                ]);
            }

            DB::commit();

            return response()->json([
                'order_number' => $order->order_number
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function qris($amount, QrisService $qrisService, SettingService $settingService)
    {
        $qrisString = $settingService->get('payment_qris_string');
        
        if (empty($qrisString)) {
            return response()->json([
                'error' => 'QRIS belum dikonfigurasi, gunakan transfer bank.'
            ]);
        }

        $dynamicQris = $qrisService->generateDynamic($qrisString, (float)$amount);
        $imageUrl = $qrisService->generateQrImage($dynamicQris);

        return response()->json([
            'qr_image' => $imageUrl
        ]);
    }

    public function bankInfo(SettingService $settingService)
    {
        return response()->json($settingService->bankInfo());
    }
}
