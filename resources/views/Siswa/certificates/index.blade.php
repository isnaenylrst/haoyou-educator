@extends('layouts.dashboard')

@section('dashboard-content')

@php
    $tab = request('tab') === 'mandiri' ? 'mandiri' : 'kelas';

    if ($tab === 'mandiri') {
        $ms           = \App\Support\SelfStudyReport::summary(auth()->id());
        $msc          = $ms['score'];
        $elig         = \App\Support\SelfStudyReport::eligibility(auth()->id(), $msc);
        $mandiriCerts = \App\Models\SelfStudyCertificate::where('user_id', auth()->id())->orderByDesc('issued_at')->get();
    }
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
       <div class="w-11 h-11 rounded-xl bg-[#ffe135] flex items-center justify-center text-lg">🏅</div>
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
        <div class="w-11 h-11 rounded-xl bg-[#ffe135] flex items-center justify-center text-lg">🏅</div>
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
{{-- TAB 2: SERTIFIKAT BELAJAR MANDIRI                            --}}
{{-- ============================================================ --}}
@if ($tab === 'mandiri')

@if (session('success'))
  <div class="bg-green-50 border border-green-200 text-green-800 text-xs rounded-xl p-3 mb-4">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl p-3 mb-4">{{ session('error') }}</div>
@endif

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-1">Sertifikat Belajar Mandiri</h3>
  <p class="text-xs text-gray-500 mb-4">
    Nilaimu dihitung dari bintang di Belajar Vocab dan Belajar Grammar. Sertifikat bisa dibuat
    setelah total nilaimu mencapai <strong>{{ $msc['passing'] }}</strong> dari 100.
  </p>

  {{-- Ringkasan bintang dan nilai --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
    <div class="rounded-xl border border-line p-4">
      <div class="text-xs text-gray-500 mb-1">⭐ Bintang vocab</div>
      <div class="text-xl font-bold">{{ number_format($ms['vocab_stars'], 0, ',', '.') }}
        <span class="text-xs font-medium text-gray-500">/ {{ number_format($ms['vocab_max'], 0, ',', '.') }}</span></div>
      <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden mt-2"><div class="h-full rounded-full bg-[#EF9F27]" style="width: {{ $msc['vocab_pct'] }}%"></div></div>
      <div class="text-xs text-gray-500 mt-1">{{ $msc['vocab_pct'] }}% · bobot {{ $msc['weight_vocab'] }}%</div>
    </div>
    <div class="rounded-xl border border-line p-4">
      <div class="text-xs text-gray-500 mb-1">⭐ Bintang grammar</div>
      <div class="text-xl font-bold">{{ number_format($ms['grammar_stars'], 0, ',', '.') }}
        <span class="text-xs font-medium text-gray-500">/ {{ number_format($ms['grammar_max'], 0, ',', '.') }}</span></div>
      <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden mt-2"><div class="h-full rounded-full bg-[#EF9F27]" style="width: {{ $msc['grammar_pct'] }}%"></div></div>
      <div class="text-xs text-gray-500 mt-1">{{ $msc['grammar_pct'] }}% · bobot {{ $msc['weight_grammar'] }}%</div>
    </div>
    <div class="rounded-xl bg-pale border border-gold p-4">
      <div class="text-xs text-gray-600 mb-1">🏆 Total capaian</div>
      <div class="text-xl font-bold">{{ $msc['total'] }}% <span class="text-xs font-medium text-gray-500">/ 100</span></div>
      <div class="h-1.5 rounded-full bg-white overflow-hidden mt-2"><div class="h-full rounded-full bg-ink" style="width: {{ $msc['total'] }}%"></div></div>
      <div class="text-xs text-gray-600 mt-1">
        {{ $msc['lulus'] ? 'Predikat: ' . $msc['predikat'] : 'Belum lulus · minimal ' . $msc['passing'] }}
      </div>
    </div>
  </div>

  {{-- Kartu penerbitan --}}
  <div class="flex items-center justify-between gap-3 py-3 border-t border-line">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-[#ffe135] flex items-center justify-center text-lg">🏅</div>
      <div>
        <div class="text-sm font-medium">Sertifikat Belajar Mandiri</div>
        <div class="text-xs text-gray-500">
          @if ($elig['ok'])
            Syarat terpenuhi. Sertifikat dengan nilai {{ $msc['total'] }} ({{ $msc['predikat'] }}) siap dibuat.
          @else
            {{ $elig['reason'] }}
          @endif
        </div>
      </div>
    </div>

    @if ($elig['ok'])
      <form method="POST" action="{{ route('student.mandiri-certificates.store') }}">
        @csrf
        <button type="submit"
                class="text-xs font-semibold bg-ink text-white rounded-full px-4 py-2 hover:opacity-90 transition whitespace-nowrap">
          Buat Sertifikat
        </button>
      </form>
    @else
      <span class="text-xs text-gray-400 whitespace-nowrap">🔒 Belum tersedia</span>
    @endif
  </div>
</div>

{{-- Daftar sertifikat yang sudah terbit --}}
<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-1">Sertifikat saya</h3>
  <p class="text-xs text-gray-500 mb-4">Sertifikat yang sudah terbit tidak berubah walaupun kamu belajar lagi. Kalau nilaimu naik, kamu bisa membuat sertifikat baru.</p>

  @forelse ($mandiriCerts as $mc)
    <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-[#ffe135] flex items-center justify-center text-lg">🏅</div>
        <div>
          <div class="text-sm font-medium">No. {{ $mc->certificate_number }}</div>
          <div class="text-xs text-gray-500">
            Diterbitkan {{ $mc->issued_at->format('d/m/Y') }} ·
            Total score {{ $mc->total_score }} / 100 · {{ $mc->predicate }}
          </div>
        </div>
      </div>
      <a href="{{ route('student.mandiri-certificates.download', $mc) }}"
         class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition whitespace-nowrap">
        Download PDF
      </a>
    </div>
  @empty
    <div class="text-center py-6">
      <div class="text-4xl mb-3">🏅</div>
      <p class="text-sm font-medium text-black">Belum ada sertifikat</p>
      <p class="text-xs text-gray-500 mt-1">Kumpulkan bintang di Belajar Vocab dan Belajar Grammar sampai nilaimu mencapai {{ $msc['passing'] }}.</p>
    </div>
  @endforelse
</div>

@endif {{-- end tab mandiri --}}


<div class="alumni-banner bg-pale border border-gold rounded-xl p-4">
  <h3 class="font-semibold mb-1">Status: Alumni</h3>
  <p class="text-xs text-oliveDark">Kamu login sebagai alumni. Sertifikat lama tetap bisa dilihat &amp; diunduh kapan saja sebagai riwayat hasil belajar.</p>
</div>

@endsection