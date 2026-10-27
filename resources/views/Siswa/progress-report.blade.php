@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-4">
    Progress report tampil di sini setelah <strong>disetujui Kurikulum</strong>. Laporan yang masih diisi guru atau sedang direview belum ditampilkan.
  </div>

  @forelse ($laporan as $item)
    <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">📊</div>
        <div>
          <div class="text-sm font-medium">{{ $item->report_type }}{{ optional(optional($item->enrollment)->class)->class_name ? ' — ' . $item->enrollment->class->class_name : '' }}</div>
          <div class="text-xs text-gray-500">
            {{ $item->report_period }} · Disetujui {{ optional($item->reviewed_at)->translatedFormat('d M Y') }}
            @if (!is_null($item->average_score)) · Rata-rata nilai {{ $item->average_score }} @endif
          </div>
        </div>
      </div>
      @if ($item->file_path)
        <a href="{{ route('progresreport.download', $item) }}" class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition">Unduh PDF</a>
      @endif
    </div>
  @empty
    <div class="py-6 text-center text-xs text-gray-500">Belum ada progress report yang disetujui.</div>
  @endforelse
</div>

<div class="alumni-banner bg-pale border border-gold rounded-xl p-4">
  <h3 class="font-semibold mb-1">Status: Alumni</h3>
  <p class="text-xs text-oliveDark">Riwayat progress report tetap bisa diakses walau kamu sudah menjadi alumni dan tidak lagi memiliki akses ke kelas, materi, atau video pembelajaran.</p>
</div>

@endsection