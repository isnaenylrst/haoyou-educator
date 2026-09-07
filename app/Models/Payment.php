<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'enrollment_id',
        'invoice_number',
        'invoice_file_path',
        'payment_stage',
        'total_bill',
        'amount_paid',
        'remaining_bill',
        'payment_method',
        'payment_date',
        'payment_proof_path',
        'status',
    ];

    protected $casts = [
        'total_bill' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining_bill' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function enrollment()
    {
        return $this->belongsTo(ClassEnrollment::class, 'enrollment_id');
    }
}