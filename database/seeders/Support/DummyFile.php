<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Menyalin file contoh asli (database/seeders/files/*) ke storage/app/public
 * supaya link Storage::disk('public')->url($path) benar-benar bisa dibuka
 * lewat public/storage (php artisan storage:link).
 *
 * Contoh pakai di Factory:
 *   'file_path' => DummyFile::store('teacher-materials', 'sample.pptx'),
 */
class DummyFile
{
    /**
     * @param  string  $directory   Folder tujuan di dalam disk public, mis. "teacher-materials"
     * @param  string  $sampleName  Nama file contoh di database/seeders/files/, mis. "sample.pdf"
     * @return string  Path relatif untuk disimpan di kolom file_path, mis. "teacher-materials/uuid.pptx"
     */
    public static function store(string $directory, string $sampleName): string
    {
        $source = database_path('seeders/files/' . $sampleName);

        if (! is_file($source)) {
            throw new RuntimeException("File contoh tidak ditemukan: {$source}");
        }

        $extension = pathinfo($sampleName, PATHINFO_EXTENSION);
        $target = trim($directory, '/') . '/' . Str::uuid() . '.' . $extension;

        Storage::disk('public')->put($target, file_get_contents($source));

        return $target;
    }

    /**
     * Hapus folder-folder file dummy sebelum seeding ulang,
     * supaya migrate:fresh --seed tidak meninggalkan file yatim.
     */
    public static function clean(array $directories): void
    {
        foreach ($directories as $directory) {
            Storage::disk('public')->deleteDirectory(trim($directory, '/'));
        }
    }
}