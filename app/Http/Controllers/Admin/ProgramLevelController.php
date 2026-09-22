<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\ProgramLevel;
use App\Models\ProgramPackage;
use App\Models\PrivatePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgramLevelController extends Controller
{
    public function index(Request $request)
    {
        $programs = Program::with(['categories' => function ($q) {
                $q->orderBy('sort_order');
            }, 'categories.levels' => function ($q) {
                $q->orderBy('sort_order');
            }, 'levels' => function ($q) {
                $q->whereNull('category_id')->orderBy('sort_order');
            }])
            ->orderBy('id')
            ->get();

        $packagesQuery = ProgramPackage::with(['program', 'category', 'level'])
            ->orderBy('program_id')
            ->orderBy('category_id')
            ->orderBy('id');

        if ($request->filled('program_id')) {
            $packagesQuery->where('program_id', $request->program_id);
        }

        $packages = $packagesQuery->get();

        $privatePackages = PrivatePackage::orderBy('id')->get();

        $stats = [
            'total_program'    => $programs->count(),
            'total_category'   => $programs->sum(fn ($p) => $p->categories->count()),
            'total_package'    => ProgramPackage::count() + PrivatePackage::count(),
            'active_package'   => ProgramPackage::where('is_active', true)->count()
                                    + PrivatePackage::where('is_active', true)->count(),
        ];

        return view('admin.programlevel', compact(
            'programs',
            'packages',
            'privatePackages',
            'stats'
        ));
    }

    public function struktur()
    {
        $programs = Program::with(['categories' => function ($q) {
                $q->orderBy('sort_order');
            }, 'categories.levels' => function ($q) {
                $q->orderBy('sort_order');
            }, 'levels' => function ($q) {
                $q->whereNull('category_id')->orderBy('sort_order');
            }])
            ->orderBy('id')
            ->get();

        return view('admin.programstruktur', compact('programs'));
    }

    /* ============================================================
       PROGRAM
    ============================================================ */

    public function storeProgram(Request $request)
    {
        $data = $request->validate([
            'program_name' => ['required', 'string', 'max:255', 'unique:programs,program_name'],
            'description'  => ['nullable', 'string'],
        ]);

        Program::create($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Program baru berhasil ditambahkan.');
    }

    public function editProgram(Program $program)
    {
        return response()->json([
            'id'           => $program->id,
            'program_name' => $program->program_name,
            'description'  => $program->description,
        ]);
    }

    public function updateProgram(Request $request, Program $program)
    {
        $data = $request->validate([
            'program_name' => ['required', 'string', 'max:255', 'unique:programs,program_name,' . $program->id],
            'description'  => ['nullable', 'string'],
        ]);

        $program->update($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Program berhasil diperbarui.');
    }

    public function destroyProgram(Program $program)
    {
        $usedByPackage = ProgramPackage::where('program_id', $program->id)->exists();

        if ($usedByPackage) {
            return back()->with(
                'error',
                'Program "' . $program->program_name . '" tidak bisa dihapus karena masih dipakai di paket reguler. Hapus paketnya terlebih dahulu.'
            );
        }

        // Cascade manual — tidak bergantung sepenuhnya pada foreign key DB.
        foreach ($program->categories as $category) {
            $category->levels()->delete();
        }
        $program->levels()->whereNull('category_id')->delete();
        $program->categories()->delete();
        $program->delete();

        return back()->with('success', 'Program berhasil dihapus beserta seluruh kategori dan level di dalamnya.');
    }

    /* ============================================================
       KATEGORI
    ============================================================ */

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'program_id'    => ['required', 'exists:programs,id'],
            'category_name' => ['required', 'string', 'max:255'],
            'min_age'       => ['nullable', 'integer', 'min:0', 'max:255'],
            'max_age'       => ['nullable', 'integer', 'min:0', 'max:255', 'gte:min_age'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        ProgramCategory::create($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function editCategory(ProgramCategory $category)
    {
        return response()->json([
            'id'             => $category->id,
            'program_id'     => $category->program_id,
            'category_name'  => $category->category_name,
            'min_age'        => $category->min_age,
            'max_age'        => $category->max_age,
            'sort_order'     => $category->sort_order,
        ]);
    }

    public function updateCategory(Request $request, ProgramCategory $category)
    {
        $data = $request->validate([
            'category_name' => ['required', 'string', 'max:255'],
            'min_age'       => ['nullable', 'integer', 'min:0', 'max:255'],
            'max_age'       => ['nullable', 'integer', 'min:0', 'max:255', 'gte:min_age'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        $category->update($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroyCategory(ProgramCategory $category)
    {
        $usedByPackage = ProgramPackage::where('category_id', $category->id)->exists();

        if ($usedByPackage) {
            return back()->with(
                'error',
                'Kategori "' . $category->category_name . '" tidak bisa dihapus karena masih dipakai di paket reguler.'
            );
        }

        $category->levels()->delete();
        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus beserta seluruh level di dalamnya.');
    }

    /* ============================================================
       LEVEL
    ============================================================ */

    public function storeLevel(Request $request)
    {
        $data = $request->validate([
            'program_id'  => ['required', 'exists:programs,id'],
            'category_id' => ['nullable', 'exists:program_categories,id'],
            'level_name'  => ['required', 'string', 'max:255'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        $validator = Validator::make($data, [])->after(function ($validator) use ($data) {
            if (!empty($data['category_id'])) {
                $category = ProgramCategory::find($data['category_id']);
                if ($category && (int) $category->program_id !== (int) $data['program_id']) {
                    $validator->errors()->add('category_id', 'Kategori yang dipilih bukan bagian dari program ini.');
                }
            }
        });
        $validator->validate();

        ProgramLevel::create($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Level baru berhasil ditambahkan.');
    }

    public function editLevel(ProgramLevel $level)
    {
        return response()->json([
            'id'          => $level->id,
            'program_id'  => $level->program_id,
            'category_id' => $level->category_id,
            'level_name'  => $level->level_name,
            'sort_order'  => $level->sort_order,
        ]);
    }

    public function updateLevel(Request $request, ProgramLevel $level)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:program_categories,id'],
            'level_name'  => ['required', 'string', 'max:255'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        $validator = Validator::make($data, [])->after(function ($validator) use ($data, $level) {
            if (!empty($data['category_id'])) {
                $category = ProgramCategory::find($data['category_id']);
                if ($category && (int) $category->program_id !== (int) $level->program_id) {
                    $validator->errors()->add('category_id', 'Kategori yang dipilih bukan bagian dari program ini.');
                }
            }
        });
        $validator->validate();

        $level->update($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Level berhasil diperbarui.');
    }

    public function destroyLevel(ProgramLevel $level)
    {
        $usedByPackage = ProgramPackage::where('level_id', $level->id)->exists();

        if ($usedByPackage) {
            return back()->with(
                'error',
                'Level "' . $level->level_name . '" tidak bisa dihapus karena masih dipakai di paket reguler.'
            );
        }

        $level->delete();

        return back()->with('success', 'Level berhasil dihapus.');
    }

    /* ============================================================
       PAKET REGULER (tidak berubah)
    ============================================================ */

    public function storePackage(Request $request)
    {
        $data = $this->validatePackage($request);

        ProgramPackage::create($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Paket baru berhasil ditambahkan.');
    }

    public function editPackage(ProgramPackage $package)
    {
        return response()->json([
            'id'               => $package->id,
            'program_id'       => $package->program_id,
            'category_id'      => $package->category_id,
            'level_id'         => $package->level_id,
            'package_name'     => $package->package_name,
            'duration_minutes' => $package->duration_minutes,
            'total_meetings'   => $package->total_meetings,
            'min_students'     => $package->min_students,
            'max_students'     => $package->max_students,
            'price'            => (float) $package->price,
            'is_active'        => (bool) $package->is_active,
        ]);
    }

    public function updatePackage(Request $request, ProgramPackage $package)
    {
        $data = $this->validatePackage($request);

        $package->update($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroyPackage(ProgramPackage $package)
    {
        if ($package->isUsedByClasses()) {
            return back()->with(
                'error',
                'Paket "' . $package->package_name . '" tidak bisa dihapus karena masih dipakai di data kelas.'
            );
        }

        $package->delete();

        return back()->with('success', 'Paket berhasil dihapus.');
    }

    private function validatePackage(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'program_id'       => ['required', 'exists:programs,id'],
            'category_id'      => ['nullable', 'exists:program_categories,id'],
            'level_id'         => ['nullable', 'exists:program_levels,id'],
            'package_name'     => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'total_meetings'   => ['required', 'integer', 'min:1'],
            'min_students'     => ['required', 'integer', 'min:1'],
            'max_students'     => ['required', 'integer', 'min:1', 'gte:min_students'],
            'price'            => ['required', 'numeric', 'min:0'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $categoryId = $request->input('category_id');
            $levelId    = $request->input('level_id');
            $programId  = $request->input('program_id');

            if ($categoryId) {
                $category = ProgramCategory::find($categoryId);
                if ($category && (int) $category->program_id !== (int) $programId) {
                    $validator->errors()->add(
                        'category_id',
                        'Kategori yang dipilih bukan bagian dari program ini.'
                    );
                }
            }

            if ($levelId) {
                $level = ProgramLevel::find($levelId);
                if ($level) {
                    if ((int) $level->program_id !== (int) $programId) {
                        $validator->errors()->add(
                            'level_id',
                            'Level yang dipilih bukan bagian dari program ini.'
                        );
                    }
                    if ($categoryId && (int) $level->category_id !== (int) $categoryId) {
                        $validator->errors()->add(
                            'level_id',
                            'Level yang dipilih bukan bagian dari kategori ini.'
                        );
                    }
                }
            }
        });

        $validated = $validator->validate();
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    public function storePrivate(Request $request)
    {
        $data = $this->validatePrivate($request);

        PrivatePackage::create($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Paket private baru berhasil ditambahkan.');
    }

    public function editPrivate(PrivatePackage $privatePackage)
    {
        return response()->json([
            'id'               => $privatePackage->id,
            'package_name'     => $privatePackage->package_name,
            'duration_minutes' => $privatePackage->duration_minutes,
            'total_meetings'   => $privatePackage->total_meetings,
            'min_students'     => $privatePackage->min_students,
            'max_students'     => $privatePackage->max_students,
            'price'            => (float) $privatePackage->price,
            'is_active'        => (bool) $privatePackage->is_active,
        ]);
    }

    public function updatePrivate(Request $request, PrivatePackage $privatePackage)
    {
        $data = $this->validatePrivate($request);

        $privatePackage->update($data);

        return redirect()
            ->route('admin.program-level')
            ->with('success', 'Paket private berhasil diperbarui.');
    }

    public function destroyPrivate(PrivatePackage $privatePackage)
    {
        if ($privatePackage->isUsedByEnrollments()) {
            return back()->with(
                'error',
                'Paket "' . $privatePackage->package_name . '" tidak bisa dihapus karena masih dipakai di data pendaftaran siswa.'
            );
        }

        $privatePackage->delete();

        return back()->with('success', 'Paket private berhasil dihapus.');
    }

    private function validatePrivate(Request $request): array
    {
        $validated = $request->validate([
            'package_name'     => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'total_meetings'   => ['required', 'integer', 'min:1'],
            'min_students'     => ['required', 'integer', 'min:1'],
            'max_students'     => ['required', 'integer', 'min:1', 'gte:min_students'],
            'price'            => ['required', 'numeric', 'min:0'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}