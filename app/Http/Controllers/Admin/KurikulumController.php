<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Tambah / ubah / hapus akun Kurikulum. Tampilan tabelnya ada di halaman Guru
 * (admin/guru/index.blade.php), jadi controller ini tidak punya index/show.
 */
class KurikulumController extends Controller
{
    /**
     * users.level_id untuk akun Kurikulum.
     * Isi angkanya kalau sudah tahu. Kalau dibiarkan null, level diambil dari
     * akun Kurikulum yang sudah ada (lihat levelId()).
     */
    private const CURRICULUM_LEVEL_ID = null;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'required|string|max:255',
            'address'   => 'nullable|string',
            'join_date' => 'nullable|date',
            'status'    => 'required|in:Active,Inactive',
            'username'  => 'required|string|max:255|unique:users,username',
            'password'  => 'required|string|min:6',
        ]);

        $levelId = $this->levelId();

        if ($levelId === null) {
            return redirect()->route('admin.guru.index')->with(
                'error',
                'Level akun Kurikulum belum diketahui. Isi CURRICULUM_LEVEL_ID di KurikulumController.'
            );
        }

        DB::transaction(function () use ($validated, $levelId) {
            $user = User::create([
                'level_id' => $levelId,
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'status'   => $validated['status'],
            ]);

            Curriculum::create([
                'user_id'   => $user->id,
                'name'      => $validated['name'],
                'phone'     => $validated['phone'],
                'address'   => $validated['address'] ?? null,
                'join_date' => $validated['join_date'] ?? null,
                'status'    => $validated['status'],
            ]);
        });

        return redirect()->route('admin.guru.index')->with('success', 'Kurikulum baru berhasil ditambahkan.');
    }

    public function edit(Curriculum $curriculum)
    {
        $curriculum->load('user');

        return response()->json([
            'id'        => $curriculum->id,
            'name'      => $curriculum->name,
            'phone'     => $curriculum->phone,
            'address'   => $curriculum->address,
            'join_date' => optional($curriculum->join_date)->format('Y-m-d'),
            'status'    => $curriculum->status,
            'username'  => $curriculum->user?->username,
        ]);
    }

    public function update(Request $request, Curriculum $curriculum)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'required|string|max:255',
            'address'   => 'nullable|string',
            'join_date' => 'nullable|date',
            'status'    => 'required|in:Active,Inactive',
            'username'  => [
                'required', 'string', 'max:255',
                Rule::unique('users', 'username')->ignore($curriculum->user_id),
            ],
            'password'  => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($validated, $curriculum) {
            $curriculum->update([
                'name'      => $validated['name'],
                'phone'     => $validated['phone'],
                'address'   => $validated['address'] ?? null,
                'join_date' => $validated['join_date'] ?? null,
                'status'    => $validated['status'],
            ]);

            $userData = [
                'username' => $validated['username'],
                'status'   => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            User::where('id', $curriculum->user_id)->update($userData);
        });

        return redirect()->route('admin.guru.index')->with('success', 'Data kurikulum berhasil diperbarui.');
    }

    public function destroy(Curriculum $curriculum)
    {
        try {
            DB::transaction(function () use ($curriculum) {
                $userId = $curriculum->user_id;
                $curriculum->delete();
                User::where('id', $userId)->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('admin.guru.index')->with(
                'error',
                'Kurikulum tidak dapat dihapus karena masih memiliki data terkait. Nonaktifkan saja akun ini jika perlu.'
            );
        }

        return redirect()->route('admin.guru.index')->with('success', 'Data kurikulum berhasil dihapus.');
    }

    private function levelId(): ?int
    {
        return self::CURRICULUM_LEVEL_ID
            ?? User::whereHas('curriculum')->value('level_id');
    }
}