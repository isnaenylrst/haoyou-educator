@extends('layouts.kurikulum')

@section('title', 'Detail Belajar Mandiri')

@section('content')
@php
    $stClass = fn ($s) => ['hafal' => 'success', 'benar' => 'success', 'ragu' => 'warning text-dark', 'salah' => 'danger'][strtolower($s)] ?? 'secondary';
@endphp
<div class="container-fluid">

    <div class="mb-4">
        <a href="{{ url('/kurikulum/belajar-mandiri') }}" class="small text-decoration-none">&larr; Kembali ke rekap</a>
        <h2 class="fw-bold mt-1 mb-1">{{ $student->name ?: $student->user->username }}</h2>
        <p class="text-muted mb-0">{{ $student->currentLevel->level_name ?? 'Level belum diatur' }} · {{ $student->user->username ?? '' }}</p>
    </div>

    @if($summary === null)
        <div class="alert alert-warning">Ringkasan nilai siswa ini gagal dihitung. Riwayat di bawah tetap ditampilkan.</div>
    @endif

    <div class="row mb-3">
        @foreach([
            ['Vocab', data_get($summary, 'vocab_stars', 0) . ' / ' . data_get($summary, 'vocab_max', 0)],
            ['Grammar', data_get($summary, 'grammar_stars', 0) . ' / ' . data_get($summary, 'grammar_max', 0)],
            ['Nilai total', $score !== null ? $score . '%' . ($predikat ? ' · ' . $predikat : '') : '-'],
        ] as [$label, $val])
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card shadow-sm border-0 h-100 mb-0"><div class="card-body">
                    <small class="text-uppercase text-muted fw-bold">{{ $label }}</small>
                    <h3 class="fw-bold mt-2 mb-0">{{ $val }}</h3>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold">Riwayat jawaban Grammar (100 terakhir)</div>
                <div class="table-responsive" style="max-height:480px">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Waktu</th><th>Soal</th><th>Status</th><th>Percobaan</th></tr></thead>
                        <tbody>
                        @forelse($grammarLogs as $l)
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($l->answered_at)->format('d M H:i') }}</td>
                                <td>No. {{ $l->question_no }}<div class="small text-muted">{{ \Illuminate\Support\Str::limit($l->soal, 60) }}</div></td>
                                <td><span class="badge bg-{{ $stClass($l->status) }}">{{ ucfirst($l->status) }}</span></td>
                                <td>{{ $l->attempt_no }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada jawaban Grammar.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-bold">Kata Vocab yang masih salah atau ragu</div>
                <div class="table-responsive" style="max-height:300px">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Kata</th><th>Arti</th><th>Tes</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($vocabTrouble as $v)
                            <tr>
                                <td><span class="fs-5">{{ $v->hanzi }}</span><div class="small text-muted">{{ $v->pinyin }}</div></td>
                                <td>{{ $v->meaning_id }}</td>
                                <td>{{ ucfirst($v->test_type) }}</td>
                                <td><span class="badge bg-{{ $stClass($v->status) }}">{{ ucfirst($v->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada kata yang salah atau ragu.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-0">
                <div class="card-header bg-white fw-bold">Riwayat percobaan Vocab (50 terakhir)</div>
                <div class="table-responsive" style="max-height:300px">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Waktu</th><th>Kata</th><th>Tes</th><th>Status</th><th>Ke-</th></tr></thead>
                        <tbody>
                        @forelse($vocabLogs as $l)
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($l->answered_at)->format('d M H:i') }}</td>
                                <td>{{ $l->hanzi }} <span class="small text-muted">{{ $l->pinyin }}</span></td>
                                <td>{{ ucfirst($l->test_type) }}</td>
                                <td><span class="badge bg-{{ $stClass($l->status) }}">{{ ucfirst($l->status) }}</span></td>
                                <td>{{ $l->attempt_no }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Log Vocab baru terisi setelah log dipasang.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection