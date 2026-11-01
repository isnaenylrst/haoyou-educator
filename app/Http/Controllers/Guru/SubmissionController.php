<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    private function teacher()
    {
        return Auth::user()->teacher;
    }

    public function index(Request $request)
    {
        $teacher = $this->teacher();
        abort_unless($teacher, 403, 'Akses khusus guru.');

        $submissions = Submission::where('teacher_id', $teacher->id)
            ->latest('date')
            ->paginate(10)
            ->withQueryString();

        return view('guru.pengajuan', compact('submissions'));
    }

    public function store(StoreSubmissionRequest $request)
    {
        $teacher = $this->teacher();
        abort_unless($teacher, 403, 'Akses khusus guru.');

        $data = $request->validated();

        $receiptPath = null;
        if ($request->hasFile('receipt_file')) {
            $receiptPath = $request->file('receipt_file')->store('submission-receipts', 'public');
        }

        $photoPath = null;
        if ($request->hasFile('photo_file')) {
            $photoPath = $request->file('photo_file')->store('submission-photos', 'public');
        }

        // Hitung total nominal jika multi-item (nama, qty, harga, total otomatis)
        $items = !empty($data['items']) ? array_values(array_filter($data['items'], fn ($it) => !empty($it['name']))) : null;
        if ($items) {
            foreach ($items as &$it) {
                $qty = !empty($it['qty']) ? (float) $it['qty'] : 1;
                $price = isset($it['price']) && $it['price'] !== '' ? (float) $it['price'] : (!empty($it['amount']) ? (float) $it['amount'] : 0);
                $it['qty'] = $qty;
                $it['price'] = $price;
                $it['amount'] = $qty * $price;
            }
            unset($it);
            $totalAmount = array_sum(array_column($items, 'amount'));
        } else {
            $totalAmount = $data['amount'] ?? 0;
        }

        DB::transaction(function () use ($teacher, $data, $items, $totalAmount, $receiptPath, $photoPath) {
            $submission = Submission::create([
                'teacher_id'   => $teacher->id,
                'created_by'   => Auth::id(),
                'type'         => $data['type'],
                'item_name'    => $data['item_name'],
                'items'        => $items,
                'amount'       => $totalAmount,
                'date'         => $data['date'],
                'description'  => $data['description'] ?? null,
                'receipt_path' => $receiptPath,
                'photo_path'   => $photoPath,
                'status'       => 'Diajukan',
            ]);

            // Kirim notifikasi database ke seluruh tim Kurikulum
            $curriculumUsers = User::whereHas('level', fn ($q) => $q->where('nama_level', 'Curriculum'))->get();
            foreach ($curriculumUsers as $currUser) {
                $currUser->notify(new AppNotification(
                    title: "Pengajuan {$submission->type} Baru",
                    message: "Guru {$teacher->name} mengajukan {$submission->item_name} sebesar {$submission->formatted_amount}.",
                    url: url('/kurikulum/review-pengajuan?tab=pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-receipt'
                ));
            }
        });

        return back()->with('success', 'Pengajuan reimburse / pembelian bahan berhasil dikirimkan ke Kurikulum untuk direview.');
    }
}

