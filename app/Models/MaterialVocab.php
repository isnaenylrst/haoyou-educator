<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialVocab extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Nama tabel
    |--------------------------------------------------------------------------
    */

    protected $table = 'materials_vocab';


    /*
    |--------------------------------------------------------------------------
    | Tabel tidak memiliki created_at dan updated_at
    |--------------------------------------------------------------------------
    */

    public $timestamps = false;


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'material_id',

        'order_number',

        'hanzi',

        'pinyin',

        'meaning',

        'example_sentence',

        'notes',

    ];


    /*
    |--------------------------------------------------------------------------
    | Relasi ke Material
    |--------------------------------------------------------------------------
    */

    public function material()
    {
        return $this->belongsTo(
            Material::class,
            'material_id'
        );
    }
}