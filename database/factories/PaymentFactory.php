<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['Pending', 'Paid', 'Partial']);
        $totalBill = fake()->randomElement([550000, 950000, 1200000, 1500000, 2000000, 2500000]);

        $amountPaid = match ($status) {
            'Paid' => $totalBill,
            'Partial' => intdiv($totalBill, 2),
            default => 0,
        };

        return [
            'invoice_number' => 'INV-' . now()->format('Ym') . '-' . fake()->unique()->numerify('######'),
            'invoice_file_path' => 'invoices/invoice_' . fake()->unique()->numerify('######') . '.pdf',
            'payment_stage' => fake()->randomElement(['DP', 'Pelunasan', 'Cicilan 1']),
            'total_bill' => $totalBill,
            'amount_paid' => $amountPaid,
            'remaining_bill' => $totalBill - $amountPaid,
            'payment_method' => fake()->randomElement(['Transfer Bank', 'QRIS', 'Cash']),
            'payment_date' => fake()->dateTimeBetween('-4 months', 'now')->format('Y-m-d'),
            'payment_proof_path' => 'payment_proofs/proof_' . fake()->unique()->numerify('######') . '.jpg',
            'status' => $status,
        ];
    }
}
