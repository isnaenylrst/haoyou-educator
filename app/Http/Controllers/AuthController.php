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

        // Hanya user dengan status Active yang boleh login
        $credentials['status'] = 'Active';

        if (Auth::attempt(
            $credentials,
            $request->boolean('remember')
        )) {

            $request->session()->regenerate();

            $user = Auth::user();

            // Ambil nama level dari kolom nama_level
            $levelName = $user->level?->nama_level;

            switch ($levelName) {

                case 'Owner':
                    return redirect()->route('owner.dashboard');

                case 'Admin':
                    return redirect()->route('admin.dashboard');

                case 'Teacher':
                    return redirect()->route('teacher.dashboard');

                case 'Student':
                    return redirect()->route('student.dashboard');

                case 'Curriculum':
                    return redirect()->route('curriculum.dashboard');

                default:

                    Auth::logout();

                    return redirect()
                        ->route('login')
                        ->with(
                            'error',
                            'Level pengguna tidak dikenali.'
                        );
            }
        }

        return back()
            ->withInput(
                $request->only('username')
            )
            ->with(
                'error',
                'Username atau Password salah, atau akun tidak aktif.'
            );
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}