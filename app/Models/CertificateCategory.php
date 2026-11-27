<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateCategory extends Model
{
    protected $fillable = ['code', 'name', 'sub', 'skills', 'passing','category'];

    protected $casts = [
        'skills'  => 'array',
        'passing' => 'integer',
        'category' => 'string',
    ];

    /** Nilai maksimal = jumlah komponen x 100. */
    public function getMaxAttribute(): int
    {
        return count($this->skills ?? []) * 100;
    }

    /**
     * Bentuk yang dipakai controller, form, dan PDF:
     * code => ['label', 'sub', 'skills' => [kunci => label], 'max', 'passing']
     */
    public static function map(): array
    {
        return static::orderBy('id')->get()->mapWithKeys(fn (self $c) => [
            $c->code => [
                'label'   => $c->name,
                'sub'     => $c->sub ?: 'MOCK TEST ' . mb_strtoupper($c->name),
                'skills'  => $c->skills ?? [],
                'max'     => $c->max,
                'passing' => $c->passing,
            ],
        ])->all();
    }
}