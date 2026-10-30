<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payments';
    public const TYPE_PACKAGE     = 'Package';
    public const TYPE_REPLACEMENT = 'Replacement';
    public const TYPE_HOME_VISIT  = 'Home Visit';

    public const EXTRA_TYPES = [
        self::TYPE_REPLACEMENT,
        self::TYPE_HOME_VISIT,
    ];

    protected $attributes = [
        'payment_type' => self::TYPE_PACKAGE,
    ];

    protected $fillable = [
        'enrollment_id',
        'class_session_id',
        'invoice_number',
        'invoice_file_path',
        'payment_stage',
        'total_bill',
        'amount_paid',
        'remaining_bill',
        'payment_method',
        'payment_date',
        'payment_proof_path',
        'payment_type',
        'status',
    ];

    protected $casts = [
        'total_bill'      => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'remaining_bill'  => 'decimal:2',
        'payment_date'    => 'date',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClassEnrollment::class, 'enrollment_id')
            ->withTrashed();
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class,'class_session_id');
    }

    public function scopePackage($query)
    {
        return $query->where('payment_type',self::TYPE_PACKAGE);
    }

    public function scopeExtra($query)
    {
        return $query->whereIn('payment_type',self::EXTRA_TYPES);
    }

    public function isExtra(): bool
    {
        return in_array($this->payment_type,self::EXTRA_TYPES,true
        );
    }
}