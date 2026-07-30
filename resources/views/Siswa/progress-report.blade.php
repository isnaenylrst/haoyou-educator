@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-4">
    Laporan perkembangan belajar diterbitkan otomatis secara berkala — setiap <strong>3 bulan</strong> untuk kelas Reguler, dan setiap <strong>2 bulan</strong> untuk kelas HSK.
  </div>

  @foreach ($laporan as $item)
    <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">📊</div>
        <div>
          <div class="text-sm font-medium">{{ $item['judul'] }}</div>
          <div class="text-xs text-gray-500">{{ $item['periode'] }}</div>
        </div>
      </div>
      <button class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition">Lihat Laporan</button>
    </div>
  @endforeach

  <div class="flex items-center justify-between py-3 border-t border-line mt-1">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">⏳</div>
      <div>
        <div class="text-sm font-medium">Progress Report Berikutnya</div>
        <div class="text-xs text-gray-500">Reguler: terbit Apr 2026 · HSK: terbit Mar 2026</div>
      </div>
    </div>
    <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-pale text-oliveDark">Belum Terbit</span>
  </div>
</div>

<div class="alumni-banner bg-pale border border-gold rounded-xl p-4">
  <h3 class="font-semibold mb-1">Status: Alumni</h3>
  <p class="text-xs text-oliveDark">Riwayat progress report tetap bisa diakses walau kamu sudah menjadi alumni dan tidak lagi memiliki akses ke kelas, materi, atau video pembelajaran.</p>
</div>

@endsection