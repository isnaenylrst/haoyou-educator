<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CertificateCategoryController extends Controller
{
    /** Komponen nilai yang tersedia (sesuai kolom di tabel certificates). */
    public const SKILLS = [
        'listening' => 'Listening',
        'reading'   => 'Reading',
        'writing'   => 'Writing',
        'speaking'  => 'Speaking',
    ];

    public function index()
    {
        $categories = CertificateCategory::orderBy('id')->get();

        $usage = Certificate::selectRaw('category, COUNT(*) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('guru.sertifikat-kategori', [
            'categories' => $categories,
            'usage'      => $usage,
            'skillNames' => self::SKILLS,
        ]);
    }

    public function store(Request $request)
    {
        [$data, $error] = $this->validated($request);

        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        CertificateCategory::create([
            'code' => Str::limit(Str::slug($data['name']), 20, '') . '-' . Str::lower(Str::random(4)),
        ] + $data);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, CertificateCategory $category)
    {
        [$data, $error] = $this->validated($request, $category);

        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        // Komponen nilai tidak boleh diubah jika kategori sudah dipakai sertifikat,
        // karena nilai sertifikat lama akan tidak cocok.
        $used = Certificate::where('category', $category->code)->count();

        $old = array_keys($category->skills ?? []);
        $new = array_keys($data['skills']);
        sort($old);
        sort($new);

        if ($used > 0 && $old !== $new) {
            return back()->with('error', "Komponen nilai tidak bisa diubah karena kategori ini sudah dipakai {$used} sertifikat. Nama, label, subjudul, dan nilai lulus tetap bisa diubah. Untuk komponen berbeda, buat kategori baru.");
        }

        $category->update($data);

        return back()->with('success', 'Kategori diperbarui. Sertifikat yang sudah ada memakai pengaturan baru saat PDF-nya dibuat ulang (Edit lalu simpan).');
    }

    public function destroy(CertificateCategory $category)
    {
        $used = Certificate::where('category', $category->code)->count();

        if ($used > 0) {
            return back()->with('error', "Kategori tidak bisa dihapus karena dipakai {$used} sertifikat.");
        }

        if (CertificateCategory::count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu kategori.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    /** Validasi + susun peta komponen. Kembalikan [data, pesanError]. */
    private function validated(Request $request, ?CertificateCategory $current = null): array
    {
        $request->validate([
            'name'      => [
                'required', 'string', 'max:100',
                Rule::unique('certificate_categories', 'name')->ignore($current?->id),
            ],
            'sub'       => ['nullable', 'string', 'max:150'],
            'passing'   => ['required', 'integer', 'min:1'],
            'skills'    => ['required', 'array', 'min:1'],
            'skills.*'  => ['string', Rule::in(array_keys(self::SKILLS))],
            'labels'    => ['nullable', 'array'],
            'labels.*'  => ['nullable', 'string', 'max:30'],
        ], [
            'skills.required' => 'Pilih minimal satu komponen nilai.',
            'skills.min'      => 'Pilih minimal satu komponen nilai.',
            'name.unique'     => 'Nama kategori sudah dipakai.',
        ]);

        $skills = [];
        foreach (array_keys(self::SKILLS) as $key) {            // urutan tetap
            if (in_array($key, (array) $request->input('skills'), true)) {
                $label = trim((string) $request->input("labels.$key"));
                $skills[$key] = $label !== '' ? $label : self::SKILLS[$key];
            }
        }

        $max     = count($skills) * 100;
        $passing = (int) $request->input('passing');

        if ($passing > $max) {
            return [null, "Nilai lulus minimal ({$passing}) tidak boleh lebih besar dari nilai maksimal ({$max})."];
        }

        return [[
            'name'    => trim($request->input('name')),
            'sub'     => trim((string) $request->input('sub')) ?: null,
            'skills'  => $skills,
            'passing' => $passing,
        ], null];
    }
}