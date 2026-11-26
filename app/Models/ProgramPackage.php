<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProgramPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'category_id',
        'level_id',
        'package_name',
        'duration_minutes',
        'duration_months',
        'total_meetings',
        'min_students',
        'max_students',
        'price',
        'installment_price',
        'installment_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'installment_count' => 'integer',
    ];
    public function category()
    {
        return $this->belongsTo(ProgramCategory::class, 'category_id');
    }

    public function level()
    {
        return $this->belongsTo(ProgramLevel::class, 'level_id');
    }
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function isUsedByClasses(): bool
    {
        return DB::table('classes')
            ->where('program_package_id', $this->id)
            ->exists();
    }

    /**
     * Jumlah termin default paket ini, atau null kalau paket hanya bisa Lunas.
     * - installment_count terisi: pakai itu (mis. HSK 3: kelas 6 bulan, 2 termin).
     * - data lama (hanya installment_price): 1 termin per bulan = duration_months.
     */
    public function installmentTermCount(): ?int
    {
        if ($this->installment_count) {
            return (int) $this->installment_count;
        }

        if ((float) $this->installment_price > 0) {
            return max((int) $this->duration_months, 1);
        }

        return null;
    }

    public function supportsInstallment(): bool
    {
        return $this->installmentTermCount() !== null;
    }

    /**
     * Jarak antar termin (bulan) = lama kelas / jumlah termin.
     * Contoh: 6 bulan / 2 termin = 3 bulan sekali; 3 bulan / 3 termin = 1 bulan sekali.
     * $termCount bisa diisi kalau admin mengubah jumlah termin untuk satu enrollment.
     */
    public function installmentIntervalMonths(?int $termCount = null): int
    {
        $count  = $termCount ?: $this->installmentTermCount();
        $months = (int) $this->duration_months;

        if (! $count || $months < 1) {
            return 1;
        }

        return max(intdiv($months, $count), 1);
    }

    /**
     * Nominal per termin untuk tampilan: Harga Termin kalau diisi,
     * kalau tidak harga lunas dibagi rata (mis. HSK 1-3).
     */
    public function installmentAmount(): ?float
    {
        $count = $this->installmentTermCount();

        if (! $count) {
            return null;
        }

        return (float) $this->installment_price > 0
            ? (float) $this->installment_price
            : round((float) $this->price / $count);
    }

    public function totalFor(string $scheme): float
    {
        // Harga Termin = nominal PER termin, jadi total = harga termin x jumlah termin.
        // Paket tanpa Harga Termin (HSK 1-3) memakai harga yang sama dengan Lunas.
        if ($scheme === 'Termin' && (float) $this->installment_price > 0) {
            return (float) $this->installment_price * ($this->installmentTermCount() ?? 1);
        }

        return (float) $this->price;
    }
}