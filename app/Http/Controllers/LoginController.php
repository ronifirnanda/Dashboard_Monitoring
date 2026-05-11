<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()?->role === 'admin') {
                return redirect()->route('admin.settings.index');
            }

            Auth::logout();
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (Auth::user()?->role !== 'admin') {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Akun ini bukan admin. Login admin hanya untuk pengguna dengan role admin.',
                ])->onlyInput('email');
            }

            return redirect()->intended(route('admin.settings.index'))->with('success', 'Login admin berhasil.');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logout berhasil.');
    }
}
