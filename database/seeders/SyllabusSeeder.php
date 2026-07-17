<?php

namespace Database\Seeders;

use App\Models\Syllabus;
use Illuminate\Database\Seeder;

class SyllabusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Syllabus::insert([

            [
                'program_id'=>1,
                'curriculum_id'=>1,
                'title'=>'Silabus Mandarin Level 1A',
                'level'=>'1A',
                'description'=>'Silabus pembelajaran Mandarin level 1A.',
                'status'=>'published',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'program_id'=>1,
                'curriculum_id'=>1,
                'title'=>'Silabus Mandarin Level 1B',
                'level'=>'1B',
                'description'=>'Silabus pembelajaran Mandarin level 1B.',
                'status'=>'published',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'program_id'=>2,
                'curriculum_id'=>1,
                'title'=>'Silabus Mandarin Level 2A',
                'level'=>'2A',
                'description'=>'Silabus pembelajaran Mandarin level 2A.',
                'status'=>'published',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'program_id'=>2,
                'curriculum_id'=>1,
                'title'=>'Silabus Mandarin Level 2B',
                'level'=>'2B',
                'description'=>'Silabus pembelajaran Mandarin level 2B.',
                'status'=>'draft',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

            [
                'program_id'=>3,
                'curriculum_id'=>1,
                'title'=>'Silabus Mandarin Level 3A',
                'level'=>'3A',
                'description'=>'Silabus pembelajaran Mandarin level 3A.',
                'status'=>'published',
                'created_at'=>now(),
                'updated_at'=>now(),
            ],

        ]);
    }
}