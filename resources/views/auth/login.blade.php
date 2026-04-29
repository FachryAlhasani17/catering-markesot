<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk / Daftar — Markesot</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="{{ asset('css/markesot.css') }}" rel="stylesheet">
<style>
  body {
    background: var(--bg);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 2rem;
  }
  .auth-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    width: 100%;
    max-width: 450px;
    box-shadow: var(--shadow);
  }
  .auth-logo {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.5rem;
    color: var(--maroon);
    margin-bottom: 0.2rem;
    text-align: center;
  }
  .auth-sub {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
    text-align: center;
  }
  .tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #f0f0f0;
  }
  .tab-btn {
    flex: 1;
    background: none;
    border: none;
    padding: 0.8rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-light);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
  }
  .tab-btn.active {
    color: var(--maroon);
    border-bottom: 2px solid var(--maroon);
  }
  .tab-pane {
    display: none;
  }
  .tab-pane.active {
    display: block;
    animation: fadeIn 0.3s ease;
  }
  @keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
  
  .form-group {
    margin-bottom: 1rem;
  }
  .form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 0.3rem;
  }
  .form-control {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 10px;
    font-family: inherit;
    font-size: 0.9rem;
    background: #fafafa;
  }
  .form-control:focus {
    border-color: var(--gold);
    outline: none;
    background: white;
  }
  
  .btn-google {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    width: 100%;
    padding: 1rem;
    background: white;
    border: 1px solid #ddd;
    border-radius: 12px;
    color: var(--text);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: 0.2s ease;
    text-decoration: none;
    margin-bottom: 1.5rem;
  }
  .btn-google:hover {
    background: #f8f8f8;
    border-color: #ccc;
  }
  .btn-google img, .btn-google svg {
    width: 20px;
    height: 20px;
  }
  .divider {
    display: flex;
    align-items: center;
    text-align: center;
    color: var(--text-light);
    font-size: 0.8rem;
    margin-bottom: 1.5rem;
  }
  .divider::before, .divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #ddd;
  }
  .divider:not(:empty)::before { margin-right: .5em; }
  .divider:not(:empty)::after { margin-left: .5em; }

  .alert {
    padding: 1rem;
    background: #fdf4ec;
    color: var(--maroon);
    border-radius: 10px;
    font-size: 0.85rem;
    margin-bottom: 1.5rem;
    text-align: left;
  }
  .back-link {
    display: block;
    text-align: center;
    margin-top: 1.5rem;
    color: var(--text-light);
    text-decoration: none;
    font-size: 0.85rem;
  }
  .back-link:hover {
    color: var(--maroon);
  }
</style>
</head>
<body>

<div class="auth-card">
  <div class="auth-logo">MARKESOT</div>
  <div class="auth-sub">Selamat datang di Kantin Universitas Jember</div>
  
  @if(session('error'))
    <div class="alert">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert">
      <ul style="margin:0;padding-left:1.2rem;">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <a href="{{ route('google.login') }}" class="btn-google">
    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><g><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path><path fill="none" d="M0 0h48v48H0z"></path></g></svg>
    Masuk / Daftar dengan Google
  </a>

  <div class="divider">ATAU</div>

  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('login')">Masuk</button>
    <button class="tab-btn" onclick="switchTab('register')">Daftar</button>
  </div>

  <div id="login-tab" class="tab-pane active">
    <form action="{{ route('login.post') }}" method="POST">
      @csrf
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;">
        <input type="checkbox" name="remember" id="remember"> <label for="remember" style="margin:0;font-weight:400;">Ingat saya</label>
      </div>
      <button type="submit" class="btn-primary" style="width:100%;margin-top:0.5rem;">Masuk</button>
    </form>
  </div>

  <div id="register-tab" class="tab-pane">
    <form action="{{ route('register.post') }}" method="POST">
      @csrf
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
      </div>
      <div class="form-group">
        <label>No. WhatsApp</label>
        <input type="tel" name="phone" class="form-control" required value="{{ old('phone') }}">
      </div>
      <div class="form-group">
        <label>Alamat Lengkap</label>
        <textarea name="address" class="form-control" required rows="2">{{ old('address') }}</textarea>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required minlength="4">
      </div>
      <div class="form-group">
        <label>Ulangi Password</label>
        <input type="password" name="password_confirmation" class="form-control" required minlength="4">
      </div>
      <button type="submit" class="btn-primary" style="width:100%;margin-top:0.5rem;">Daftar Akun Baru</button>
    </form>
  </div>

  <a href="/" class="back-link">← Kembali ke Beranda</a>
</div>

<script>
function switchTab(tabId) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  
  if (tabId === 'login') {
    document.querySelectorAll('.tab-btn')[0].classList.add('active');
    document.getElementById('login-tab').classList.add('active');
  } else {
    document.querySelectorAll('.tab-btn')[1].classList.add('active');
    document.getElementById('register-tab').classList.add('active');
  }
}

// Show register tab if there are validation errors related to registration
@if($errors->has('name') || $errors->has('phone') || $errors->has('address') || $errors->has('password_confirmation'))
  switchTab('register');
@endif
</script>
</body>
</html>
