<?php

namespace App\Http\Controllers\CalonSiswa;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use Illuminate\Http\Request;

class PendaftaranController extends Controller
{
    /**
     * Menampilkan halaman pendaftaran
     */
    public function create()
    {
        return view('CalonSiswa.daftar');
    }

    /**
     * Menyimpan pendaftaran calon siswa
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],

        'gender' => [
            'required',
            'in:Male,Female'
        ],

        'birth_date' => [
            'nullable',
            'date'
        ],

        'phone' => [
            'required',
            'string',
            'max:255'
        ],

        'parent_name' => [
            'nullable',
            'string',
            'max:255'
        ],

        'parent_phone' => [
            'nullable',
            'string',
            'max:255'
        ],

        'address' => [
            'nullable',
            'string'
        ],

        'school' => [
            'nullable',
            'string',
            'max:255'
        ],

        'source' => [
            'nullable',
            'string',
            'max:255'
        ],

        'allergy' => [
            'nullable',
            'string'
        ],

        'interested_program' => [
            'required',
            'string',
            'max:255'
        ],

        'available_schedule' => [
            'nullable',
            'string',
            'max:500'
        ],
    ]);

    $candidate = CandidateStudent::create([
        'name' => $validated['name'],
        'gender' => $validated['gender'],
        'birth_date' => $validated['birth_date'] ?? null,
        'phone' => $validated['phone'],

        'parent_name' => $validated['parent_name'] ?? null,
        'parent_phone' => $validated['parent_phone'] ?? null,

        'address' => $validated['address'] ?? null,
        'school' => $validated['school'] ?? null,

        'source' => $validated['source'] ?? 'Website',

        'allergy' => $validated['allergy'] ?? null,

        'interested_program' => $validated['interested_program'],

        'trial_status' => 'Pending',
        'lead_status' => 'Warm',
    ]);

    return redirect()
        ->route('pendaftaran.sukses')
        ->with('candidate_id', $candidate->id);
}
/*
|--------------------------------------------------------------------------
| HALAMAN SUKSES
|--------------------------------------------------------------------------
*/

public function sukses()
{
    return view('CalonSiswa.daftar-sukses');
}
}