<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SelfStudySetting;
use Illuminate\Http\Request;

class SelfStudySettingController extends Controller
{
    public function edit()
    {
        return view('owner.self-learning.settings', [
            'setting'  => SelfStudySetting::current(),
            'predikat' => config('selfstudy.predikat', []),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'weight_vocab'  => ['required', 'integer', 'min:0', 'max:100'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'weight_vocab.required'  => 'Bobot Vocab wajib diisi.',
            'passing_score.required' => 'Batas lulus wajib diisi.',
            'passing_score.min'      => 'Batas lulus minimal 1.',
            'passing_score.max'      => 'Batas lulus maksimal 100.',
        ]);

        // total bobot selalu 100
        SelfStudySetting::updateOrCreate(['id' => 1], [
            'weight_vocab'   => $data['weight_vocab'],
            'weight_grammar' => 100 - $data['weight_vocab'],
            'passing_score'  => $data['passing_score'],
            'updated_by'     => auth()->id(),
        ]);

        return back()->with('success', 'Pengaturan nilai Belajar Mandiri berhasil disimpan.');
    }
}