<?php

namespace Database\Factories;

use App\Models\PrivatePackage;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CandidateStudentFactory extends Factory
{
    private static ?array $programIds = null;
    private static ?array $privatePackageIds = null;

    public function definition(): array
    {
        $birthDate = fake()->dateTimeBetween('-30 years', '-4 years');
        $age = Carbon::parse($birthDate)->age;

        [$programId, $privatePackageId] = $this->resolveInterest($age);

        $trialStatus = fake()->randomElement(['Pending', 'Pending', 'Completed', 'Completed', 'Completed', 'Cancelled']);

        // Trial Pending harus tanggalnya di masa depan, Completed/Cancelled
        // harus di masa lalu — tidak boleh kontradiktif.
        $trialDate = $trialStatus === 'Pending'
            ? fake()->dateTimeBetween('now', '+2 weeks')
            : fake()->dateTimeBetween('-2 months', '-1 days');

        [$feeStatus, $feePaidAt] = $this->resolveRegistrationFee();

        return [
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'birth_date' => $birthDate->format('Y-m-d'),
            'phone' => fake()->numerify('08##########'),
            'parent_name' => fake()->name(),
            'parent_phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'school' => $this->schoolFor($age),
            'source' => fake()->randomElement(['Instagram', 'Referral', 'Website', 'Walk-in', 'TikTok']),
            'allergy' => fake()->randomElement([null, null, null, 'Kacang', 'Debu']),
            'program_id' => $programId,
            'private_package_id' => $privatePackageId,
            'trial_date' => $trialDate->format('Y-m-d'),
            'trial_status' => $trialStatus,
            'trial_notes' => $trialStatus === 'Completed' ? fake()->optional(0.6)->sentence() : null,
            'lead_status' => $this->leadStatusFor($trialStatus),
            'registration_fee_status' => $feeStatus,
            'registration_fee_paid_at' => $feePaidAt,
            'registration_fee_proof_path' => $feeStatus === 'Pending'
                ? null
                : 'proofs/registration/' . fake()->uuid() . '.jpg',
        ];
    }

    /**
     * State: calon siswa yang sengaja tertarik program PRIVAT (bukan reguler).
     */
    public function interestedInPrivate(): static
    {
        $ids = $this->privatePackageIds();

        return $this->state(fn () => [
            'program_id' => null,
            'private_package_id' => empty($ids) ? null : fake()->randomElement($ids),
        ]);
    }

    /**
     * State: fee pendaftaran sudah lewat 30 hari & belum lanjut DP —
     * buat nge-test job ExpireRegistrationFees.
     */
    public function registrationFeeExpired(): static
    {
        return $this->state(fn () => [
            'registration_fee_status' => 'Expired',
            'registration_fee_paid_at' => now()->subDays(fake()->numberBetween(31, 90)),
            'registration_fee_proof_path' => 'proofs/registration/' . fake()->uuid() . '.jpg',
        ]);
    }

    private function resolveInterest(int $age): array
    {
        $privateIds = $this->privatePackageIds();

        // ~15% calon siswa tertarik program privat
        if (!empty($privateIds) && fake()->boolean(15)) {
            return [null, fake()->randomElement($privateIds)];
        }

        $programIds = $this->programIds();
        if (empty($programIds)) {
            return [null, null];
        }

        // Anak di bawah 10 tahun realistisnya Daily Activity, bukan HSK
        if ($age < 10 && isset($programIds['Daily Activity'])) {
            return [$programIds['Daily Activity'], null];
        }

        return [fake()->randomElement($programIds), null];
    }

    private function resolveRegistrationFee(): array
    {
        // Mayoritas masih Pending (baru jadi lead, belum bayar apa-apa)
        $status = fake()->randomElement(['Pending', 'Pending', 'Pending', 'Active', 'Active', 'Expired']);

        return match ($status) {
            'Active' => [$status, now()->subDays(fake()->numberBetween(0, 29))],
            'Expired' => [$status, now()->subDays(fake()->numberBetween(31, 90))],
            default => [$status, null],
        };
    }

    private function schoolFor(int $age): ?string
    {
        return match (true) {
            $age < 6 => fake()->optional(0.5)->randomElement(['TK Kartika', 'TK Islam Terpadu']),
            $age < 12 => fake()->randomElement(['SD Kartika', 'SD Negeri 1', 'SD Islam Terpadu']),
            $age < 15 => fake()->randomElement(['SMP Negeri 1', 'SMP Negeri 3']),
            $age < 18 => fake()->randomElement(['SMA Negeri 3', 'SMA Negeri 5']),
            default => fake()->optional(0.4)->randomElement(['Universitas Brawijaya', 'Politeknik Negeri Malang']),
        };
    }

    private function leadStatusFor(string $trialStatus): string
    {
        return match ($trialStatus) {
            'Completed' => fake()->randomElement(['Warm', 'Hot', 'Hot']),
            'Cancelled' => fake()->randomElement(['Cold', 'Cold', 'Warm']),
            default => fake()->randomElement(['Cold', 'Warm', 'Hot']),
        };
    }

    private function programIds(): array
    {
        if (self::$programIds === null) {
            self::$programIds = Program::pluck('id', 'program_name')->toArray();
        }

        return self::$programIds;
    }

    private function privatePackageIds(): array
    {
        if (self::$privatePackageIds === null) {
            self::$privatePackageIds = PrivatePackage::pluck('id')->toArray();
        }

        return self::$privatePackageIds;
    }
}