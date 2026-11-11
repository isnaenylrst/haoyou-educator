<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\ClassSessionTeacherOffer;
use App\Services\ClassSessionOfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Guru menjawab pengajuan kelas pengganti (satu pertemuan untuk satu siswa) dari halaman Notifikasi.
 * Terima = guru terisi di pertemuan itu (siapa cepat dia dapat), tolak = wajib beri alasan.
 */
class ReplacementOfferController extends Controller
{
    private function teacher()
    {
        $teacher = Auth::user()->teacher;
        abort_if(! $teacher, 403, 'Akun Anda belum terhubung dengan data guru.');

        return $teacher;
    }

    public function respond(Request $request, ClassSession $classSession, ClassSessionOfferService $offers)
    {
        $teacher = $this->teacher();

        $isOffer = ClassSessionTeacherOffer::where('class_session_id', $classSession->id)
            ->where('teacher_id', $teacher->id)->where('status', 'Pending')->exists();

        abort_unless($isOffer, 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['Accepted', 'Rejected'])],
            'note'     => ['nullable', 'required_if:decision,Rejected', 'string', 'max:1000'],
        ], [
            'note.required_if' => 'Isi alasan saat menolak.',
        ]);

        try {
            $data['decision'] === 'Accepted'
                ? $offers->accept($classSession, $teacher)
                : $offers->reject($classSession, $teacher, $data['note']);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', $data['decision'] === 'Accepted'
            ? 'Kelas pengganti diterima dan masuk ke jadwal Anda.'
            : 'Pengajuan kelas pengganti ditolak.');
    }
}