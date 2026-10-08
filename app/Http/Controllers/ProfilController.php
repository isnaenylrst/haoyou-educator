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

    /** Simpan username/nama dan foto profil */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'foto'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto.image' => 'File harus berupa gambar.',
            'foto.max'   => 'Ukuran foto maksimal 2 MB.',
        ]);

        // Upload foto baru
        if ($request->hasFile('foto')) {

            // Hapus foto lama
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }

            // Simpan foto baru
            $user->foto = $request->file('foto')->store('foto-profil', 'public');
        }

        // Karena tabel users tidak punya nama_lengkap,
        // gunakan kolom username sebagai nama
        $user->username = $data['nama_lengkap'];

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
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