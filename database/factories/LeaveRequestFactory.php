<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\Curriculum;
use App\Models\ClassSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {

        return [

            'teacher_id' => Teacher::factory(),

            'class_schedule_id' => ClassSchedule::factory(),

            'replacement_teacher_id' => Teacher::factory(),

            'type' => fake()->randomElement([
                'Sick',
                'Leave',
                'Permission',
                'Emergency'
            ]),

            'reason' => fake()->paragraph(),

            'status' => fake()->randomElement([
                'Pending',
                'Approved',
                'Rejected'
            ]),

            'approved_by' => Curriculum::factory(),

            'approved_at' => now(),

        ];

    }
}