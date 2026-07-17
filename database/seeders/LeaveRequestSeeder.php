<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveRequest;

class LeaveRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        LeaveRequest::insert([

            [

                'teacher_id' => 1,
                'class_schedule_id' => 1,
                'replacement_teacher_id' => 2,
                'type' => 'Sick',
                'reason' => 'Demam dan tidak dapat mengajar.',
                'status' => 'Approved',
                'approved_by' => 1,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),

            ],

            [

                'teacher_id' => 2,
                'class_schedule_id' => 2,
                'replacement_teacher_id' => 3,
                'type' => 'Leave',
                'reason' => 'Keperluan keluarga.',
                'status' => 'Pending',
                'approved_by' => null,
                'approved_at' => null,
                'created_at' => now(),
                'updated_at' => now(),

            ],

            [

                'teacher_id' => 3,
                'class_schedule_id' => 3,
                'replacement_teacher_id' => null,
                'type' => 'Permission',
                'reason' => 'Menghadiri seminar.',
                'status' => 'Rejected',
                'approved_by' => 2,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),

            ],

        ]);

    }
}