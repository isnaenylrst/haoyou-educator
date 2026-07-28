<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'enrollment_id',
        'invoice_number',
        'invoice_file',
        'payment_stage',
        'total_bill',
        'amount_bill',
        'remaining_bill',
        'payment_method',
        'payment_date',
        'payment_proof',
        'status',
    ];

    protected $casts = [
        'total_bill' => 'decimal:2',
        'amount_bill' => 'decimal:2',
        'remaining_bill' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function enrollment()
    {
        return $this->belongsTo(ClassEnrollment::class, 'enrollment_id');
    }
}