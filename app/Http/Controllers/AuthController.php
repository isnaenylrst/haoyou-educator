<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        // Hanya user yang statusnya Active yang boleh login
        $credentials['status'] = 'Active';

        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            $request->session()->regenerate();

            $user = Auth::user();

            $routeName = $user->homeRouteName();

            if ($routeName) {
                return redirect()->route($routeName);
            }

            // Owner/Admin/level tidak dikenali -> modul belum ada
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Modul untuk level akun ini belum tersedia.');
        }

        return back()
            ->withInput()
            ->with('error', 'Username atau Password salah.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}