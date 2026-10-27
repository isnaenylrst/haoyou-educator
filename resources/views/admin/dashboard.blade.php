@extends('admin.app')

@section('title', 'Dashboard | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/admin/dashboard.css') }}">
@endpush

@section('content')


    {{-- =====================================================
        HEADER
    ===================================================== --}}
    <div class="dashboard-header">
        <div>
            <div class="eyebrow">RINGKASAN</div>
            <h1>Selamat datang kembali, {{ auth()->user()->name }} 👋</h1>
            <p>Berikut ringkasan operasional Haoyou Educator hari ini, {{ now()->translatedFormat('l d F Y') }}.</p>
        </div>

        <div class="dashboard-header-actions">
            <button class="btn btn-secondary">
                <i class="fa-solid fa-chart-line"></i> Lihat Laporan
            </button>
        </div>
    </div>

    {{-- =====================================================
        PRIORITY
    ===================================================== --}}
    <div class="priority-card">
        <div class="priority-title">
            <span class="danger-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <strong>Prioritas Hari Ini</strong>
            <span>{{ $prioritas['total'] }} hal perlu ditindaklanjuti</span>
        </div>

        <div class="priority-items">

            {{-- Kelas berakhir hari ini (hari H) --}}
            @foreach ($prioritas['hari_h'] as $k)
                <div class="priority-item">
                    <div class="priority-icon red"><i class="fa-solid fa-flag-checkered"></i></div>
                    <div class="priority-text">
                        <strong>{{ $k->class_name }} berakhir hari ini</strong>
                        <span>Guru {{ $k->teacher->name ?? '-' }} · {{ $k->students_count }} siswa</span>
                    </div>
                </div>
            @endforeach

            {{-- Kelas akan berakhir (sebelum) --}}
            @foreach ($prioritas['sebelum'] as $k)
                <div class="priority-item">
                    <div class="priority-icon yellow"><i class="fa-solid fa-hourglass-end"></i></div>
                    <div class="priority-text">
                        <strong>{{ $k->class_name }} akan berakhir</strong>
                        <span>{{ (int) abs(today()->diffInDays($k->end_date)) }} hari lagi · Guru {{ $k->teacher->name ?? '-' }}</span>
                    </div>
                </div>
            @endforeach

            {{-- Setelah berakhir: konfirmasi ke guru --}}
            @foreach ($prioritas['setelah'] as $k)
                <div class="priority-item">
                    <div class="priority-icon red"><i class="fa-solid fa-user-check"></i></div>
                    <div class="priority-text">
                        <strong>Konfirmasi ke guru: {{ $k->class_name }}</strong>
                        <span>Berakhir {{ $k->end_date->translatedFormat('d M Y') }} ({{ (int) abs($k->end_date->diffInDays(today())) }} hari lalu) · Guru {{ $k->teacher->name ?? '-' }}</span>
                    </div>
                </div>
            @endforeach

            {{-- Follow up siswa: progress report sudah terverifikasi kurikulum --}}
            @foreach ($prioritas['follow_up'] as $r)
                <div class="priority-item">
                    <div class="priority-icon yellow"><i class="fa-solid fa-phone-volume"></i></div>
                    <div class="priority-text">
                        <strong>Follow up {{ $r->student->name ?? '-' }}</strong>
                        <span>Progress report {{ $r->report_period }} · {{ $r->enrollment->class->class_name ?? '' }} sudah terverifikasi kurikulum</span>
                    </div>
                </div>
            @endforeach

            @if ($prioritas['total'] === 0)
                <div class="priority-item">
                    <div class="priority-text"><strong>Tidak ada prioritas hari ini 🎉</strong></div>
                </div>
            @endif
        </div>
    </div>

    {{-- =====================================================
        STATISTICS
    ===================================================== --}}
    @php
        $cards = [
            ['Siswa Aktif',            $stats['siswa_aktif'],            'green',  'fa-user-graduate'],
            ['Calon Siswa Minggu Ini', $stats['calon_siswa_minggu_ini'], 'blue',   'fa-user-plus'],
            ['Waiting Trial Hari Ini', $stats['waiting_trial_hari_ini'], 'blue',   'fa-hourglass-half'],
            ['Konsultasi Hari Ini',    $stats['konsultasi_hari_ini'],    'red',    'fa-comments'],
            ['Menunggu Jadwal Kelas',  $stats['menunggu_jadwal_kelas'],  'purple', 'fa-calendar-days'],
            ['Kelas Berjalan',         $stats['kelas_berjalan'],         'yellow', 'fa-chalkboard'],
        ];
    @endphp

    <div class="stats-grid">
        @foreach ($cards as [$label, $value, $color, $icon])
            <div class="stat-card {{ $color }}">
                <div class="stat-header">
                    <span>{{ $label }}</span>
                    <span class="stat-icon"><i class="fa-solid {{ $icon }}"></i></span>
                </div>
                <strong>{{ $value }}</strong>
            </div>
        @endforeach
    </div>

    {{-- =====================================================
        ANALYTICS
    ===================================================== --}}
    <div class="dashboard-two-column">

        {{-- FUNNEL --}}
        <div class="dashboard-card funnel-card">
            <div class="card-header">
                <div>
                    <h3>Lead Conversion Funnel</h3>
                    <p>Inquiry &rarr; Warm &rarr; Trial &rarr; Payment &rarr; Student</p>
                </div>
                <div class="period-tabs">
                    <button class="active">30 Hari</button>
                    <button>90 Hari</button>
                </div>
            </div>

            <div class="funnel-list">
                <div class="funnel-row">
                    <span class="funnel-name">Inquiry</span>
                    <div class="funnel-bar"><div style="width: 100%" class="funnel-fill gray"></div></div>
                    <strong>128</strong>
                </div>
                <div class="funnel-row">
                    <span class="funnel-name">Warm Lead</span>
                    <div class="funnel-bar"><div style="width: 68%" class="funnel-fill yellow"></div></div>
                    <strong>87</strong>
                </div>
                <div class="funnel-row">
                    <span class="funnel-name">Trial</span>
                    <div class="funnel-bar"><div style="width: 38%" class="funnel-fill blue"></div></div>
                    <strong>49</strong>
                </div>
                <div class="funnel-row">
                    <span class="funnel-name">Payment</span>
                    <div class="funnel-bar"><div style="width: 29%" class="funnel-fill gold"></div></div>
                    <strong>37</strong>
                </div>
                <div class="funnel-row">
                    <span class="funnel-name">Student</span>
                    <div class="funnel-bar"><div style="width: 24%" class="funnel-fill green"></div></div>
                    <strong>31</strong>
                </div>
            </div>
        </div>

        {{-- PAYMENT STATUS --}}
        <div class="dashboard-card payment-card">
            <div class="card-header">
                <div>
                    <h3>Status Pembayaran</h3>
                    <p>Bulan berjalan</p>
                </div>
            </div>

            <div class="payment-content">
                <div class="donut">
                    <div class="donut-center">
                        <div class="val">Rp205Jt</div>
                        <div class="lbl">Total Invoice</div>
                    </div>
                </div>

                <div class="legend">
                    <div class="legend-item">
                        <span class="legend-dot green-dot"></span>
                        Paid - 58%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot yellow-dot"></span>
                        Partial - 20%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot red-dot"></span>
                        Overdue - 12%
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot gray-dot"></span>
                        Belum Ditagih - 10%
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =====================================================
        PROGRAM + SCHEDULE
    ===================================================== --}}
    <div class="dashboard-two-column">

        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h3>Program Kelas Aktif</h3>
                    <p>Kelas mendekati sesi terakhir ditandai khusus</p>
                </div>
            </div>

            <div class="cp-lis" style="max-height: 520px; overflow-y: auto;">
                @forelse ($programAktif as $k)
                    @php
                        $barColor = match ($k->state) {
                            'ending' => '#D9A404',
                            'done'   => '#16A34A',
                            'closed' => '#9CA3AF',
                            default  => null,
                        };
                    @endphp
                    <div class="cp-row">
                        <div class="cp-head">
                            <div>
                                <div class="cp-title">{{ $k->class_name }}</div>
                                <div class="cp-meta">Guru {{ $k->teacher->name ?? '-' }} · {{ $k->students_count }} siswa · {{ $k->delivery_mode }} </div>
                            </div>
                            <div class="cp-badges">
                                @if ($k->state === 'ending')
                                    <span class="class-status class-ending">Akan Berakhir · {{ $k->sessions_left }} sesi lagi</span>
                                @elseif ($k->state === 'done')
                                    <span class="class-status class-done">Selesai</span>
                                @elseif ($k->state === 'closed')
                                    <span class="class-status" style="background:#F1F2F4; color:#6B7280;">Ditutup</span>
                                @elseif ($k->state === 'scheduled')
                                    <span class="class-status class-scheduled">Belum Mulai</span>
                                @else
                                    <span class="class-status class-live">Berjalan</span>
                                @endif
                            </div>
                        </div>
                        <div class="cp-track">
                            <div class="cp-fill" style="width: {{ $k->progress }}%;{{ $barColor ? ' background:' . $barColor . ';' : '' }}"></div>
                        </div>
                        <div class="cp-foot">
                            <span class="cp-sessions">Pertemuan
                                <b>{{ $k->done_sessions }}@if ($k->total_sessions > 0)/{{ $k->total_sessions }}@endif</b>
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="cp-row">
                        <div class="cp-meta">Belum ada kelas aktif.</div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- JADWAL --}}
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h3>Jadwal Kelas Hari Ini</h3>
                    <p>{{ $jadwalHariIni->count() }} kelas terjadwal</p>
                </div>
                <a href="#">Kalender <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="today-list">
                @forelse ($jadwalHariIni as $s)
                    @php
                        $guru = $s->teacher->name ?? $s->class->teacher->name ?? '-';
                        $mode = $s->location_type ?: ($s->class->delivery_mode ?? '');
                    @endphp
                    <div class="today-item {{ $s->state === 'completed' ? 'completed' : (in_array($s->state, ['soon', 'ongoing']) ? 'soon' : '') }}">
                        @if (in_array($s->state, ['soon', 'ongoing']))
                            <div class="today-time" style="color: #E0A400; font-weight: 800;">
                                {{ $s->start_label }} – {{ $s->end_label }} ·
                                {{ $s->state === 'ongoing' ? 'sedang berlangsung' : 'dalam ' . $s->minutes_to . ' menit' }}
                            </div>
                        @else
                            <div class="today-time">{{ $s->start_label }} – {{ $s->end_label }}{{ $s->state === 'completed' ? ' · completed' : '' }}</div>
                        @endif
                        <div class="today-title">{{ $s->class->class_name ?? '-' }}</div>
                        <div class="today-meta">{{ $guru }} · {{ $s->class->students_count ?? 0 }} siswa · <b>{{ $mode }}</b>{{ $s->room ? ' · ' . $s->room : '' }}</div>
                    </div>
                @empty
                    <div class="today-item">
                        <div class="today-title">Tidak ada kelas hari ini</div>
                    </div>
                @endforelse
            </div>
        </div>
        </div>

        {{-- =====================================================
        PAYMENT + TEACHER
        ===================================================== --}}
        <div class="dashboard-two-column">

            {{-- PAYMENT DUE --}}
            <div class="dashboard-card">
                <div class="card-header">
                    <div>
                        <h3>Pembayaran Jatuh Tempo</h3>
                        <p>Per siswa, diurutkan terdekat</p>
                    </div>
                </div>

                <div class="panel-list">
              <div class="panel-row">
                <div class="panel-avatar">SN</div>
                <div><div class="panel-title">Sinta Nuraini</div><div class="panel-meta">Termin 2/3 · Rp 2.500.000</div></div>
                <span class="due-badge due-overdue" style="margin-left:auto;">Terlambat 3 hr</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar">FH</div>
                <div><div class="panel-title">Farhan Hidayat</div><div class="panel-meta">Termin 1/3 · Rp 1.800.000</div></div>
                <span class="due-badge due-overdue" style="margin-left:auto;">Terlambat 1 hr</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar">AL</div>
                <div><div class="panel-title">Alya Lestari</div><div class="panel-meta">Termin 1/3 · Rp 1.800.000</div></div>
                <span class="due-badge due-soon" style="margin-left:auto;">Jatuh tempo 2 hr lagi</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar">DP</div>
                <div><div class="panel-title">Dewi Putri</div><div class="panel-meta">Termin 3/3 · Rp 900.000</div></div>
                <span class="due-badge due-soon" style="margin-left:auto;">Jatuh tempo 4 hr lagi</span>
              </div>
              <div class="panel-row">
                <div class="panel-avatar">RH</div>
                <div><div class="panel-title">Rendra Hakim</div><div class="panel-meta">Termin 2/3 · Rp 1.200.000</div></div>
                <span class="due-badge due-later" style="margin-left:auto;">14 hr lagi</span>
              </div>
            </div>
            <button class="btn btn-ghost" style="width:100%; justify-content:center; margin-top:12px; font-size:12px; padding:8px;">Lihat semua tagihan</button>
            </div>

            {{-- TEACHER --}}
            <div class="dashboard-card">
                <div class="card-header">
                    <div>
                        <h3>Guru Tersedia Hari Ini</h3>
                        <p>Bisa dialokasikan untuk trial / kelas pengganti</p>
                    </div>
                </div>

            <div class="panel-list" style="max-height: 420px; overflow-y: auto;">
              @forelse ($guruTersedia as $g)
                <div class="panel-row">
                  <div class="panel-avatar" style="background:#FFF7E0;{{ $g->has_teaching ? '' : ' color:#92620A;' }}">{{ $g->initials }}</div>
                  <div><div class="panel-title">{{ $g->name }}</div><div class="panel-meta">{{ $g->meta }}</div></div>
                  <span class="due-badge {{ $g->has_teaching ? 'due-later' : 'class-live' }}" style="margin-left:auto;">{{ $g->has_teaching ? 'Mengajar' : 'Tidak ada jadwal' }}</span>
                </div>
              @empty
                <div class="panel-row">
                  <div class="panel-meta">Belum ada guru yang menyatakan tersedia hari ini.</div>
                </div>
              @endforelse
            </div>
            </div>

        </div>
    </div>

@endsection