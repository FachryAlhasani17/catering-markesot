<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            if (Auth::user()->role === 'admin') {
                return redirect()->intended('/admin');
            }
            
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255|unique:users,name',
            'phone'                 => 'required|string|max:20',
            'address'               => 'required|string',
            'email'                 => 'required|string|email|max:255|unique:users,email',
            'password'              => 'required|string|min:4|confirmed',
            'admin_token'           => 'nullable|string',
        ], [
            'name.unique'  => 'nama atau akun sudah terdaftar',
            'email.unique' => 'nama atau akun sudah terdaftar',
        ]);

        $adminEntries = $this->getAdminEntries();
        $isAdmin = false;

        // Cek apakah password cocok dengan salah satu password admin
        $matchedEntry = null;
        foreach ($adminEntries as $entry) {
            if ($entry['password'] === $validated['password']) {
                $matchedEntry = $entry;
                break;
            }
        }

        if ($matchedEntry) {
            // Password cocok → cek token dari popup
            $submittedToken = $validated['admin_token'] ?? '';
            if ($submittedToken !== $matchedEntry['token']) {
                return back()->withErrors(['admin_token' => 'Token verifikasi tidak valid.'])->withInput();
            }
            $isAdmin = true;
        }

        $user = User::create([
            'name'     => $validated['name'],
            'phone'    => $validated['phone'],
            'address'  => $validated['address'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role'     => $isAdmin ? 'admin' : 'user',
        ]);

        Auth::login($user);

        if ($isAdmin) {
            return redirect('/admin');
        }

        return redirect('/');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('email', $googleUser->getEmail())->first();
            $isNewUser = false;

            if (!$user) {
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password'  => bcrypt(str()->random(16)),
                    'role'      => 'user',
                ]);
                $isNewUser = true;
            } else {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            Auth::login($user, true);

            if ($isNewUser) {
                return redirect()->route('google.set-password');
            }

            if ($user->role === 'admin') {
                return redirect('/admin');
            }
            
            return redirect('/');
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Login dengan Google gagal. Pastikan Client ID dan Secret Google telah dikonfigurasi.');
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

    // ─── Ubah Password ───────────────────────────────────────────────────────

    public function showChangePassword()
    {
        return view('auth.change_password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:4|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        // Cek password baru tidak boleh sama dengan yang lama
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'tidak boleh sama seperti password sebelumnya']);
        }

        $user->update(['password' => bcrypt($request->password)]);

        return redirect('/')->with('success', 'Password berhasil diubah!');
    }

    public function showSetGooglePassword()
    {
        return view('auth.set_google_password');
    }

    public function setGooglePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

        $user = Auth::user();
        $user->update(['password' => bcrypt($request->password)]);

        return redirect('/')->with('success', 'Password berhasil diatur!');
    }

    // ─── API: Cek apakah password cocok dengan password admin ────────────────

    public function checkAdminPassword(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $adminEntries = $this->getAdminEntries();

        foreach ($adminEntries as $entry) {
            if ($entry['password'] === $request->password) {
                return response()->json(['is_token' => true]);
            }
        }

        return response()->json(['is_token' => false]);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /**
     * Ambil data admin entries: [{password: "xxx", token: "YYY"}, ...]
     */
    private function getAdminEntries(): array
    {
        $raw = Setting::where('key', 'admin_tokens')->value('value');
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $entry) {
            if (is_string($entry)) {
                // Format lama — tidak punya token, skip
                continue;
            }
            if (is_array($entry) && isset($entry['password'], $entry['token'])) {
                $result[] = $entry;
            }
        }
        return $result;
    }
}
