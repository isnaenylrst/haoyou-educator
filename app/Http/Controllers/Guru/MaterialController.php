<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    /*
    | Guru hanya BISA LIHAT & DOWNLOAD materi + silabus yang sudah
    | disiapkan Kurikulum. Tidak ada upload/edit/hapus di sisi Guru.
    */

    public function index()
    {
        $materials = Material::with([
                'level.category',
                'level.program',
                'uploader',
                'vocabularies',
            ])
            ->orderBy('level_id')
            ->orderBy('meeting_number')
            ->get();

        $categories = $materials
            ->pluck('level.category.category_name')
            ->filter()
            ->unique()
            ->values();

        return view('guru.materi', compact('materials', 'categories'));
    }

    public function show(Material $material)
    {
        abort_unless($material->file_exists, 404, 'File tidak ditemukan di server.');

        // PDF dibuka di browser; Word/PowerPoint otomatis diunduh
        if (!$material->is_pdf) {
            return $this->download($material);
        }

        return response()->file(
            storage_path('app/public/' . $material->material_file_path),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function download(Material $material)
    {
        if (!$material->file_exists) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        $name = (Str::slug($material->title) ?: 'materi-' . $material->id)
            . '.' . $material->file_extension;

        return response()->download(storage_path('app/public/' . $material->material_file_path), $name);
    }
}