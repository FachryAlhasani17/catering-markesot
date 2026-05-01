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
    max-width: 820px;
    margin: 0 auto;
  }
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }
  .page-header h1 {
    font-size: 1.8rem;
    color: var(--maroon);
    margin: 0;
  }
  .header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
  }

  /* ── Tabs ── */
  .order-tabs {
    display: flex;
    gap: 0;
    background: white;
    border-radius: 14px 14px 0 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    overflow: hidden;
    border-bottom: 2px solid #f0f0f0;
  }
  .order-tab-btn {
    flex: 1;
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-light);
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-family: inherit;
  }
  .order-tab-btn.active {
    color: var(--maroon);
    border-bottom-color: var(--maroon);
    background: #fff8f5;
  }
  .order-tab-btn:hover:not(.active) {
    background: #fafafa;
    color: var(--text);
  }
  .badge {
    background: var(--maroon);
    color: white;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.1rem 0.45rem;
    min-width: 18px;
    text-align: center;
  }
  .badge-grey {
    background: #ccc;
  }

  .tab-content-wrapper {
    background: white;
    border-radius: 0 0 14px 14px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    min-height: 200px;
  }
  .tab-pane { display: none; }
  .tab-pane.active {
    display: block;
    animation: fadeIn 0.25s ease;
  }
  @keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }

  /* ── Order Card ── */
  .order-card {
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    transition: box-shadow 0.2s;
  }
  .order-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
  .order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
  }
  .order-id {
    font-weight: 700;
    font-size: 1rem;
    color: var(--text);
  }
  .order-date {
    font-size: 0.82rem;
    color: var(--text-light);
    margin-top: 0.2rem;
  }
  .status-badge {
    padding: 0.35rem 0.8rem;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .status-pending   { background: #fef3c7; color: #b45309; }
  .status-dp_paid   { background: #e0e7ff; color: #4338ca; }
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
    font-size: 0.88rem;
    padding: 0.25rem 0;
    color: var(--text);
  }
  .total-row {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
    font-size: 1rem;
    color: var(--text);
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px dashed #ccc;
  }

  /* ── Tracker ── */
  .tracker {
    display: flex;
    justify-content: space-between;
    margin-top: 1.2rem;
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
    margin: 0 auto 0.4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    transition: all 0.3s;
  }
  .track-label {
    font-size: 0.72rem;
    color: var(--text-light);
    font-weight: 600;
  }
  .track-step.active .track-icon {
    border-color: var(--green);
    background: var(--green);
    color: white;
  }
  .track-step.active .track-label { color: var(--green); }

  /* ── Empty state ── */
  .empty-state {
    text-align: center;
    padding: 3rem 0;
    color: var(--text-light);
  }
  .empty-state-icon { font-size: 3rem; margin-bottom: 1rem; }

  /* ── Cancel Modal ── */
  .cancel-modal-wrapper {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }
  .cancel-modal-wrapper.show {
    display: flex !important;
  }
</style>
</head>
<body>

<div class="container">
  <div class="page-header">
    <h1>Pesanan Saya</h1>
    <div class="header-actions">
      <a href="/" style="text-decoration:none;color:var(--text-light);font-weight:600;font-size:0.85rem;padding:0.5rem 1rem;border:1px solid #ddd;border-radius:8px;transition:0.2s;" onmouseover="this.style.borderColor='var(--maroon)';this.style.color='var(--maroon)';" onmouseout="this.style.borderColor='#ddd';this.style.color='var(--text-light)';">← Kembali ke Beranda</a>
    </div>
  </div>

  <div class="order-tabs">
    <button class="order-tab-btn active" id="tab-btn-active" onclick="switchOrderTab('active')">
      Dalam Proses
      @if($activeOrders->count() > 0)
        <span class="badge">{{ $activeOrders->count() }}</span>
      @endif
    </button>
    <button class="order-tab-btn" id="tab-btn-history" onclick="switchOrderTab('history')">
      Riwayat
      @if($historyOrders->count() > 0)
        <span class="badge badge-grey">{{ $historyOrders->count() }}</span>
      @endif
    </button>
  </div>

  <div class="tab-content-wrapper">

    {{-- ── Tab: Dalam Proses ── --}}
    <div id="tab-active" class="tab-pane active">
      @if($activeOrders->isEmpty())
        <div class="empty-state">
          <div class="empty-state-icon">🍽️</div>
          <p>Tidak ada pesanan yang sedang diproses.</p>
          <a href="/" class="btn-primary" style="display:inline-block;text-decoration:none;margin-top:1rem;">Pesan Sekarang</a>
        </div>
      @else
        @foreach($activeOrders as $order)
          @include('landing._order_card', ['order' => $order, 'showTracker' => true])
        @endforeach
      @endif
    </div>

    {{-- ── Tab: Riwayat ── --}}
    <div id="tab-history" class="tab-pane">
      @if($historyOrders->isEmpty())
        <div class="empty-state">
          <div class="empty-state-icon">📋</div>
          <p>Belum ada riwayat pesanan.</p>
        </div>
      @else
        @foreach($historyOrders as $order)
          @include('landing._order_card', ['order' => $order, 'showTracker' => false])
        @endforeach
      @endif
    </div>

  </div>
</div>

{{-- ── Modal Batal Pesanan ── --}}
<div class="cancel-modal-wrapper" id="cancelModal" onclick="if(event.target===this) closeCancelModal()">
  <div style="background:white;border-radius:14px;padding:1.5rem;width:100%;max-width:400px;box-shadow:0 10px 25px rgba(0,0,0,0.2);animation:fadeIn 0.2s;">
    <h3 style="margin-top:0;margin-bottom:0.5rem;color:var(--text);font-size:1.2rem;">Batalkan Pesanan</h3>
    <p style="font-size:0.85rem;color:var(--text-light);margin-bottom:1.2rem;line-height:1.5;">Apakah Anda yakin ingin membatalkan pesanan ini? Pilih alasan pembatalan di bawah ini.</p>
    
    <form id="cancelForm" method="POST" action="">
      @csrf
      <div style="margin-bottom:1rem;">
        <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:0.4rem;color:var(--text);">Alasan Batal</label>
        <select name="reason" id="cancelReason" onchange="toggleOtherReason()" style="width:100%;padding:0.7rem;border:1px solid #ddd;border-radius:8px;font-family:inherit;font-size:0.9rem;outline:none;">
          <option value="Salah pesan">Salah pesan</option>
          <option value="Berubah pikiran">Berubah pikiran</option>
          <option value="Ingin mengubah alamat/waktu">Ingin mengubah alamat/waktu</option>
          <option value="Lainnya">Lainnya</option>
        </select>
      </div>
      <div id="otherReasonContainer" style="display:none;margin-bottom:1rem;">
        <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:0.4rem;color:var(--text);">Sebutkan Alasan Lainnya</label>
        <textarea name="other_reason" id="otherReason" rows="3" style="width:100%;padding:0.7rem;border:1px solid #ddd;border-radius:8px;font-family:inherit;font-size:0.9rem;outline:none;resize:vertical;"></textarea>
      </div>
      <div style="display:flex;gap:0.8rem;margin-top:1.5rem;">
        <button type="button" onclick="closeCancelModal()" style="flex:1;background:white;border:1px solid #ccc;padding:0.8rem;border-radius:8px;font-weight:600;cursor:pointer;color:var(--text);">Tutup</button>
        <button type="submit" style="flex:1;background:#ef4444;border:none;padding:0.8rem;border-radius:8px;font-weight:600;color:white;cursor:pointer;">Batalkan</button>
      </div>
    </form>
  </div>
</div>

<script>
function batalPesanan(orderId) {
  const modal = document.getElementById('cancelModal');
  const form = document.getElementById('cancelForm');
  if (modal && form) {
    form.action = '/order/' + orderId + '/cancel';
    modal.classList.add('show');
  } else {
    alert("Terjadi kesalahan teknis. Harap refresh halaman.");
  }
}

function closeCancelModal() {
  document.getElementById('cancelModal').classList.remove('show');
  document.getElementById('cancelReason').value = 'Salah pesan';
  toggleOtherReason();
}

function toggleOtherReason() {
  const reason = document.getElementById('cancelReason').value;
  const container = document.getElementById('otherReasonContainer');
  const textarea = document.getElementById('otherReason');
  if (reason === 'Lainnya') {
    container.style.display = 'block';
    textarea.setAttribute('required', 'required');
  } else {
    container.style.display = 'none';
    textarea.removeAttribute('required');
  }
}

function switchOrderTab(tab) {
  document.querySelectorAll('.order-tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-btn-' + tab).classList.add('active');
  document.getElementById('tab-' + tab).classList.add('active');
}
</script>

</body>
</html>
