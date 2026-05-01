<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Atur Password — Markesot</title>
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
  .card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    width: 100%;
    max-width: 450px;
    box-shadow: var(--shadow);
  }
  .card-title {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--maroon);
    margin-bottom: 0.3rem;
  }
  .card-sub {
    color: var(--text-light);
    font-size: 0.88rem;
    margin-bottom: 1.8rem;
  }
  .form-group {
    margin-bottom: 1.1rem;
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
    box-sizing: border-box;
    transition: border-color 0.2s;
  }
  .form-control:focus {
    border-color: var(--gold);
    outline: none;
    background: white;
  }
  .form-control.is-invalid {
    border-color: #ef4444;
    background: #fff5f5;
  }
  .invalid-feedback {
    color: #ef4444;
    font-size: 0.8rem;
    margin-top: 0.3rem;
    display: block;
  }
</style>
</head>
<body>

<div class="card">
  <div class="card-title">Atur Password Baru</div>
  <div class="card-sub">Karena Anda baru saja mendaftar menggunakan Google, silakan atur password untuk akun Anda terlebih dahulu.</div>

  <form action="{{ route('google.set-password.post') }}" method="POST">
    @csrf
    
    <div class="form-group">
      <label for="password">Password</label>
      <input
        type="password"
        name="password"
        id="password"
        class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
        required
        minlength="4"
      >
      @error('password')
        <span class="invalid-feedback">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="password_confirmation">Konfirmasi Password</label>
      <input
        type="password"
        name="password_confirmation"
        id="password_confirmation"
        class="form-control"
        required
        minlength="4"
      >
    </div>

    <button type="submit" class="btn-primary" style="width:100%;margin-top:0.5rem;">
      Simpan Password & Lanjutkan
    </button>
  </form>
</div>

</body>
</html>
