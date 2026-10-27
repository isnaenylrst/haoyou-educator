<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buang duplikat: simpan baris terbaru untuk tiap (jenis dokumen + nama)
        $groups = DB::table('document_templates')
            ->select('document_type', 'name', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('document_type', 'name')
            ->having('total', '>', 1)
            ->get();

        foreach ($groups as $g) {
            $old = DB::table('document_templates')
                ->where('document_type', $g->document_type)
                ->where('name', $g->name)
                ->where('id', '<>', $g->keep_id)
                ->get();

            foreach ($old as $row) {
                if ($row->file_path) {
                    Storage::disk('public')->delete($row->file_path);
                }
            }

            DB::table('document_templates')
                ->where('document_type', $g->document_type)
                ->where('name', $g->name)
                ->where('id', '<>', $g->keep_id)
                ->delete();
        }

        // 2. Cegah duplikat di level database
        Schema::table('document_templates', function (Blueprint $table) {
            $table->unique(['document_type', 'name'], 'document_templates_type_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropUnique('document_templates_type_name_unique');
        });
    }
};