<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Testimoni;

class LandingController extends Controller
{
    /**
     * Menampilkan Landing Page (Bagian 0 pada flowchart — publik, belum login).
     */
    public function index()
    {
        $programList = Program::orderBy('id')->get();

        return view('landing', [
            'programList' => $programList,
            'testimoniList' => [],
            'whatsappAdmin' => config(
                'services.whatsapp.admin_number',
                env('ADMIN_WHATSAPP_NUMBER')
            ),
        ]);
    }

    /**
     * Tombol "Konsultasi Gratis" di Hero Section ->e redirect ke WhatsApp Admin.
     */
    public function konsultasiGratis()
    {
        $nomor = env('ADMIN_WHATSAPP_NUMBER', '62895352684913');
        $pesan = urlencode('Halo Admin Haoyou, saya ingin konsultasi mengenai kelas Mandarin.');

        return redirect()->away("https://wa.me/{$nomor}?text={$pesan}");
    }
}
