<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman Login (Bagian 1: Login & Dashboard pada flowchart).
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses login sesuai flowchart:
     * "Sistem Cek: Nama Terdaftar & Sudah Diberi Akses oleh Admin?"
     *  - Tidak -> Tampilkan Pesan Error
     *  - Ya    -> Masuk ke Dashboard Client
     *
     * Catatan: redirect setelah login SEMENTARA diarahkan kembali ke
     * Landing Page dengan pesan sukses, karena halaman Dashboard belum
     * dikerjakan. Nanti tinggal ganti baris redirect di akhir method ini.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('nama_lengkap', $credentials['nama_lengkap'])
            ->where('role', 'siswa')
            ->first();

        // Nama tidak ditemukan sama sekali
        if (! $user) {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Nama tidak ditemukan. Pastikan kamu sudah mendaftar melalui menu "Daftar Sekarang".',
            ]);
        }

        // Nama ada, tapi admin belum memberikan akses (masih pending verifikasi)
        if ($user->isPendingVerifikasi()) {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Akun kamu belum diberikan akses oleh Admin. Silakan tunggu konfirmasi via WhatsApp/Email maksimal 1x24 jam.',
            ]);
        }

        if ($user->status_akun === 'nonaktif') {
            throw ValidationException::withMessages([
                'nama_lengkap' => 'Akun kamu saat ini nonaktif. Silakan hubungi Admin Haoyou.',
            ]);
        }

        // Cek password
        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password yang kamu masukkan salah.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // TODO: setelah halaman Dashboard dibuat, ganti redirect di bawah ini
        // menjadi: return redirect()->route('dashboard');
        return redirect()
            ->route('landing')
            ->with('status', 'Login berhasil! Selamat datang kembali, ' . $user->nama_lengkap . '.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}