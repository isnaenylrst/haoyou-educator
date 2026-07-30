<?php

namespace Database\Seeders;

use App\Models\ClassEnrollment;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * DUMMY DATA - tiap enrollment mendapat 1 pembayaran.
     */
    public function run(): void
    {
        $enrollmentIds = ClassEnrollment::pluck('id');

        foreach ($enrollmentIds as $enrollmentId) {
            Payment::factory()->create(['enrollment_id' => $enrollmentId]);
        }
    }
}
