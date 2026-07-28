<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    /**
     * Tiap enrollment punya 1 pembayaran (DP atau pelunasan).
     */
    public function run(): void
    {
        $now = now();

        $enrollments = DB::table('class_enrollments')->get();

        foreach ($enrollments as $index => $enrollment) {
            $totalBill = fake()->randomElement([550000, 950000, 1200000, 1500000, 2000000, 2500000]);
            $status = fake()->randomElement(['Pending', 'Paid', 'Partial']);

            $amountPaid = match ($status) {
                'Paid' => $totalBill,
                'Partial' => intdiv($totalBill, 2),
                default => 0,
            };

            DB::table('payments')->insert([
                'enrollment_id' => $enrollment->id,
                'invoice_number' => 'INV-' . now()->format('Ym') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'invoice_file_path' => 'invoices/invoice_' . ($index + 1) . '.pdf',
                'payment_stage' => fake()->randomElement(['DP', 'Pelunasan', 'Cicilan 1']),
                'total_bill' => $totalBill,
                'amount_paid' => $amountPaid,
                'remaining_bill' => $totalBill - $amountPaid,
                'payment_method' => fake()->randomElement(['Transfer Bank', 'QRIS', 'Cash']),
                'payment_date' => fake()->dateTimeBetween('-4 months', 'now')->format('Y-m-d'),
                'payment_proof_path' => 'payment_proofs/proof_' . ($index + 1) . '.jpg',
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}