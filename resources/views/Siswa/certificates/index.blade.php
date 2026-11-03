@extends('layouts.dashboard')

@section('dashboard-content')

@php
    $tab = request('tab') === 'mandiri' ? 'mandiri' : 'kelas';

    // ===== DATA DUMMY (nanti diganti dari controller) =====
    $mandiriItems = [
        ['icon' => '📖', 'label' => 'Test Belajar Vocab',   'desc' => 'Selesaikan seluruh rangkaian test Vocab',   'done' => 8, 'total' => 10, 'soon' => false],
        ['icon' => '📝', 'label' => 'Test Belajar Grammar', 'desc' => 'Selesaikan seluruh rangkaian test Grammar', 'done' => 3, 'total' => 10, 'soon' => false],
        ['icon' => '✏️', 'label' => 'Kalimat Rumpang',      'desc' => 'Latihan melengkapi kalimat',                'done' => 0, 'total' => 0,  'soon' => true],
    ];
    $aktif     = collect($mandiriItems)->where('soon', false);
    $totalDone = $aktif->sum('done');
    $totalAll  = $aktif->sum('total');
    $pctAll    = $totalAll > 0 ? round($totalDone / $totalAll * 100) : 0;
    $mandiriOk = $totalAll > 0 && $totalDone >= $totalAll;
@endphp

{{-- ===== TAB ===== --}}
<div class="flex gap-2 mb-5 border-b border-line">
  <a href="{{ route('sertifikat.index') }}"
     class="px-4 py-2.5 text-sm -mb-px border-b-2 {{ $tab === 'kelas' ? 'border-ink font-semibold text-ink' : 'border-transparent text-gray-500 hover:text-ink' }}">
    🎓 Sertifikat Kelas
  </a>
  <a href="{{ route('sertifikat.index', ['tab' => 'mandiri']) }}"
     class="px-4 py-2.5 text-sm -mb-px border-b-2 {{ $tab === 'mandiri' ? 'border-ink font-semibold text-ink' : 'border-transparent text-gray-500 hover:text-ink' }}">
    📚 Sertifikat Belajar Mandiri
  </a>
</div>

{{-- ============================================================ --}}
{{-- TAB 1: SERTIFIKAT KELAS                                      --}}
{{-- ============================================================ --}}
@if ($tab === 'kelas')

@if (session('success'))
  <div class="bg-green-50 border border-green-200 text-green-800 text-xs rounded-xl p-3 mb-4">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl p-3 mb-4">{{ session('error') }}</div>
@endif

{{-- ===== Buat sertifikat otomatis (program HSK) ===== --}}
<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-1">Sertifikat HSK Saya</h3>
  <p class="text-xs text-gray-500 mb-4">
    Nama pada sertifikat:
    <strong>{{ \Illuminate\Support\Facades\DB::table('students')->where('user_id', auth()->id())->value('name') ?: (auth()->user()->name ?? '-') }}</strong>
    (otomatis dari akunmu).
    Sertifikat hanya bisa dibuat satu kali untuk setiap level.
  </p>

  @forelse ($levels as $row)
    @php
      $level = $row['level'];
      $cert  = $row['certificate'];
      $check = $row['check'];
    @endphp

    <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🏅</div>
        <div>
          <div class="text-sm font-medium">Sertifikat {{ $level->level_name }}</div>

          @if ($cert)
            <div class="text-xs text-gray-500">
              Diterbitkan {{ $cert->issued_at->format('d/m/Y') }} · No. {{ $cert->certificate_number }}
            </div>
            <div class="text-xs mt-0.5 {{ $cert->isPassed() ? 'text-green-700' : 'text-red-700' }}">
              Total score {{ $cert->total_score }} / {{ $cert->max_total }} ·
              @if ($cert->isPassed())
                Lulus · memenuhi standar {{ $cert->level_name }}
              @else
                Tidak lulus · di bawah nilai standar {{ $cert->level_name }} ({{ $cert->passing_score }})
              @endif
            </div>
          @elseif ($check['ok'])
            <div class="text-xs text-gray-500">Syarat terpenuhi. Sertifikat siap dibuat.</div>
          @else
            <div class="text-xs text-gray-500">{{ $check['reason'] }}</div>
          @endif
        </div>
      </div>

      @if ($cert)
        <a href="{{ route('student.certificates.download', $cert) }}"
           class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition whitespace-nowrap">
          Download PDF
        </a>
      @elseif ($check['ok'])
        <form method="POST" action="{{ route('student.certificates.store') }}">
          @csrf
          <input type="hidden" name="program_level_id" value="{{ $level->id }}">
          <button type="submit"
                  class="text-xs font-semibold bg-ink text-white rounded-full px-4 py-2 hover:opacity-90 transition whitespace-nowrap">
            Buat Sertifikat
          </button>
        </form>
      @else
        <span class="text-xs text-gray-400 whitespace-nowrap">Belum tersedia</span>
      @endif
    </div>
  @empty
    <div class="text-center py-8">
      <div class="text-4xl mb-3">🏅</div>
      <p class="text-sm font-medium text-black">Belum ada level HSK</p>
      <p class="text-xs text-gray-500 mt-1">Sertifikat muncul setelah kamu menyelesaikan program HSK dan mengikuti ujian akhir.</p>
    </div>
  @endforelse

  <div class="border border-dashed border-line rounded-xl p-4 mt-4 text-xs text-gray-500">
    Sertifikat hanya tersedia untuk siswa program <strong>HSK</strong>. Program non-HSK (kelas Reguler biasa) tidak menerbitkan sertifikat.
  </div>
</div>

{{-- ===== Sertifikat lama (upload manual kurikulum), tetap dipertahankan untuk alumni ===== --}}
@if ($documents->isNotEmpty())
<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Riwayat Sertifikat Lama</h3>

  @foreach ($documents as $doc)
    <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🏅</div>
        <div>
          <div class="text-sm font-medium">{{ $doc->title }}</div>
          <div class="text-xs text-gray-500">
            Diterbitkan {{ ($doc->uploaded_at ?? $doc->created_at)?->format('d/m/Y') }}
            @if ($doc->programLevel)
              · {{ $doc->programLevel->program?->program_name }} {{ $doc->programLevel->level_name }}
            @endif
          </div>
          @if ($doc->description)
            <div class="text-xs text-gray-500 mt-0.5">{{ $doc->description }}</div>
          @endif
        </div>
      </div>

      <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" download
         class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition whitespace-nowrap">
        Download PDF
      </a>
    </div>
  @endforeach
</div>
@endif

@endif {{-- end tab kelas --}}


{{-- ============================================================ --}}
{{-- TAB 2: SERTIFIKAT BELAJAR MANDIRI (DUMMY)                    --}}
{{-- ============================================================ --}}
@if ($tab === 'mandiri')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-1">Sertifikat Belajar Mandiri</h3>
  <p class="text-xs text-gray-500 mb-4">
    Sertifikat terbit <strong>otomatis</strong> setelah kamu menyelesaikan seluruh rangkaian test
    di Belajar Vocab, Belajar Grammar, dan Kalimat Rumpang.
  </p>

  {{-- Progres keseluruhan --}}
  <div class="rounded-xl bg-pale border border-gold p-4 mb-5">
    <div class="flex items-center justify-between text-sm mb-2">
      <span class="font-semibold">Progres keseluruhan</span>
      <span class="font-semibold">{{ $pctAll }}%</span>
    </div>
    <div class="h-2.5 rounded-full bg-white overflow-hidden">
      <div class="h-full rounded-full bg-ink" style="width: {{ $pctAll }}%"></div>
    </div>
    <div class="text-xs text-gray-500 mt-2">{{ $totalDone }} dari {{ $totalAll }} test selesai</div>
  </div>

  {{-- Syarat per modul --}}
  @foreach ($mandiriItems as $m)
    @php
        $pct = $m['total'] > 0 ? round($m['done'] / $m['total'] * 100) : 0;
        $fin = !$m['soon'] && $m['total'] > 0 && $m['done'] >= $m['total'];
    @endphp
    <div class="flex items-center gap-3 py-3 border-b border-line last:border-0 {{ $m['soon'] ? 'opacity-60' : '' }}">
      <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg flex-shrink-0">{{ $m['icon'] }}</div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2">
          <div class="text-sm font-medium">{{ $m['label'] }}</div>
          @if ($m['soon'])
            <span class="text-[11px] font-semibold bg-gray-100 text-gray-500 rounded-full px-2.5 py-0.5">Segera hadir</span>
          @elseif ($fin)
            <span class="text-[11px] font-semibold bg-green-50 text-green-700 rounded-full px-2.5 py-0.5">✓ Selesai</span>
          @else
            <span class="text-xs text-gray-500">{{ $m['done'] }}/{{ $m['total'] }}</span>
          @endif
        </div>
        <div class="text-xs text-gray-500 mb-1.5">{{ $m['desc'] }}</div>
        @unless ($m['soon'])
          <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
            <div class="h-full rounded-full {{ $fin ? 'bg-green-600' : 'bg-ink' }}" style="width: {{ $pct }}%"></div>
          </div>
        @endunless
      </div>
    </div>
  @endforeach
</div>

{{-- Kartu sertifikat --}}
<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <div class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🏅</div>
      <div>
        <div class="text-sm font-medium">Sertifikat Belajar Mandiri</div>
        <div class="text-xs text-gray-500">
          @if ($mandiriOk)
            Semua syarat terpenuhi. Sertifikat sedang diproses otomatis.
          @else
            Belum tersedia. Selesaikan semua test untuk membukanya.
          @endif
        </div>
      </div>
    </div>

    @if ($mandiriOk)
      <a href="#" class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition whitespace-nowrap">Download PDF</a>
    @else
      <span class="text-xs text-gray-400 whitespace-nowrap">🔒 Terkunci</span>
    @endif
  </div>

  <div class="border border-dashed border-line rounded-xl p-4 mt-4 text-xs text-gray-500">
    Tampilan ini masih <strong>data contoh</strong>. Progres akan terhubung ke hasil test
    Vocab, Grammar, dan Kalimat Rumpang yang sebenarnya.
  </div>
</div>

@endif {{-- end tab mandiri --}}


<div class="alumni-banner bg-pale border border-gold rounded-xl p-4">
  <h3 class="font-semibold mb-1">Status: Alumni</h3>
  <p class="text-xs text-oliveDark">Kamu login sebagai alumni. Sertifikat lama tetap bisa dilihat &amp; diunduh kapan saja sebagai riwayat hasil belajar.</p>
</div>

@endsection