<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class GuruController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('username', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('training_status')) {
            $query->where('training_status', $request->training_status);
        }

        $teachers = $query->orderBy('name')->paginate(10)->withQueryString();

        $totalGuru      = Teacher::count();
        $guruAktif      = Teacher::where('status', 'Active')->count();
        $sedangTraining = Teacher::where('training_status', 'Training')->count();
        $lulusTraining  = Teacher::where('training_status', 'Passed')->count();

        return view('admin.guru', compact(
            'teachers',
            'totalGuru',
            'guruAktif',
            'sedangTraining',
            'lulusTraining'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'required|string|max:255',
            'address'          => 'nullable|string',
            'join_date'        => 'nullable|date',
            'training_status'  => 'nullable|in:Training,Passed,Failed',
            'status'           => 'required|in:Active,Inactive',
            'username'         => 'required|string|max:255|unique:users,username',
            'password'         => 'required|string|min:6',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'level_id' => 4, // Teacher
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'status'   => $validated['status'],
            ]);

            Teacher::create([
                'user_id'         => $user->id,
                'name'            => $validated['name'],
                'phone'           => $validated['phone'],
                'address'         => $validated['address'] ?? null,
                'join_date'       => $validated['join_date'] ?? null,
                'training_status' => $validated['training_status'] ?? null,
                'status'          => $validated['status'],
            ]);
        });

        return redirect()->route('admin.guru')->with('success', 'Guru baru berhasil ditambahkan.');
    }

    public function edit(Teacher $teacher)
    {
        $teacher->load('user');

        return response()->json([
            'id'              => $teacher->id,
            'name'            => $teacher->name,
            'phone'           => $teacher->phone,
            'address'         => $teacher->address,
            'join_date'       => optional($teacher->join_date)->format('Y-m-d'),
            'training_status' => $teacher->training_status,
            'status'          => $teacher->status,
            'username'        => $teacher->user?->username,
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'required|string|max:255',
            'address'          => 'nullable|string',
            'join_date'        => 'nullable|date',
            'training_status'  => 'nullable|in:Training,Passed,Failed',
            'status'           => 'required|in:Active,Inactive',
            'username'         => [
                'required', 'string', 'max:255',
                Rule::unique('users', 'username')->ignore($teacher->user_id),
            ],
            'password'         => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($validated, $teacher) {
            $teacher->update([
                'name'            => $validated['name'],
                'phone'           => $validated['phone'],
                'address'         => $validated['address'] ?? null,
                'join_date'       => $validated['join_date'] ?? null,
                'training_status' => $validated['training_status'] ?? null,
                'status'          => $validated['status'],
            ]);

            $userData = [
                'username' => $validated['username'],
                'status'   => $validated['status'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $teacher->user()->update($userData);
        });

        return redirect()->route('admin.guru')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher)
    {
        try {
            DB::transaction(function () use ($teacher) {
                $userId = $teacher->user_id;
                $teacher->delete();
                User::where('id', $userId)->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('admin.guru')->with(
                'error',
                'Guru tidak dapat dihapus karena masih memiliki data terkait (kelas, jurnal mengajar, dll). Nonaktifkan saja guru ini jika perlu.'
            );
        }

        return redirect()->route('admin.guru')->with('success', 'Data guru berhasil dihapus.');
    }

    public function show(Teacher $teacher)
    {
        $teacher->load('user');

        // Dokumen guru (CV, Sertifikat, Foto)
        $documents = Document::where('user_id', $teacher->user_id)
            ->whereIn('document_type', ['CV', 'Teacher Certificate', 'Photo'])
            ->orderByDesc('created_at')
            ->get();

        // Riwayat jurnal mengajar
        // NB: sesuaikan nama relasi 'class' & 'material' kalau di model TeachingJournal beda
        $teachingJournals = $teacher->teachingJournals()
            ->with(['class', 'material'])
            ->orderByDesc('session_date')
            ->paginate(8, ['*'], 'jurnal_page');

        // Materi (PPT) yang pernah diupload guru untuk direview
        $teacherMaterials = $teacher->teacherMaterials()
            ->with('material')
            ->orderByDesc('created_at')
            ->paginate(8, ['*'], 'materi_page');

        return view('admin.gurudetail', compact(
            'teacher',
            'documents',
            'teachingJournals',
            'teacherMaterials'
        ));
    }

    public function storeDocument(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'document_type' => 'required|in:CV,Teacher Certificate,Photo,Other',
            'description'   => 'nullable|string',
            'file'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('file')->store('documents', 'public');

        Document::create([
            'user_id'       => $teacher->user_id,
            'title'         => $validated['title'],
            'document_type' => $validated['document_type'],
            'description'   => $validated['description'] ?? null,
            'file_path'     => $path,
            'visibility'    => 'Private',
            'uploaded_by'   => Auth::id(),
            'uploaded_at'   => now(),
        ]);

        return redirect()
            ->route('admin.guru.show', $teacher->id)
            ->with('success', 'Dokumen berhasil diupload.');
    }

    public function destroyDocument(Document $document)
    {
        $teacher = Teacher::where('user_id', $document->user_id)->first();

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('admin.guru.show', $teacher?->id)
            ->with('success', 'Dokumen berhasil dihapus.');
    }
}