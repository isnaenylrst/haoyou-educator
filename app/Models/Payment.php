<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'program_price_id',
        'admin_id',
        'invoice_number',
        'invoice_pdf',
        'payment_type',
        'total_tagihan',
        'total_bayar',
        'sisa_tagihan',
        'status',
        'tanggal_invoice',
        'jatuh_tempo',
        'catatan',
    ];

    protected $casts = [
        'tanggal_invoice' => 'date',
        'jatuh_tempo' => 'date',
        'total_tagihan' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'sisa_tagihan' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function programPrice()
    {
        return $this->belongsTo(ProgramPrice::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function paymentDetails()
    {
        return $this->hasMany(PaymentDetail::class);
    }
}