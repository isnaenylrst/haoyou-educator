<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ProgressReportTemplate extends Model
{
    protected $fillable = ['name', 'program_category_id', 'program_level_id', 'private_package_id', 'updated_by'];

    /**
     * Daftar program dinamis yang tersedia di Admin:
     * - Program Categories (Maochong, Jianer, Hudie, Feixiang, HSK Class, HSK Preparation, dll)
     * - Private Packages (Private VIP Class, Private Exclusive Class, Private 2 on 1 jika dibuat di Paket Private)
     */
    public static function availablePrograms(): \Illuminate\Support\Collection
    {
        $categories = ProgramCategory::orderBy('sort_order')->orderBy('category_name')->get();
        $privatePackages = PrivatePackage::orderByDesc('is_active')->orderBy('package_name')->get();

        return collect([
            'categories'      => $categories,
            'privatePackages' => $privatePackages,
        ]);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProgramCategory::class, 'program_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }

    /** Template khusus satu Paket Private (Admin -> Program & Level -> Paket Private). */
    public function privatePackage(): BelongsTo
    {
        return $this->belongsTo(PrivatePackage::class, 'private_package_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(ProgressReportTemplateMeeting::class, 'template_id')->orderBy('meeting_number');
    }

    /**
     * Template untuk sebuah kelas.
     *  - Kelas PRIVATE : cocokkan paket private kelas (VIP / Exclusive ...).
     *  - Kelas reguler : cocokkan level kelas dulu, lalu kategori program level tsb (Feixiang / Maochong / HSK ...).
     * Null bila Kurikulum belum membuatnya.
     */
    public static function forClass(int $classId): ?self
    {
        $class = DB::table('classes')->where('id', $classId)->first(['level_id', 'private_package_id']);
        if (!$class) {
            return null;
        }

        if ($class->private_package_id) {
            $byPackage = static::with('meetings')->where('private_package_id', $class->private_package_id)->first();

            return ($byPackage && $byPackage->meetings->isNotEmpty()) ? $byPackage : null;
        }

        $levelId = $class->level_id;
        if (!$levelId) {
            return null;
        }

        $byLevel = static::with('meetings')->where('program_level_id', $levelId)->first();
        if ($byLevel && $byLevel->meetings->isNotEmpty()) {
            return $byLevel;
        }

        $categoryId = DB::table('program_levels')->where('id', $levelId)->value('category_id');
        if (!$categoryId) {
            return null;
        }

        $byCategory = static::with('meetings')
            ->where('program_category_id', $categoryId)
            ->whereNull('program_level_id')
            ->whereNull('private_package_id')
            ->first();

        return ($byCategory && $byCategory->meetings->isNotEmpty()) ? $byCategory : null;
    }

    /** Nama program/level/paket kelas (untuk pesan ke guru). */
    public static function programLabelForClass(int $classId): string
    {
        $row = DB::table('classes as c')
            ->leftJoin('private_packages as pp', 'pp.id', '=', 'c.private_package_id')
            ->leftJoin('program_levels as pl', 'pl.id', '=', 'c.level_id')
            ->leftJoin('program_categories as pc', 'pc.id', '=', 'pl.category_id')
            ->where('c.id', $classId)
            ->first(['pp.package_name', 'pc.category_name', 'pl.level_name']);

        if ($row?->package_name) {
            return str_starts_with($row->package_name, 'Private') ? $row->package_name : 'Private ' . $row->package_name;
        }

        return $row?->category_name ?: ($row?->level_name ?: 'program ini');
    }
}
