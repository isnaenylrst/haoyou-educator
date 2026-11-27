<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Draf konversi calon siswa menjadi siswa (sebelum DP dibayar).
 * Dihapus otomatis oleh ConvertController::store setelah siswa dan pembayaran DP tercatat.
 */
class ConvertDraft extends Model
{
    protected $fillable = [
        'candidate_student_id',
        'selection',
        'payment_scheme',
        'discount',
        'notes',
        'form_manual',
        'invoice_number',
        'invoice_issued_at',
    ];

    protected $casts = [
        'selection'         => 'array',
        'form_manual'       => 'array',
        'discount'          => 'float',
        'invoice_issued_at' => 'datetime',
    ];

    public function candidateStudent()
    {
        return $this->belongsTo(CandidateStudent::class);
    }
}