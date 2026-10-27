<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionFee extends Model
{
    protected $table = 'session_fees';

    protected $fillable = [
        'class_session_id',
        'class_enrollment_id',
        'fee_type',
        'amount',
        'status',
        'paid_at',
        'payment_method',
        'payment_proof_path',
        'invoice_number',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClassEnrollment::class, 'class_enrollment_id')->withTrashed();
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'Unpaid');
    }

    public function isPaid(): bool
    {
        return $this->status === 'Paid';
    }
}