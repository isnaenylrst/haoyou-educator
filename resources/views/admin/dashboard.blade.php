@extends('admin.app')

@section('title', 'Dashboard | Haoyou Educator')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/css/admin/dashboard.css') }}">
    <style>
        /* Tinggi area list Program Kelas Aktif & Jadwal Hari Ini dibuat SAMA; kalau isi lebih panjang -> scroll */
        :root { --dash-list-h: 520px; }
        .dash-scroll { height: var(--dash-list-h); overflow-y: auto; position: relative; }

        /* Track progres: bar menyambung tanpa jeda.
           kuning/hijau = jurnal sudah ada, merah = sudah berlangsung tapi jurnal belum dibuat */
        .cp-track { display: flex; gap: 0; overflow: hidden; border-radius: 999px; }
        /* ujung kanan bar melengkung; kalau ada bagian merah, lengkung kanan pindah ke ujung merah */
        .cp-fill  { flex: none; border-radius: 999px; }
        .cp-fill.has-miss { border-radius: 999px 0 0 999px; }     /* kiri melengkung, kanan rata (menyambung ke merah) */
        .cp-miss  {
            flex: none; height: 100%;
            border-radius: 0 999px 999px 0;                       /* kiri rata (menyambung), kanan melengkung */
            background: #EF4444;                                  /* merah solid */
        }
        .cp-missing { color: #DC2626; font-weight: 700; font-size: 12px; }

        /* ---- Panel notifikasi dashboard ---- */
        .nb-card { background: #fff; border: 1px solid #edeef1; border-radius: 16px; padding: 18px 20px 20px; margin-bottom: 20px; }
        .nb-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .nb-head-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .nb-bell { position: relative; width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center;
                   background: var(--yellow-soft, #fef9c3); color: var(--gold-dark, #a16207); font-size: 15px; flex-shrink: 0; }
        .nb-bell.is-urgent { background: #fef2f2; color: #dc2626; }
        .nb-bell-dot { position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
                       background: #dc2626; color: #fff; font-size: 10px; font-weight: 600; display: grid; place-items: center;
                       border: 2px solid #fff; line-height: 1; }
        .nb-head strong { display: block; font-size: 14.5px; font-weight: 600; color: #1f2937; line-height: 1.2; }
        .nb-sub { display: block; font-size: 12px; font-weight: 400; color: #6b7280; margin-top: 2px; }
        .nb-sub b { color: #dc2626; font-weight: 500; }
        .nb-all { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; color: #374151;
                  text-decoration: none; padding: 7px 12px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff;
                  transition: border-color .15s, background-color .15s; }
        .nb-all:hover { border-color: var(--yellow, #facc15); background: #fffdf3; color: #111827; }
        .nb-all i { font-size: 10px; }

        .nb-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 10px; }
        a.nb-tile { position: relative; display: flex; align-items: center; gap: 12px; padding: 13px 14px 13px 16px;
                    border: 1px solid #edeef1; border-radius: 12px; background: #fff; text-decoration: none; color: inherit;
                    overflow: hidden; transition: border-color .15s, background-color .15s; }
        a.nb-tile::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #f59e0b; }
        a.nb-tile.is-urgent::before { background: #dc2626; }
        a.nb-tile:hover { border-color: #d9dce1; background: #fbfbfc; color: inherit; }
        a.nb-tile:focus-visible { outline: 2px solid var(--yellow, #facc15); outline-offset: 2px; }
        .nb-ico { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; flex-shrink: 0; font-size: 14px;
                  background: var(--yellow-soft, #fef9c3); color: var(--gold-dark, #a16207); }
        .nb-tile.is-urgent .nb-ico { background: #fef2f2; color: #dc2626; }
        .nb-txt { flex: 1; min-width: 0; }
        .nb-txt strong { display: block; font-size: 13px; font-weight: 500; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .nb-txt span { display: block; font-size: 11.5px; font-weight: 400; color: #6b7280; margin-top: 1px; }
        .nb-tile.is-urgent .nb-txt span { color: #dc2626; font-weight: 500; }
        .nb-num { font-size: 17px; font-weight: 600; color: #374151; line-height: 1; }

        .nb-empty { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 12px; background: #ecfdf5; color: #166534; font-size: 12.5px; }
        .nb-empty i { font-size: 18px; }
        .nb-empty strong { display: block; color: #14532d; font-size: 13px; font-weight: 600; }
    </style>
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
    </div>

    {{-- =====================================================
        NOTIFIKASI PER SECTION
    ===================================================== --}}
    @php
        $notifSections = \App\Services\AdminNotificationService::SECTIONS;
        $notifCounts   = $notif['counts'] ?? [];
        $notifUrgent   = $notif['urgent'] ?? [];
        $notifIcons    = [
            'siswa' => 'fa-user-graduate', 'kelas' => 'fa-chalkboard-user', 'jurnal' => 'fa-book-open',
            'pembayaran' => 'fa-money-bill-wave', 'progress' => 'fa-file-lines',
            'pengajuan' => 'fa-receipt', 'sop' => 'fa-clipboard-list',
        ];
    @endphp

    @php $notifUrgentTotal = array_sum($notifUrgent); @endphp

    <div class="nb-card">
        <div class="nb-head">
            <div class="nb-head-left">
                <span class="nb-bell {{ $notifUrgentTotal > 0 ? 'is-urgent' : '' }}">
                    <i class="fa-regular fa-bell"></i>
                    @if ($notif['total'] > 0)<span class="nb-bell-dot">{{ $notif['total'] > 99 ? '99+' : $notif['total'] }}</span>@endif
                </span>
                <div>
                    <strong>Notifikasi</strong>
                    <span class="nb-sub">
                        {{ $notif['total'] }} hal perlu ditindaklanjuti
                        @if ($notifUrgentTotal > 0) &middot; <b>{{ $notifUrgentTotal }} mendesak</b> @endif
                    </span>
                </div>
            </div>
            <a href="{{ route('admin.notifikasi.index') }}" class="nb-all">
                Lihat semua <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>

        @if ($notif['total'] === 0)
            <div class="nb-empty">
                <i class="fa-solid fa-circle-check"></i>
                <div><strong>Semua sudah beres</strong>Tidak ada notifikasi yang perlu ditindaklanjuti.</div>
            </div>
        @else
            {{-- Satu kotak per section; klik membuka halaman Notifikasi pada tab section itu --}}
            <div class="nb-grid">
                @foreach ($notifSections as $key => $label)
                    @continue(($notifCounts[$key] ?? 0) === 0)
                    @php $isUrgent = ($notifUrgent[$key] ?? 0) > 0; @endphp
                    <a href="{{ route('admin.notifikasi.index', ['tab' => $key]) }}" class="nb-tile {{ $isUrgent ? 'is-urgent' : '' }}">
                        <span class="nb-ico"><i class="fa-solid {{ $notifIcons[$key] ?? 'fa-bell' }}"></i></span>
                        <span class="nb-txt">
                            <strong>{{ $label }}</strong>
                            <span>{{ $isUrgent ? $notifUrgent[$key] . ' mendesak' : 'Perlu tindakan' }}</span>
                        </span>
                        <span class="nb-num">{{ $notifCounts[$key] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
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

            <div class="cp-lis dash-scroll">
                @forelse ($programAktif as $k)
                    @php
                        $barColor = match ($k->state) {
                            'ending' => '#D9A404',
                            'done'   => '#16A34A',
                            'closed' => '#9CA3AF',
                            default  => null,
                        };

                        // Pertemuan yang sudah berlangsung tapi jurnal guru belum dibuat (diisi controller)
                        $missing = (int) ($k->missing_sessions ?? 0);
                        $held    = (int) $k->done_sessions + $missing;   // total pertemuan yang sudah berlangsung

                        if ($k->total_sessions > 0) {
                            $donePct = min(100, round($k->done_sessions / $k->total_sessions * 100, 2));
                            $missPct = $missing > 0
                                ? min(100 - $donePct, round($missing / $k->total_sessions * 100, 2))
                                : 0;
                        } else {
                            $donePct = (float) $k->progress;
                            $missPct = 0;
                        }
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
                            <div class="cp-fill{{ $missPct > 0 ? ' has-miss' : '' }}" style="width: {{ $donePct }}%;{{ $barColor ? ' background:' . $barColor . ';' : '' }}"></div>
                            @if ($missPct > 0)
                                <div class="cp-miss" style="width: {{ $missPct }}%;" title="{{ $missing }} pertemuan sudah berlangsung, jurnal belum dibuat"></div>
                            @endif
                        </div>
                        <div class="cp-foot">
                            <span class="cp-sessions">Pertemuan
                                <b>{{ $held }}@if ($k->total_sessions > 0)/{{ $k->total_sessions }}@endif</b>
                            </span>
                            @if ($missing > 0)
                                <span class="cp-missing"><i class="fa-solid fa-circle-exclamation"></i> {{ $missing }} jurnal belum dibuat</span>
                            @endif
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

            @php
                // Item yang dipusatkan di tengah card:
                // 1) kelas yang sedang berlangsung, 2) kalau tidak ada: kelas terdekat berikutnya, 3) kalau tidak ada: kelas terakhir
                $focusIdx = $jadwalHariIni->filter(fn ($x) => $x->state === 'ongoing')->keys()->all();

                if (empty($focusIdx)) {
                    $next = $jadwalHariIni->first(fn ($x) => in_array($x->state, ['soon', 'upcoming']));
                    if ($next) {
                        $focusIdx = $jadwalHariIni->filter(fn ($x) => $x->start_label === $next->start_label)->keys()->all();
                    }
                }

                if (empty($focusIdx) && $jadwalHariIni->isNotEmpty()) {
                    $focusIdx = [$jadwalHariIni->count() - 1];
                }
            @endphp

            <div class="today-list dash-scroll" id="todayList">
                @forelse ($jadwalHariIni as $i => $s)
                    @php
                        $guru = $s->teacher->name ?? $s->class->teacher->name ?? '-';
                        $mode = $s->location_type ?: ($s->class->delivery_mode ?? '');
                    @endphp
                    <div class="today-item {{ $s->state === 'completed' ? 'completed' : (in_array($s->state, ['soon', 'ongoing']) ? 'soon' : '') }}"
                         @if (in_array($i, $focusIdx)) data-focus="1" @endif>
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

        <script>
            // Scroll list jadwal supaya kelas yang sedang berlangsung berada di tengah card
            (function () {
                const list = document.getElementById('todayList');
                if (!list) return;

                const items = list.querySelectorAll('[data-focus="1"]');
                if (!items.length) return;

                const first  = items[0];
                const last   = items[items.length - 1];
                const top    = first.offsetTop;
                const bottom = last.offsetTop + last.offsetHeight;
                const target = (top + bottom) / 2 - list.clientHeight / 2;

                list.scrollTop = Math.max(0, target);
            })();
        </script>

        {{-- =====================================================
        PAYMENT + TEACHER
        ===================================================== --}}
        {{-- <div class="dashboard-two-column"> --}}

            {{-- PAYMENT DUE --}}
            {{-- <div class="dashboard-card">
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
            </div> --}}

            {{-- TEACHER --}}
            {{-- <div class="dashboard-card">
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

        </div> --}}
    </div>

@endsection