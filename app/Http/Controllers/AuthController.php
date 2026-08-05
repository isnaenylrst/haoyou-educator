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

        if (Auth::attempt($credentials, $request->filled('remember'))) {

            $request->session()->regenerate();

            $user = Auth::user();

            switch ($user->level->nama_level) {
                case 'Owner':
                    return redirect()->route('owner.dashboard');

                case 'Admin':
                    return redirect()->route('admin.dashboard');

                case 'Curriculum':
                    return redirect()->route('kurikulum.dashboard');

                case 'Teacher':
                    return redirect()->route('teacher.dashboard');

                case 'Student':
                    return redirect()->route('student.dashboard');

                default:
                    Auth::logout();

                    return redirect()
                        ->route('login')
                        ->with('error', 'Level tidak dikenali.');
            }
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