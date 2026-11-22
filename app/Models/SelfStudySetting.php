<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfStudySetting extends Model
{
    protected $table = 'self_study_settings';

    protected $fillable = ['weight_vocab', 'weight_grammar', 'passing_score', 'updated_by'];

    /** Pengaturan aktif. Kalau tabel/baris belum ada, jatuh ke config/selfstudy.php. */
    public static function current(): array
    {
        $row = null;

        try {
            $row = static::query()->first();
        } catch (\Throwable $e) {
            // tabel belum dimigrasi: pakai config
        }

        return [
            'weight_vocab'   => (int) ($row->weight_vocab   ?? config('selfstudy.weight_vocab', 70)),
            'weight_grammar' => (int) ($row->weight_grammar ?? config('selfstudy.weight_grammar', 30)),
            'passing_score'  => (int) ($row->passing_score  ?? config('selfstudy.passing_score', 75)),
        ];
    }
}