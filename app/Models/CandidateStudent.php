<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateStudent extends Model
{
    use SoftDeletes;

    protected $table = 'candidate_students';

    protected $fillable = [
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'usia',
        'no_hp',
        'email',
        'nama_ortu',
        'no_hp_ortu',
        'alamat',
        'sekolah',
        'sumber',
        'kebutuhan_belajar',
        'available_schedule',
        'alergi',
        'catatan',
        'status_lead',
        'status_trial',
        'tanggal_trial',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_trial' => 'date',
        ];
    }

    // ----- Helper status_lead -----
    public function isConverted(): bool
    {
        return $this->status_lead === 'Converted';
    }

    public function isLost(): bool
    {
        return $this->status_lead === 'Lost';
    }

    // ----- Scope untuk dashboard admin nanti -----
    public function scopeBelumDihubungi($query)
    {
        return $query->where('status_lead', 'Inquiry');
    }

    public function scopeButuhTrial($query)
    {
        return $query->whereIn('status_trial', ['Belum', 'Menunggu']);
    }
}