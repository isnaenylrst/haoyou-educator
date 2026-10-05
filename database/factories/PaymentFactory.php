<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $totalBill = fake()->randomElement([550000, 950000, 1200000, 1500000, 2000000, 2500000]);

        return [
            'invoice_number' => $this->makeInvoiceNumber(),
            'invoice_file_path' => 'invoices/invoice_' . fake()->unique()->numerify('######') . '.pdf',
            'payment_stage' => 'Pelunasan',
            'total_bill' => $totalBill,
            'amount_paid' => $totalBill,
            'remaining_bill' => 0,
            'payment_method' => fake()->randomElement(['Transfer Bank', 'QRIS', 'Cash']),
            'payment_date' => fake()->dateTimeBetween('-4 months', 'now')->format('Y-m-d'),
            'payment_proof_path' => 'payment_proofs/proof_' . fake()->unique()->numerify('######') . '.jpg',
            'status' => 'Paid',
        ];
    }

    public function installment(
        string $stage,
        float $totalBill,
        float $amountPaid,
        float $remainingBill,
        string $paymentDate
    ): static {
        return $this->state(fn () => [
            'payment_stage' => $stage,
            'total_bill' => $totalBill,
            'amount_paid' => $amountPaid,
            'remaining_bill' => $remainingBill,
            'payment_date' => $paymentDate,
            'status' => $remainingBill <= 0 ? 'Paid' : 'Partial',
            'invoice_number' => $this->makeInvoiceNumber(),
        ]);
    }

    /**
     * Tagihan yang belum dibayar sama sekali.
     */
    public function pending(float $totalBill, string $paymentDate): static
    {
        return $this->state(fn () => [
            'payment_stage' => 'DP',
            'total_bill' => $totalBill,
            'amount_paid' => 0,
            'remaining_bill' => $totalBill,
            'payment_date' => $paymentDate,
            'payment_proof_path' => null,
            'status' => 'Pending',
            'invoice_number' => $this->makeInvoiceNumber(),
        ]);
    }

    private function makeInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ym') . '-' . fake()->unique()->numerify('######');
    }
}