<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tagihan kelas pengganti sebelum dibayar. Lihat migrasi create_replacement_bills_table.
 */
class ReplacementBill extends Model
{
    protected $fillable = [
        'class_enrollment_id',
        'session',
        'replacement_amount',
        'home_visit_amount',
        'due_date',
        'invoice_number',
        'issued_at',
        'class_session_id',
    ];

    protected $casts = [
        'session'            => 'array',
        'replacement_amount' => 'float',
        'home_visit_amount'  => 'float',
        'due_date'           => 'date',
        'issued_at'          => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClassEnrollment::class, 'class_enrollment_id')->withTrashed();
    }

    /** Sudah dibayar = sesi pengganti dan pembayarannya sudah dibuat. */
    public function isPaid(): bool
    {
        return $this->class_session_id !== null;
    }

    public function scopeUnpaid($query)
    {
        return $query->whereNull('class_session_id');
    }
}