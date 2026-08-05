<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialVocab;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    | Menampilkan:
    | - Daftar materi
    | - Daftar kelas
    | - Vocabulary
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $materials = Material::with([
            'classroom.programPackage.program',
            'uploader',
            'vocabularies'
        ])
        ->latest()
        ->get();

        $classes = ClassModel::with([
            'programPackage.program'
        ])
        ->orderBy('class_name')
        ->get();

        return view(
            'kurikulum.materi',
            compact(
                'materials',
                'classes'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    | Menyimpan materi baru
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([

            'class_id' => [
                'required',
                'exists:classes,id',
            ],

            'meeting_number' => [
                'required',
                'integer',
                'min:1',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'syllabus' => [
                'nullable',
                'string',
            ],

            'material_file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx',
                'max:10240',
            ],

            'vocabularies' => [
                'nullable',
                'string',
            ],

        ]);


        try {

            DB::transaction(function () use ($request) {

                /*
                |--------------------------------------------------------------------------
                | Upload File
                |--------------------------------------------------------------------------
                */

                $filePath = $request
                    ->file('material_file')
                    ->store('materials', 'public');


                /*
                |--------------------------------------------------------------------------
                | Simpan Material
                |--------------------------------------------------------------------------
                */

                $material = Material::create([

                    'class_id' => $request->class_id,

                    'uploaded_by' => Auth::id(),

                    'meeting_number' => $request->meeting_number,

                    'title' => $request->title,

                    'syllabus' => $request->syllabus,

                    'material_file_path' => $filePath,

                ]);


                /*
                |--------------------------------------------------------------------------
                | Simpan Vocabulary
                |--------------------------------------------------------------------------
                */

                $this->saveVocabularies(
                    $material,
                    $request->vocabularies
                );

            });


            return redirect()
                ->route('kurikulum.materi')
                ->with(
                    'success',
                    'Materi berhasil ditambahkan.'
                );


        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menambahkan materi: ' . $e->getMessage()
                );

        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    | Digunakan oleh MODAL EDIT di materi.blade.php
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Material $material)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'class_id' => [
                'required',
                'exists:classes,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Meeting tidak boleh sama dalam kelas yang sama
            |--------------------------------------------------------------------------
            */

            'meeting_number' => [
                'required',
                'integer',
                'min:1',

                Rule::unique('materials', 'meeting_number')
                    ->where(function ($query) use ($request) {

                        return $query->where(
                            'class_id',
                            $request->class_id
                        );

                    })
                    ->ignore($material->id),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'syllabus' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | File baru bersifat OPTIONAL
            |--------------------------------------------------------------------------
            */

            'material_file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx',
                'max:10240',
            ],

            'vocabularies' => [
                'nullable',
                'string',
            ],

        ]);


        try {

            DB::transaction(function () use ($request, $material) {

                /*
                |--------------------------------------------------------------------------
                | Data yang akan diperbarui
                |--------------------------------------------------------------------------
                */

                $data = [

                    'class_id' => $request->class_id,

                    'meeting_number' => $request->meeting_number,

                    'title' => $request->title,

                    'syllabus' => $request->syllabus,

                ];


                /*
                |--------------------------------------------------------------------------
                | Jika user upload file baru
                |--------------------------------------------------------------------------
                */

                if ($request->hasFile('material_file')) {

                    /*
                    |--------------------------------------------------------------------------
                    | Hapus file lama
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $material->material_file_path &&
                        Storage::disk('public')->exists(
                            $material->material_file_path
                        )
                    ) {

                        Storage::disk('public')->delete(
                            $material->material_file_path
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Simpan file baru
                    |--------------------------------------------------------------------------
                    */

                    $data['material_file_path'] = $request
                        ->file('material_file')
                        ->store('materials', 'public');

                }


                /*
                |--------------------------------------------------------------------------
                | Update Material
                |--------------------------------------------------------------------------
                */

                $material->update($data);


                /*
                |--------------------------------------------------------------------------
                | Hapus Vocabulary Lama
                |--------------------------------------------------------------------------
                */

                $material
                    ->vocabularies()
                    ->delete();


                /*
                |--------------------------------------------------------------------------
                | Simpan Vocabulary Baru
                |--------------------------------------------------------------------------
                */

                $this->saveVocabularies(
                    $material,
                    $request->vocabularies
                );

            });


            return redirect()
                ->route('kurikulum.materi')
                ->with(
                    'success',
                    'Materi berhasil diperbarui.'
                );


        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui materi: ' . $e->getMessage()
                );

        }
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE VOCABULARIES
    |--------------------------------------------------------------------------
    | Menyimpan vocabulary sekaligus memberikan order_number
    |--------------------------------------------------------------------------
    */

    private function saveVocabularies(
        Material $material,
        ?string $vocabularies
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Tidak ada vocabulary
        |--------------------------------------------------------------------------
        */

        if (!$vocabularies) {

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Pecah berdasarkan baris
        |--------------------------------------------------------------------------
        */

        $rows = preg_split(
            '/\r\n|\r|\n/',
            $vocabularies
        );


        /*
        |--------------------------------------------------------------------------
        | Nomor urut vocabulary dimulai dari 1
        |--------------------------------------------------------------------------
        */

        $orderNumber = 1;


        foreach ($rows as $row) {

            $row = trim($row);


            /*
            |--------------------------------------------------------------------------
            | Lewati baris kosong
            |--------------------------------------------------------------------------
            */

            if ($row === '') {

                continue;

            }


            /*
            |--------------------------------------------------------------------------
            | Format:
            |
            | Hanzi | Pinyin | Meaning
            |--------------------------------------------------------------------------
            */

            $parts = explode('|', $row);


            /*
            |--------------------------------------------------------------------------
            | Minimal harus mempunyai 3 bagian
            |--------------------------------------------------------------------------
            */

            if (count($parts) < 3) {

                continue;

            }


            /*
            |--------------------------------------------------------------------------
            | Simpan Vocabulary
            |--------------------------------------------------------------------------
            */

            MaterialVocab::create([

                'material_id' => $material->id,

                'order_number' => $orderNumber,

                'hanzi' => trim($parts[0]),

                'pinyin' => trim($parts[1]),

                'meaning' => trim($parts[2]),

            ]);


            /*
            |--------------------------------------------------------------------------
            | Nomor urut berikutnya
            |--------------------------------------------------------------------------
            */

            $orderNumber++;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    | Menghapus:
    | - Vocabulary
    | - File
    | - Material
    |--------------------------------------------------------------------------
    */

    public function destroy(Material $material)
    {
        try {

            DB::transaction(function () use ($material) {

                /*
                |--------------------------------------------------------------------------
                | Hapus Vocabulary
                |--------------------------------------------------------------------------
                */

                $material
                    ->vocabularies()
                    ->delete();


                /*
                |--------------------------------------------------------------------------
                | Hapus File
                |--------------------------------------------------------------------------
                */

                if (
                    $material->material_file_path &&
                    Storage::disk('public')->exists(
                        $material->material_file_path
                    )
                ) {

                    Storage::disk('public')->delete(
                        $material->material_file_path
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Hapus Material
                |--------------------------------------------------------------------------
                */

                $material->delete();

            });


            return redirect()
                ->route('kurikulum.materi')
                ->with(
                    'success',
                    'Materi berhasil dihapus.'
                );


        } catch (\Exception $e) {

            return back()
                ->with(
                    'error',
                    'Gagal menghapus materi: ' . $e->getMessage()
                );

        }
    }
}