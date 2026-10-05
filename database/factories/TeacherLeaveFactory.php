<?php

namespace Database\Factories;

use App\Models\TeacherLeave;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TeacherLeaveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'leave_type' => fake()->randomElement(['Sick', 'Permission']),
            'leave_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'reason' => fake()->sentence(),
            'supporting_document' => fake()->optional(0.6)->passthrough('leaves/' . fake()->uuid() . '.pdf'),
            'status' => 'Pending',
            'approved_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TeacherLeave $leave) {
            $leaveDate = Carbon::parse($leave->leave_date);

            $leave->status = $leaveDate->isPast()
                ? fake()->randomElement(['Approved', 'Approved', 'Rejected'])
                : fake()->randomElement(['Pending', 'Approved']);

            if ($leave->status !== 'Pending') {
                $approvedAt = $leaveDate->copy()->subDays(fake()->numberBetween(1, 5));
                $leave->approved_at = $approvedAt->isFuture() ? now() : $approvedAt;
            }
        });
    }
}