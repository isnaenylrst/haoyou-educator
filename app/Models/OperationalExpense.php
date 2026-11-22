<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OperationalExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'operational_expenses';

    public const CATEGORIES = [
        'ATK',
        'Kebersihan',
        'Konsumsi & Minuman',
        'Perlengkapan Kantor',
        'Perawatan & Perbaikan',
        'Lainnya',
    ];

    public const STATUSES = [
        'Menunggu Owner',
        'Disetujui',
        'Ditolak',
    ];

    protected $fillable = [
        'date',
        'item_name',
        'category',
        'amount',
        'purchase_method',
        'purchase_link',
        'description',
        'receipt_path',
        'status',
        'created_by',
        'admin_signature_path',
        'admin_signer_name',
        'admin_signed_at',
        'owner_id',
        'owner_signature_path',
        'owner_signer_name',
        'owner_note',
        'owner_signed_at',
    ];

    protected $casts = [
        'date'            => 'date',
        'amount'          => 'decimal:2',
        'admin_signed_at' => 'datetime',
        'owner_signed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getFormattedAmountAttribute(): string
    {
        return format_rupiah($this->amount);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'Menunggu Owner' => 'badge-owner',
            'Disetujui'      => 'badge-completed',
            'Ditolak'        => 'badge-hot',
            default          => 'badge-neutral',
        };
    }

    /** Admin hanya boleh edit/hapus selama belum disetujui Owner */
    public function isEditable(): bool
    {
        return $this->status !== 'Disetujui';
    }

    public function isOnline(): bool
    {
        return $this->purchase_method === 'Online';
    }

    /**
     * Ringkasan kas masuk & saldo kas (dipakai halaman Admin dan Owner).
     *
     * - kasMasuk*  : kas masuk pada periode yang dipilih
     * - saldoKas   : kumulatif, seluruh kas masuk - pengeluaran Disetujui,
     *                dihitung sampai akhir periode (tanpa periode = sampai saat ini)
     * - saldoSetelahMenunggu : saldoKas - pengeluaran yang masih Menunggu TTD Owner
     */
    public static function cashSummary(?string $bulan, ?string $dateFrom, ?string $dateTo): array
    {
        $applyPeriod = function ($q) use ($dateFrom, $dateTo, $bulan) {
            return $q->when(
                $dateFrom || $dateTo,
                fn ($qq) => $qq->when($dateFrom, fn ($x) => $x->whereDate('date', '>=', $dateFrom))
                               ->when($dateTo, fn ($x) => $x->whereDate('date', '<=', $dateTo)),
                fn ($qq) => $qq->when($bulan, function ($x) use ($bulan) {
                    [$y, $m] = explode('-', $bulan);
                    $x->whereYear('date', (int) $y)->whereMonth('date', (int) $m);
                })
            );
        };

        $cashQuery = fn () => DB::table('cash_funds')->whereNull('deleted_at');

        $kasMasukPeriode = (float) $applyPeriod($cashQuery())->sum('amount');
        $kasMasukCount   = $applyPeriod($cashQuery())->count();
        $kasMasukList    = $applyPeriod($cashQuery())->orderByDesc('date')->orderByDesc('id')->limit(10)->get();
        $kasMasukUsers   = \App\Models\User::whereIn('id', $kasMasukList->pluck('created_by')->unique())->get()->keyBy('id');

        $asOf = $dateTo ?: ($bulan ? \Carbon\Carbon::createFromFormat('!Y-m', $bulan)->endOfMonth()->toDateString() : null);
        $untilAsOf = fn ($q) => $q->when($asOf, fn ($qq) => $qq->whereDate('date', '<=', $asOf));

        $kasMasukSampai = (float) $untilAsOf($cashQuery())->sum('amount');
        $keluarSampai   = (float) $untilAsOf(static::where('status', 'Disetujui'))->sum('amount');
        $menungguSampai = (float) $untilAsOf(static::where('status', 'Menunggu Owner'))->sum('amount');

        $saldoKas = $kasMasukSampai - $keluarSampai;

        return [
            'kasMasukPeriode'      => $kasMasukPeriode,
            'kasMasukCount'        => $kasMasukCount,
            'kasMasukList'         => $kasMasukList,
            'kasMasukUsers'        => $kasMasukUsers,
            'saldoKas'             => $saldoKas,
            'saldoSetelahMenunggu' => $saldoKas - $menungguSampai,
            'asOfLabel'            => $asOf ? \Carbon\Carbon::parse($asOf)->translatedFormat('d M Y') : 'saat ini',
        ];
    }

    /**
     * Owner menyetujui + menandatangani pengeluaran ini.
     * Dipakai oleh halaman Rekap Pengajuan (tab Kas) dan Antrean TTD.
     *
     * TTD profil Owner disalin sebagai arsip, supaya dokumen yang sudah ditandatangani
     * tidak berubah walaupun tanda tangan di Profil diganti kemudian.
     * Pemanggil wajib memastikan $owner->signature_path ada.
     */
    public function approveByOwner(\App\Models\User $owner, ?string $note = null): void
    {
        DB::transaction(function () use ($owner, $note) {
            $ext  = pathinfo($owner->signature_path, PATHINFO_EXTENSION) ?: 'png';
            $path = 'signatures/operational/owner-' . $this->id . '-' . now()->format('YmdHis') . '.' . $ext;
            Storage::disk('public')->copy($owner->signature_path, $path);

            $this->update([
                'status'               => 'Disetujui',
                'owner_id'             => $owner->id,
                'owner_note'           => $note,
                'owner_signed_at'      => now(),
                'owner_signer_name'    => $owner->display_name ?? $owner->username,
                'owner_signature_path' => $path,
            ]);

            \App\Models\SignatureHistory::create([
                'document_type'  => 'operational_expense',
                'document_id'    => $this->id,
                'signer_id'      => $owner->id,
                'signer_role'    => 'Owner',
                'signature_path' => $path,
                'notes'          => "Persetujuan dan TTD Kas Operasional {$this->item_name} sebesar {$this->formatted_amount}",
                'signed_at'      => now(),
                'created_at'     => now(),
            ]);
        });
    }
}