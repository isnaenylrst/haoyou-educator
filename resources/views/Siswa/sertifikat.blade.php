@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Sertifikat Saya</h3>

  @forelse ($documents as $doc)
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
  @empty
    <div class="text-center py-8">
      <div class="text-4xl mb-3">🏅</div>
      <p class="text-sm font-medium text-black">Belum ada sertifikat</p>
      <p class="text-xs text-gray-500 mt-1">Sertifikat muncul setelah kamu menyelesaikan program dan admin menerbitkannya.</p>
    </div>
  @endforelse

  <div class="border border-dashed border-line rounded-xl p-4 mt-4 text-xs text-gray-500">
    Sertifikat hanya tersedia untuk siswa program <strong>HSK</strong>. Program non-HSK (kelas Reguler biasa) tidak menerbitkan sertifikat.
  </div>
</div>

<div class="alumni-banner bg-pale border border-gold rounded-xl p-4">
  <h3 class="font-semibold mb-1">Status: Alumni</h3>
  <p class="text-xs text-oliveDark">Kamu login sebagai alumni. Sertifikat lama tetap bisa dilihat &amp; diunduh kapan saja sebagai riwayat kelulusan.</p>
</div>

@endsection
