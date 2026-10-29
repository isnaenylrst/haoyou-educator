@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Sertifikat Saya</h3>

  <div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-4">
    Sertifikat tampil di sini setelah <strong>disetujui Kurikulum</strong>.
  </div>

  @forelse ($sertifikat as $item)
    <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🏅</div>
        <div>
          <div class="text-sm font-medium">
            Sertifikat — {{ $item->course_name ?: (optional(optional($item->enrollment)->class)->class_name ?? 'Kelas') }}
          </div>
          <div class="text-xs text-gray-500">
            {{ $item->start_date->translatedFormat('d M Y') }} s/d {{ $item->end_date->translatedFormat('d M Y') }}
            @if (!is_null($item->score)) · Nilai {{ $item->score }} @endif
            · No. {{ $item->certificate_number }}
          </div>
        </div>
      </div>

      @if ($item->file_path)
        <a href="{{ route('sertifikat.download', $item) }}"
           class="text-xs font-semibold bg-gold text-white rounded-full px-4 py-2 hover:brightness-105 transition whitespace-nowrap">
          Download PDF
        </a>
      @endif
    </div>
  @empty
    <div class="text-center py-8">
      <div class="text-4xl mb-3">🏅</div>
      <p class="text-sm font-medium text-black">Belum ada sertifikat</p>
      <p class="text-xs text-gray-500 mt-1">Sertifikat muncul setelah kamu menyelesaikan program dan disetujui Kurikulum.</p>
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