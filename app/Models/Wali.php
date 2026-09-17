<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wali extends Model
{
    protected $table = 'wali';

    protected $fillable = ['siswa_id', 'nama_wali', 'no_whatsapp', 'email'];

    public function siswa()
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}