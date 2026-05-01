<div class="order-card">
  @php
    $firstPayment = $order->payments->first();
    $paymentLabel = 'Tidak Diketahui';
    $isFullPayment = $order->dp_percentage == 100;

    if ($firstPayment) {
        if ($firstPayment->payment_method === 'cash') {
            $paymentLabel = 'Tunai';
        } elseif ($firstPayment->payment_method === 'transfer') {
            $paymentLabel = $isFullPayment ? 'Transfer (Lunas)' : 'Transfer (DP ' . round($order->dp_percentage) . '%)';
        }
    }
    
    $isDpFlow = (!$isFullPayment && $firstPayment && $firstPayment->payment_method === 'transfer');
  @endphp
  <div class="order-header">
    <div>
      <div class="order-id">{{ $order->order_number }}</div>
      <div class="order-date">Tanggal Acara: {{ \Carbon\Carbon::parse($order->event_date)->translatedFormat('d F Y') }}</div>
      <div class="order-date">Dipesan: {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d F Y, H:i') }}</div>
    </div>
    <div class="status-badge status-{{ $order->status }}">
      @if($order->status == 'pending')
        ⏳ Nunggu Verifikasi
      @elseif($order->status == 'dp_paid')
        💳 DP Diterima
      @elseif($order->status == 'confirmed')
        👨‍🍳 Sedang Dimasak
      @elseif($order->status == 'completed')
        ✅ Selesai
      @else
        ❌ Dibatalkan
      @endif
    </div>
  </div>

  <div class="items-list">
    @foreach($order->orderItems as $item)
      <div class="item-row">
        <span>{{ $item->menu_name }} ×{{ $item->quantity }}</span>
        <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
      </div>
    @endforeach
    <div class="total-row">
      <div style="display: flex; flex-direction: column;">
        <span>Total Bayar</span>
        <span style="font-size: 0.75rem; color: var(--text-light); font-weight: 500; margin-top: 2px;">Metode: {{ $paymentLabel }}</span>
      </div>
      <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
    </div>
  </div>

  @if($firstPayment && $firstPayment->payment_method === 'cash' && $order->status !== 'cancelled' && $order->status !== 'completed')
    <div style="margin-top: 1.2rem; text-align: right; border-top: 1px solid #f0f0f0; padding-top: 1rem; position: relative; z-index: 10;">
      @if($order->status === 'pending')
        <button type="button" onclick="batalPesanan({{ $order->id }})" style="background: white; border: 1px solid #ef4444; color: #ef4444; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: 0.2s; pointer-events: auto; position: relative; z-index: 20;">Batalkan Pesanan</button>
      @else
        <button type="button" disabled style="background: #f5f5f5; border: 1px solid #ddd; color: #aaa; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: not-allowed;">Batalkan Pesanan</button>
        <div style="font-size: 0.75rem; color: #888; margin-top: 0.4rem;">Pesanan sedang diproses dan tidak bisa dibatalkan</div>
      @endif
    </div>
  @endif

  @if(isset($showTracker) && $showTracker && $order->status !== 'cancelled')
    <div class="tracker">
      <div class="track-step {{ in_array($order->status, ['pending', 'dp_paid', 'confirmed', 'completed']) ? 'active' : '' }}">
        <div class="track-icon">1</div>
        <div class="track-label">Verifikasi</div>
      </div>
      @if($isDpFlow)
        <div class="track-step {{ in_array($order->status, ['dp_paid', 'confirmed', 'completed']) ? 'active' : '' }}">
          <div class="track-icon">2</div>
          <div class="track-label">DP Diterima</div>
        </div>
      @endif
      <div class="track-step {{ in_array($order->status, ['confirmed', 'completed']) ? 'active' : '' }}">
        <div class="track-icon">{{ $isDpFlow ? '3' : '2' }}</div>
        <div class="track-label">Dimasak</div>
      </div>
      <div class="track-step {{ $order->status === 'completed' ? 'active' : '' }}">
        <div class="track-icon">{{ $isDpFlow ? '4' : '3' }}</div>
        <div class="track-label">Selesai</div>
      </div>
    </div>
  @endif
</div>
