<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_type',
        'document_id',
        'signer_id',
        'signer_role',
        'signature_path',
        'notes',
        'signed_at',
        'created_at',
    ];

    protected $casts = [
        'signed_at'  => 'datetime',
        'created_at' => 'datetime',
    ];

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }
}

