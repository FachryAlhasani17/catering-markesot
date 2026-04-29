<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Markesot — Kantin Universitas Jember</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700;800&family=Bebas+Neue&display=swap" rel="stylesheet">
<link href="{{ asset('css/markesot.css') }}" rel="stylesheet">
<style>
/* ── Cart Badge ── */
.fab-order { position: relative; }
.cart-badge {
    position: absolute; top: -8px; right: -8px;
    background: var(--gold, #d4af37); color: var(--maroon, #800000);
    border-radius: 50%; min-width: 24px; height: 24px;
    display: none; align-items: center; justify-content: center;
    font-size: 0.85rem; font-weight: 800;
    border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.cart-badge.pop { animation: badgePop 0.4s ease; }
@keyframes badgePop {
  0% { transform: scale(1); }
  50% { transform: scale(1.45); }
  100% { transform: scale(1); }
}

/* ── Category Block ── */
.menu-category-block { margin-bottom: 2.5rem; }
.menu-cat-header {
    display: flex; align-items: baseline; gap: 0.7rem;
    padding: 0 max(1.5rem, 5vw); margin-bottom: 1rem;
}
.menu-cat-title {
    font-family: var(--f-head, 'Cormorant Garamond', serif);
    font-size: 1.6rem; font-weight: 700; color: var(--maroon, #800000); margin: 0;
}
.menu-cat-count {
    font-size: 0.82rem; color: #999; font-weight: 500;
}

/* ── Horizontal Scroll ── */
.menu-scroll-wrap {
    overflow: visible; padding: 0 max(1.5rem, 5vw);
}
.menu-scroll-track {
    display: flex; gap: 1rem; overflow-x: auto;
    scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;
    padding-bottom: 1rem; scroll-padding-left: 1rem;
}
.menu-scroll-track::-webkit-scrollbar { height: 4px; }
.menu-scroll-track::-webkit-scrollbar-track { background: transparent; }
.menu-scroll-track::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }

/* ── Menu Card ── */
.m-card {
    flex: 0 0 200px; scroll-snap-align: start;
    background: #fff; border-radius: 16px; overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    display: flex; flex-direction: column;
    transition: transform 0.25s, box-shadow 0.25s;
}
.m-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.10);
}
.m-card-img {
    height: 150px; position: relative;
    background-size: cover; background-position: center;
    display: flex; align-items: center; justify-content: center;
}
.m-card-emoji { font-size: 4rem; }
.m-card-badge {
    position: absolute; top: 8px; left: 8px;
    background: var(--maroon, #800000); color: var(--gold, #d4af37);
    font-size: 0.65rem; font-weight: 800; letter-spacing: 0.03em;
    padding: 0.25rem 0.55rem; border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
}
.m-card-body {
    padding: 0.9rem; display: flex; flex-direction: column; flex-grow: 1;
}
.m-card-name {
    font-weight: 700; font-size: 0.95rem; color: #222;
    margin-bottom: 0.3rem; text-transform: capitalize;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.m-card-desc {
    font-size: 0.75rem; color: #888; line-height: 1.4;
    flex-grow: 1; margin-bottom: 0.7rem;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.m-card-bottom {
    display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
}
.m-card-price {
    font-weight: 800; color: var(--maroon, #800000); font-size: 0.9rem; white-space: nowrap;
}

/* ── Stepper Controls ── */
.landing-stepper { flex-shrink: 0; }
.add-btn-init {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--maroon, #800000); color: #fff;
    border: none; font-size: 1.2rem; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 2px 8px rgba(128,0,0,0.25);
}
.add-btn-init:hover { transform: scale(1.1); box-shadow: 0 4px 12px rgba(128,0,0,0.35); }
.add-btn-init:active { transform: scale(0.92); }
.stepper-controls {
    display: flex; align-items: center;
    border: 1.5px solid var(--maroon, #800000); border-radius: 20px;
    overflow: hidden; height: 32px;
}
.st-minus, .st-plus {
    width: 30px; height: 100%; border: none; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 1rem; transition: background 0.15s;
}
.st-minus { background: #fff; color: var(--maroon, #800000); }
.st-minus:hover { background: #fef2f2; }
.st-plus { background: var(--maroon, #800000); color: #fff; }
.st-plus:hover { background: #6b0000; }
.qty-display {
    min-width: 26px; text-align: center;
    font-weight: 800; font-size: 0.9rem; color: #333;
}

/* ── Responsive ── */
@media(min-width: 768px) {
    .m-card { flex: 0 0 220px; }
    .m-card-img { height: 170px; }
}
@media(min-width: 1024px) {
    .m-card { flex: 0 0 240px; }
    .m-card-img { height: 180px; }
}
</style>
</head>
<body>
@auth
  <div class="user-badge" style="position:fixed;top:1rem;right:1rem;background:rgba(255,255,255,0.9);padding:0.5rem 1rem;border-radius:20px;box-shadow:var(--shadow-sm);z-index:100;font-size:0.85rem;display:flex;align-items:center;gap:0.5rem;backdrop-filter:blur(5px);font-weight:500;">
    <span>👤 {{ auth()->user()->name }}</span>
    <a href="{{ route('my.orders') }}" style="text-decoration:none;color:var(--text);font-weight:600;padding-left:0.5rem;border-left:1px solid #ddd;">Pesanan Saya</a>
    <form action="{{ route('logout') }}" method="POST" style="margin:0;">
      @csrf
      <button type="submit" style="background:none;border:none;color:var(--maroon);font-weight:700;cursor:pointer;font-size:0.8rem;padding-left:0.5rem;border-left:1px solid #ddd;">Logout</button>
    </form>
  </div>
@else
  <div style="position:fixed;top:1rem;right:1rem;z-index:100;">
    <a href="{{ route('login') }}" style="background:white;color:var(--maroon);padding:0.6rem 1.2rem;border-radius:20px;box-shadow:var(--shadow-sm);text-decoration:none;font-weight:700;font-size:0.85rem;display:inline-block;">Masuk / Daftar</a>
  </div>
@endauth

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
    <div class="section-chip">✦ Jelajahi Rasa ✦</div>
    <h2 class="section-title"><em>Menu</em> Kami</h2>
    <div class="section-rule"></div>
    <p class="section-sub">Pilihan hidangan istimewa dan minuman segar, disiapkan dengan bahan terbaik untuk kepuasan Anda.</p>
  </div>

  @php
    $grouped = $menus->groupBy('category_name');
    $catIndex = 0;
  @endphp

  @foreach($grouped as $catName => $catMenus)
    @if($catIndex === 1)
      <div id="menuMoreWrap" style="position:relative; max-height:0; overflow:hidden; transition: max-height 0.6s ease;">
    @endif
    <div class="menu-category-block sr">
      <div class="menu-cat-header">
        <h3 class="menu-cat-title">{{ $catName }}</h3>
        <span class="menu-cat-count">{{ $catMenus->count() }} menu</span>
      </div>
      <div class="menu-scroll-wrap">
        <div class="menu-scroll-track">
          @foreach($catMenus->values() as $i => $menu)
          <div class="m-card" id="mcard-{{ $menu['id'] }}">
            <div class="m-card-img" style="
              @if($menu['image'])
                background-image: url('{{ $menu['image'] }}');
              @else
                background: linear-gradient(135deg,#f5e4be,#e8c97a);
              @endif
            ">
              @if(!$menu['image'])
                <span class="m-card-emoji">{{ $menu['emoji'] }}</span>
              @endif
              @if(!empty($menu['is_best_seller']))
                <span class="m-card-badge">🔥 Best Seller</span>
              @endif
            </div>
            <div class="m-card-body">
              <div class="m-card-name">{{ $menu['name'] }}</div>
              <div class="m-card-desc">{{ Str::limit($menu['desc'], 55) }}</div>
              <div class="m-card-bottom">
                <div class="m-card-price">Rp {{ number_format($menu['price'], 0, ',', '.') }}</div>
                <div class="landing-stepper" id="stepper-{{ $menu['id'] }}">
                  <button class="add-btn-init" onclick="addLandingItem({{ $menu['id'] }})">+</button>
                  <div class="stepper-controls" style="display:none;">
                    <button class="st-minus" onclick="chgQty({{ $menu['id'] }}, -1)">−</button>
                    <span class="qty-display" id="qty-disp-{{ $menu['id'] }}">1</span>
                    <button class="st-plus" onclick="chgQty({{ $menu['id'] }}, 1)">+</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
    @php $catIndex++; @endphp
  @endforeach

  @if($catIndex > 1)
    </div>
    <!-- Gradient overlay + Show More button -->
    <div id="menuFadeOverlay" style="position:relative; margin-top:-3rem; padding-top:3rem; background:linear-gradient(to bottom, rgba(251,248,243,0), rgba(251,248,243,1) 60%); text-align:center; padding-bottom:1rem; z-index:2;">
      <button id="menuToggleBtn" onclick="toggleMenuMore()" style="background:var(--maroon);color:#fff;border:none;padding:0.7rem 2rem;border-radius:25px;font-weight:700;font-size:0.9rem;cursor:pointer;box-shadow:0 4px 12px rgba(128,0,0,0.2);transition:all 0.2s;">
        Lihat Semua Menu ▼
      </button>
    </div>
  @endif
</section>

<script>
function toggleMenuMore() {
  const wrap = document.getElementById('menuMoreWrap');
  const btn = document.getElementById('menuToggleBtn');
  const overlay = document.getElementById('menuFadeOverlay');
  if (!wrap) return;
  const isOpen = wrap.style.maxHeight !== '0px' && wrap.style.maxHeight !== '';
  if (wrap.style.maxHeight === '0px' || wrap.style.maxHeight === '') {
    wrap.style.maxHeight = wrap.scrollHeight + 'px';
    btn.innerHTML = 'Sembunyikan Menu ▲';
    overlay.style.background = 'none';
    overlay.style.marginTop = '0';
    overlay.style.paddingTop = '0.5rem';
  } else {
    wrap.style.maxHeight = '0px';
    btn.innerHTML = 'Lihat Semua Menu ▼';
    overlay.style.background = 'linear-gradient(to bottom, rgba(251,248,243,0), rgba(251,248,243,1) 60%)';
    overlay.style.marginTop = '-3rem';
    overlay.style.paddingTop = '3rem';
  }
}
</script>

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
    <button class="btn-gold" style="font-size:1.05rem;padding:1.1rem 2.8rem;" onclick="openOrder()">🛒 Keranjang</button>
    <button class="btn-dss-hero" onclick="openDSS()" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);">
      <div class="dss-sparkle">🧠</div> Masih bingung? Coba rekomendasi
    </button>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div><div class="footer-brand">MARKESOT</div><div class="footer-info">Kantin Universitas Jember<br>© 2025 Markesot. All rights reserved.</div></div>
  <div class="footer-contact">📞 08123480411</div>
</footer>

<!-- FABs -->
<div class="fabs">
  <button class="fab fab-dss" onclick="openDSS()">
    <div class="fab-dot"></div> Bingung mau makan apa?
  </button>
  <button class="fab fab-order" onclick="openOrder()">
    <div class="fab-dot"></div> 🛒 Keranjang
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
  window.IS_LOGGED_IN = {{ auth()->check() ? 'true' : 'false' }};
  window.USER_NAME = {!! json_encode(auth()->user()->name ?? '') !!};
  window.USER_PHONE = {!! json_encode(auth()->user()->phone ?? '') !!};
  window.USER_ADDRESS = {!! json_encode(auth()->user()->address ?? '') !!};
  window.USER_EMAIL = {!! json_encode(auth()->user()->email ?? '') !!};
  window.LOGIN_URL = "{{ route('login') }}";
  window.GOOGLE_LOGIN_URL = "{{ route('google.login') }}";
</script>
<script src="{{ asset('js/markesot.js') }}"></script>
</body>
</html>