@extends('layouts.dashboard')

@section('dashboard-content')

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

<div class="alumni-banner bg-pale border border-gold rounded-xl p-4">
  <h3 class="font-semibold mb-1">Status: Alumni</h3>
  <p class="text-xs text-oliveDark">Kamu login sebagai alumni. Sertifikat lama tetap bisa dilihat &amp; diunduh kapan saja sebagai riwayat hasil belajar.</p>
</div>

@endsection