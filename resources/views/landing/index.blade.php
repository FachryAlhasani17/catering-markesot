<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Markesot — Kantin Universitas Jember</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700;800&family=Bebas+Neue&display=swap" rel="stylesheet">
<link href="{{ asset('css/markesot.css') }}" rel="stylesheet">
</head>
<body>

<!-- ═══════════════════════════════════════
     LANDING PAGE
═══════════════════════════════════════ -->

<!-- HERO -->
<section class="hero">
  <div class="hero-circles"><span></span><span></span><span></span></div>
  <div class="hero-content">
    <div class="hero-eyebrow">🎓 Kantin Universitas Jember</div>
    <h1 class="hero-title">MARKESOT</h1>
    <p class="hero-subtitle">Authentic Campus Kitchen</p>
    <p class="hero-desc">"Perut kosong hati meronta, cium aroma langsung tergoda.<br>Markesot bukan nama biasa — rasa masakan bikin jatuh cinta!"</p>
    <div class="hero-btns">
      <button class="btn-gold" onclick="openOrder()">🍽️ Pesan Sekarang</button>
      <button class="btn-dss-hero" onclick="openDSS()">
        <div class="dss-sparkle">🧠</div>
        Bingung mau makan apa?
      </button>
    </div>
  </div>
  <div class="hero-scroll">Scroll</div>
</section>

<!-- STATS -->
<div class="stats-strip">
  <div class="stat-item"><div class="stat-num">5+</div><div class="stat-label">Menu Pilihan</div></div>
  <div class="stat-item"><div class="stat-num">100%</div><div class="stat-label">Bahan Segar</div></div>
  <div class="stat-item"><div class="stat-num">Halal</div><div class="stat-label">Terjamin</div></div>
  <div class="stat-item"><div class="stat-num">Fast</div><div class="stat-label">Penyajian Cepat</div></div>
</div>

<!-- MENU SECTION -->
<section class="food-section" id="menu">
  <div class="section-head sr">
    <div class="section-chip">✦ Menu Andalan ✦</div>
    <h2 class="section-title">Sajian <em>Istimewa</em> Kami</h2>
    <div class="section-rule"></div>
    <p class="section-sub">Setiap hidangan dimasak dengan bumbu pilihan, cinta, dan resep turun-temurun yang bikin kamu selalu kembali lagi.</p>
  </div>
  <div class="food-grid">
    @foreach($menus->where('cat', 'food')->values() as $i => $menu)
      @php
        $delay = ($i * 0.05);
        $bg = [
          'linear-gradient(135deg,#f5e4be,#e8c97a)',
          'linear-gradient(135deg,#fde8c0,#f5b280)',
          'linear-gradient(135deg,#f0e8c0,#d4c070)',
          'linear-gradient(135deg,#fdf0cc,#f0d080)',
          'linear-gradient(135deg,#2a1810,#5c2e14)'
        ][$i % 5];
      @endphp
      <div class="food-card sr" style="transition-delay:{{ $delay }}s">
        <div class="food-card-visual" style="background:{{ $bg }}">
          {{ $menu['emoji'] }}
          @if($i === 0)
            <span class="food-card-tag">⭐ Best Seller</span>
          @endif
        </div>
        <div class="food-card-body">
          <div class="food-card-name">{{ $menu['name'] }}</div>
          <div class="food-card-desc">{{ $menu['desc'] }}</div>
          <div class="food-card-flavor">
            @foreach($menu['tags'] as $tag)
              <span class="flavor-tag">{{ $tag }}</span>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </div>
</section>

<!-- DRINKS -->
<section class="drinks-section">
  <div class="section-head sr">
    <div class="section-chip">✦ Minuman Segar ✦</div>
    <h2 class="section-title" style="color:white">Pelepas <em>Dahaga</em></h2>
    <div class="section-rule"></div>
    <p class="section-sub">Minuman segar pelengkap sempurna — dari bahan pilihan, penuh kesegaran alami.</p>
  </div>
  <div class="drinks-grid sr">
    @foreach($menus->where('cat', 'drink') as $menu)
    <div class="drink-card">
      <span class="drink-icon">{{ $menu['emoji'] }}</span>
      <div class="drink-name">{{ $menu['name'] }}</div>
      <div class="drink-desc">{{ $menu['desc'] }}</div>
    </div>
    @endforeach
  </div>
</section>

<!-- DSS STRIP ← tombol khusus DSS -->
<section class="dss-strip sr">
  <div class="dss-strip-inner">
    <div class="dss-strip-left">
      <div class="dss-strip-tag">🧠 Rekomendasi Cerdas</div>
      <div class="dss-strip-title">Bingung mau<br>makan <em>apa?</em></div>
      <div class="dss-strip-sub">Jawab beberapa pertanyaan singkat dan sistem kami akan merekomendasikan menu yang paling cocok untukmu hari ini — cepat, mudah, dan akurat!</div>
    </div>
    <div class="dss-strip-right">
      <button class="btn-dss-main" onclick="openDSS()">
        <span class="brain">🧠</span>
        Rekomendasiin Menu<br>
        <span style="font-size:.8rem;font-weight:500;opacity:.8">untuk saya!</span>
      </button>
    </div>
  </div>
</section>

<!-- WHY US -->
<section class="why-section">
  <div class="section-head sr">
    <div class="section-chip">✦ Mengapa Markesot ✦</div>
    <h2 class="section-title">Lebih dari Sekadar <em>Makan Siang</em></h2>
    <div class="section-rule"></div>
  </div>
  <div class="why-grid">
    <div class="why-card sr"><div class="why-icon">🌿</div><div class="why-title">Bahan Segar Setiap Hari</div><div class="why-desc">Bahan dipilih setiap pagi dari pasar lokal untuk kesegaran dan cita rasa terbaik.</div></div>
    <div class="why-card sr" style="transition-delay:.1s"><div class="why-icon">👨‍🍳</div><div class="why-title">Masak dengan Hati</div><div class="why-desc">Dimasak to order dengan bumbu rempah asli — bukan instan, bukan frozen.</div></div>
    <div class="why-card sr" style="transition-delay:.2s"><div class="why-icon">✅</div><div class="why-title">100% Halal</div><div class="why-desc">Semua bahan dan proses memasak terjamin halal. Nikmati dengan tenang.</div></div>
    <div class="why-card sr" style="transition-delay:.3s"><div class="why-icon">⚡</div><div class="why-title">Penyajian Cepat</div><div class="why-desc">Pesanan diproses cepat tanpa mengorbankan kualitas dan kehangatan hidangan.</div></div>
  </div>
</section>

<!-- CTA BOTTOM -->
<section class="cta-section">
  <h2 class="cta-title">Sudah Lapar? <em>Yuk Order!</em></h2>
  <p class="cta-sub">Jangan biarkan perut kosong mengganggu harimu. Satu klik, pesanan langsung kami proses!</p>
  <div class="cta-btns">
    <button class="btn-gold" style="font-size:1.05rem;padding:1.1rem 2.8rem;" onclick="openOrder()">🛒 Pesan Sekarang</button>
    <button class="btn-dss-hero" onclick="openDSS()" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);">
      <div class="dss-sparkle">🧠</div> Masih bingung? Coba rekomendasi
    </button>
  </div>
  <div style="margin-top:1rem;">
    <a href="https://wa.me/6208123480411" target="_blank" class="btn-dss-hero" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2);text-decoration:none;">
      📞 Hubungi Kami — 08123480411
    </a>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div><div class="footer-brand">MARKESOT</div><div class="footer-info">Kantin Universitas Jember<br>© 2025 Markesot. All rights reserved.</div></div>
</footer>

<!-- FABs -->
<div class="fabs">
  <button class="fab fab-dss" onclick="openDSS()">
    <div class="fab-dot"></div> Bingung mau makan apa?
  </button>
  <button class="fab fab-order" onclick="openOrder()">
    <div class="fab-dot"></div> Pesan Sekarang
  </button>
</div>


<!-- ═══════════════════════════════════════
     ORDER MODAL
═══════════════════════════════════════ -->
<div class="overlay" id="orderOverlay" onclick="handleOverlayClick(event,'orderOverlay')">
  <div class="sheet" id="orderSheet">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
      <div class="sheet-title" id="orderTitle">Pilih Menu</div>
      <button class="sheet-close" onclick="closeOrder()">✕</button>
    </div>
    <div class="steps-row" id="orderStepsRow"></div>
    <div class="sheet-body" id="orderBody"></div>
  </div>
</div>


<!-- ═══════════════════════════════════════
     DSS MODAL
═══════════════════════════════════════ -->
<div class="overlay" id="dssOverlay" onclick="handleOverlayClick(event,'dssOverlay')">
  <div class="sheet" id="dssSheet">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
      <div class="sheet-title" id="dssTitle">🧠 Rekomendasi Menu</div>
      <button class="sheet-close" onclick="closeDSS()">✕</button>
    </div>
    <div class="dss-progress-wrap" id="dssProgressWrap">
      <div class="dss-prog-header">
        <div class="dss-prog-label" id="dssPLabel">Yuk Mulai!</div>
        <div class="dss-prog-step" id="dssPStep">0 dari 10</div>
      </div>
      <div class="dss-prog-track"><div class="dss-prog-fill" id="dssPFill" style="width:0%"></div></div>
      <div class="dss-prog-dots" id="dssPDots"></div>
    </div>
    <div class="sheet-body" id="dssBody"></div>
  </div>
</div>


<script>
  window.APP_MENUS = {!! json_encode($menus->values()) !!};
  window.DP_PCT = {{ $dpPercentage }};
</script>
<script src="{{ asset('js/markesot.js') }}"></script>
</body>
</html>