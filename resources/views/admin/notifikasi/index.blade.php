@extends('admin.app')

@section('title', 'Notifikasi')

@push('styles')
    {{-- Samakan path dengan yang dipakai halaman admin lain (calonsiswa.blade.php) --}}
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <style>
        :root {
            --nf-red: #dc2626;      --nf-red-soft: #fef2f2;
            --nf-blue: #2563eb;     --nf-blue-soft: #eff6ff;
            --nf-line: #edeef1;
            --nf-muted: #6b7280;
            --nf-faint: #9ca3af;
        }

        /* ---- Header halaman: lebih ringan ---- */
        .dashboard-header h1 { font-size: 22px; font-weight: 600; letter-spacing: -.01em; }
        .dashboard-header p { font-size: 13px; font-weight: 400; }
        .dashboard-header-actions .btn { font-size: 12.5px; font-weight: 500; }

        /* ---- Pill kategori ---- */
        .nf-pills { display: flex; gap: 8px; overflow-x: auto; padding: 2px 2px 8px; margin: 6px 0 14px; scrollbar-width: thin; }
        .nf-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 999px;
                   border: 1px solid #e5e7eb; background: #fff; color: #4b5563; font-size: 12.5px; font-weight: 500;
                   text-decoration: none; white-space: nowrap; transition: border-color .15s, background-color .15s, color .15s; }
        .nf-pill:hover { border-color: var(--yellow); color: #111; }
        .nf-pill.active { background: var(--yellow); border-color: var(--yellow); color: #111; font-weight: 600; }
        .nf-pill i { font-size: 12px; opacity: .7; }
        .nf-pill-count { font-size: 11px; font-weight: 600; line-height: 1; padding: 3px 7px; border-radius: 999px; background: rgba(0,0,0,.07); }
        .nf-pill.active .nf-pill-count { background: rgba(255,255,255,.65); }
        .nf-pill-count.red { background: #fde2e2; color: #c0392b; }

        /* ---- Bar filter ---- */
        .nf-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .nf-count { font-size: 12.5px; color: var(--nf-muted); }
        .nf-count strong { color: #111827; font-weight: 600; }
        .nf-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 0; }
        .nf-filter-label { font-size: 12px; font-weight: 600; color: var(--nf-muted); display: inline-flex; align-items: center; gap: 6px; }
        .nf-input { height: 36px; padding: 0 10px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff;
                    color: #374151; font-family: inherit; font-size: 12.5px; outline: none; }
        .nf-input:focus { border-color: var(--yellow); box-shadow: 0 0 0 3px rgba(250, 204, 21, .18); }
        .nf-apply { height: 36px; padding: 0 16px; border-radius: 10px; border: 1px solid var(--yellow); background: var(--yellow);
                    color: #111827; font-family: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; }
        .nf-apply:hover { filter: brightness(.96); }
        .nf-reset { font-size: 12.5px; color: var(--nf-muted); text-decoration: underline; }

        /* ---- Daftar: satu panel, dipisah per prioritas ---- */
        .nf-feed { background: #fff; border: 1px solid var(--nf-line); border-radius: 16px; overflow: hidden; }

        .nf-group { display: flex; align-items: center; gap: 10px; padding: 11px 22px; background: #fafafb;
                    border-bottom: 1px solid var(--nf-line); font-size: 12px; font-weight: 600; color: #374151; }
        .nf-feed > .nf-group:not(:first-child) { border-top: 1px solid var(--nf-line); }
        .nf-group-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--nf-blue); }
        .nf-group.p3 .nf-group-dot { background: var(--nf-red); }
        .nf-group.p2 .nf-group-dot { background: #f59e0b; }
        .nf-group-count { margin-left: auto; font-size: 11.5px; font-weight: 400; color: var(--nf-faint); }

        .nf-item { position: relative; display: flex; align-items: flex-start; gap: 16px; padding: 18px 22px 18px 24px;
                   border-bottom: 1px solid var(--nf-line); text-decoration: none; color: inherit;
                   transition: background-color .15s; }
        .nf-item:last-child { border-bottom: 0; }
        .nf-item::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: transparent; }
        .nf-item.p3::before { background: var(--nf-red); }
        .nf-item.p2::before { background: #f59e0b; }
        .nf-item:hover { background: #fbfbfc; color: inherit; }
        .nf-item:focus-visible { outline: 2px solid var(--yellow); outline-offset: -2px; }

        .nf-icon { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; flex-shrink: 0; font-size: 14px; }
        .nf-icon.p3 { background: var(--nf-red-soft); color: var(--nf-red); }
        .nf-icon.p2 { background: var(--yellow-soft); color: var(--gold-dark); }
        .nf-icon.p1 { background: var(--nf-blue-soft); color: var(--nf-blue); }

        .nf-body { flex: 1; min-width: 0; }
        .nf-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px; }
        .nf-chip { font-size: 11px; font-weight: 500; padding: 2px 9px; border-radius: 999px; background: #f3f4f6; color: #4b5563; }
        .nf-chip.hot  { background: var(--nf-red-soft); color: var(--nf-red); }
        .nf-chip.warm { background: var(--yellow-soft); color: var(--gold-badge-text); }
        .nf-title { font-size: 13.5px; font-weight: 600; color: #1f2937; line-height: 1.4; }
        .nf-text { font-size: 12.5px; font-weight: 400; color: #6b7280; line-height: 1.55; margin-top: 2px;
                   display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .nf-side { display: flex; align-items: center; gap: 12px; flex-shrink: 0; text-align: right; }
        .nf-when { display: flex; flex-direction: column; gap: 2px; }
        .nf-when b { font-size: 12px; font-weight: 500; color: #4b5563; }
        .nf-when span { font-size: 11px; color: var(--nf-faint); }
        .nf-go { color: #d1d5db; font-size: 12px; transition: color .15s, transform .15s; }
        .nf-item:hover .nf-go { color: #111827; transform: translateX(2px); }

        .nf-empty { text-align: center; padding: 70px 20px; color: var(--nf-muted); font-size: 12.5px; }
        .nf-empty i { display: grid; place-items: center; width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 50%;
                      background: #ecfdf5; color: #16a34a; font-size: 24px; }
        .nf-empty strong { display: block; color: #111827; font-size: 14px; font-weight: 600; margin-bottom: 2px; }

        @media (max-width: 640px) {
            .nf-item { flex-wrap: wrap; padding: 16px 16px 16px 18px; }
            .nf-side { width: 100%; justify-content: space-between; text-align: left; padding-left: 58px; }
            .nf-when { flex-direction: row; gap: 8px; align-items: baseline; }
            .nf-group { padding: 10px 16px; }
        }
    </style>
@endpush

@section('content')
@php
    $icons = [
        'siswa' => 'fa-user-graduate', 'kelas' => 'fa-chalkboard-user', 'jurnal' => 'fa-book-open',
        'pembayaran' => 'fa-money-bill-wave', 'progress' => 'fa-file-lines',
        'pengajuan' => 'fa-receipt', 'sop' => 'fa-clipboard-list',
    ];
    $levelLabel = [3 => 'Mendesak', 2 => 'Perlu tindakan', 1 => 'Info'];
    $keep       = ['sort' => $sort === 'priority' ? null : $sort, 'from' => $from, 'to' => $to];
    $tabUrl     = fn ($t) => route('admin.notifikasi.index', array_filter($keep) + ($t === 'all' ? [] : ['tab' => $t]));
    $groups     = $sort === 'priority'
        ? $items->groupBy('priority')->sortKeysDesc()
        : collect([0 => $items]);
@endphp

<div class="dashboard-header">
    <div>
        <h1>Notifikasi</h1>
        <p>Hal yang perlu ditindaklanjuti admin. Notifikasi hilang otomatis setelah masalahnya selesai.</p>
    </div>
    <div class="dashboard-header-actions">
        <a href="{{ request()->fullUrl() }}" class="btn btn-secondary"><i class="fa-solid fa-rotate"></i> Muat ulang</a>
    </div>
</div>

{{-- Pill kategori --}}
<div class="nf-pills">
    <a href="{{ $tabUrl('all') }}" class="nf-pill {{ $tab === 'all' ? 'active' : '' }}">
        Semua <span class="nf-pill-count">{{ $total }}</span>
    </a>
    @foreach($sections as $key => $label)
        <a href="{{ $tabUrl($key) }}" class="nf-pill {{ $tab === $key ? 'active' : '' }}">
            <i class="fa-solid {{ $icons[$key] ?? 'fa-bell' }}"></i>
            {{ $label }}
            @if(($counts[$key] ?? 0) > 0)
                <span class="nf-pill-count {{ ($urgent[$key] ?? 0) > 0 ? 'red' : '' }}">{{ $counts[$key] }}</span>
            @endif
        </a>
    @endforeach
</div>

{{-- Bar filter --}}
<div class="nf-bar">
    <span class="nf-count">Menampilkan <strong>{{ $items->count() }}</strong> notifikasi</span>

    <form method="GET" action="{{ route('admin.notifikasi.index') }}" class="nf-filter">
        @if($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
        <span class="nf-filter-label"><i class="fa-solid fa-sliders"></i> Filter</span>
        <select name="sort" class="nf-input" onchange="this.form.submit()">
            <option value="priority" @selected($sort === 'priority')>Prioritas</option>
            <option value="newest"   @selected($sort === 'newest')>Terbaru dulu</option>
            <option value="oldest"   @selected($sort === 'oldest')>Terlama dulu</option>
        </select>
        <input type="date" name="from" value="{{ $from }}" class="nf-input" title="Dari tanggal">
        <span class="x-muted">s/d</span>
        <input type="date" name="to" value="{{ $to }}" class="nf-input" title="Sampai tanggal">
        <button type="submit" class="nf-apply">Terapkan</button>
        @if($from || $to || $sort !== 'priority')
            <a href="{{ route('admin.notifikasi.index', $tab === 'all' ? [] : ['tab' => $tab]) }}" class="nf-reset">Reset</a>
        @endif
    </form>
</div>

<div class="nf-feed">
    @if($items->isEmpty())
        <div class="nf-empty">
            <i class="fa-solid fa-check"></i>
            <strong>Semua sudah beres</strong>
            Tidak ada notifikasi{{ $tab === 'all' ? '' : ' untuk ' . $sections[$tab] }}.
        </div>
    @else
        @foreach($groups as $priority => $list)
            @if($sort === 'priority')
                <div class="nf-group p{{ $priority }}">
                    <span class="nf-group-dot"></span>
                    {{ $levelLabel[$priority] ?? 'Lainnya' }}
                    <span class="nf-group-count">{{ $list->count() }} notifikasi</span>
                </div>
            @endif

            @foreach($list as $n)
                @php $isMidnight = $n['date']->format('H:i') === '00:00'; @endphp
                <a href="{{ $n['url'] }}" class="nf-item p{{ $n['priority'] }}">
                    <div class="nf-icon p{{ $n['priority'] }}">
                        <i class="fa-solid {{ $icons[$n['section']] ?? 'fa-bell' }}"></i>
                    </div>

                    <div class="nf-body">
                        <div class="nf-top">
                            <span class="nf-chip">{{ $sections[$n['section']] }}</span>
                            @if($n['priority'] === 3)
                                <span class="nf-chip hot">Mendesak</span>
                            @elseif($n['priority'] === 2)
                                <span class="nf-chip warm">Perlu tindakan</span>
                            @endif
                        </div>
                        <div class="nf-title">{{ $n['title'] }}</div>
                        <div class="nf-text">{{ $n['message'] }}</div>
                    </div>

                    <div class="nf-side">
                        <div class="nf-when">
                            <b>{{ $n['date']->translatedFormat($isMidnight ? 'd M Y' : 'd M Y • H:i') }}</b>
                            <span>{{ $n['date']->diffForHumans() }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-right nf-go"></i>
                    </div>
                </a>
            @endforeach
        @endforeach
    @endif
</div>
@endsection