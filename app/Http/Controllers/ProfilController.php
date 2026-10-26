<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    /** Lihat profil + form edit */
    public function index()
    {
        return view('profil.index', [
            'title' => 'Profil Saya',
            'user'  => auth()->user(),
        ]);
    }

    /** Simpan foto profil */
    public function update(Request $request)
    {
        $user = $request->user();

        // Form Edit profil sekarang hanya berisi foto,
        // jadi nama_lengkap tidak lagi divalidasi / disimpan.
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto.required' => 'Pilih foto terlebih dahulu.',
            'foto.image'    => 'File harus berupa gambar.',
            'foto.mimes'    => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.max'      => 'Ukuran foto maksimal 2 MB.',
        ]);

        // Hapus foto lama
        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
        }

        // Simpan foto baru
        $user->foto = $request->file('foto')->store('foto-profil', 'public');
        $user->save();

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    /** Ganti password */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.confirmed'                => 'Konfirmasi password baru tidak sama.',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password berhasil diganti.');
    }

    /** Hapus foto profil */
    public function destroyFoto(Request $request)
    {
        $user = $request->user();

        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);

            $user->foto = null;
            $user->save();
        }

        return back()->with('success', 'Foto profil dihapus.');
    }
}