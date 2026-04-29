<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Saya — Markesot</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="{{ asset('css/markesot.css') }}" rel="stylesheet">
<style>
  body {
    background: var(--bg);
    min-height: 100vh;
    padding: 2rem;
  }
  .container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: var(--shadow);
  }
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
  }
  .header h1 {
    font-size: 1.8rem;
    color: var(--maroon);
    margin: 0;
  }
  .order-card {
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
  }
  .order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }
  .order-id {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text);
  }
  .order-date {
    font-size: 0.85rem;
    color: var(--text-light);
  }
  .status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
  }
  .status-pending { background: #fef3c7; color: #b45309; }
  .status-dp_paid { background: #e0e7ff; color: #4338ca; }
  .status-confirmed { background: #d1fae5; color: #047857; }
  .status-completed { background: #dcfce7; color: #166534; }
  .status-cancelled { background: #fee2e2; color: #b91c1c; }
  
  .items-list {
    background: #f9f9f9;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
  }
  .item-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    padding: 0.3rem 0;
  }
  .total-row {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text);
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px dashed #ccc;
  }
  .tracker {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
    position: relative;
  }
  .tracker::before {
    content: '';
    position: absolute;
    top: 14px;
    left: 10%;
    right: 10%;
    height: 3px;
    background: #eee;
    z-index: 1;
  }
  .track-step {
    text-align: center;
    z-index: 2;
    position: relative;
    flex: 1;
  }
  .track-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: white;
    border: 3px solid #ddd;
    margin: 0 auto 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
  }
  .track-label {
    font-size: 0.75rem;
    color: var(--text-light);
    font-weight: 600;
  }
  .track-step.active .track-icon {
    border-color: var(--green);
    background: var(--green);
    color: white;
  }
  .track-step.active .track-label {
    color: var(--green);
  }
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1>🛒 Pesanan Saya</h1>
    <a href="/" style="text-decoration:none;color:var(--text-light);font-weight:600;">← Kembali</a>
  </div>

  @if($orders->isEmpty())
    <div style="text-align:center;padding:3rem 0;color:var(--text-light);">
      <div style="font-size:3rem;margin-bottom:1rem;">🍽️</div>
      <p>Belum ada pesanan.</p>
      <a href="/" class="btn-primary" style="display:inline-block;text-decoration:none;margin-top:1rem;">Pesan Sekarang</a>
    </div>
  @else
    @foreach($orders as $order)
      <div class="order-card">
        <div class="order-header">
          <div>
            <div class="order-id">{{ $order->order_number }}</div>
            <div class="order-date">Tanggal Acara: {{ \Carbon\Carbon::parse($order->event_date)->translatedFormat('d F Y') }}</div>
          </div>
          <div class="status-badge status-{{ $order->status }}">
            @if($order->status == 'pending')
              ⏳ Nunggu Verifikasi
            @elseif($order->status == 'dp_paid')
              💳 DP Diterima
            @elseif($order->status == 'confirmed')
              👨‍🍳 Sedang Dimasak
            @elseif($order->status == 'completed')
              ✅ Selesai (Bisa diambil/diantar)
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
            <span>Total Bayar</span>
            <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
          </div>
        </div>

        @if($order->status !== 'cancelled')
          <div class="tracker">
            <div class="track-step {{ in_array($order->status, ['pending', 'dp_paid', 'confirmed', 'completed']) ? 'active' : '' }}">
              <div class="track-icon">1</div>
              <div class="track-label">Verifikasi</div>
            </div>
            <div class="track-step {{ in_array($order->status, ['confirmed', 'completed']) ? 'active' : '' }}">
              <div class="track-icon">2</div>
              <div class="track-label">Dimasak</div>
            </div>
            <div class="track-step {{ $order->status === 'completed' ? 'active' : '' }}">
              <div class="track-icon">3</div>
              <div class="track-label">Selesai</div>
            </div>
          </div>
        @endif
      </div>
    @endforeach
  @endif
</div>

</body>
</html>
