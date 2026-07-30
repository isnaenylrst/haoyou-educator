@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-5">
  Sistem mengirim notifikasi otomatis setiap kali ada jadwal kelas (online/offline) yang akan segera dimulai. Notifikasi juga dikirim ke email kamu.
</div>

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Belum Dibaca</h3>
  @foreach ($belumDibaca as $n)
    <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🔔</div>
        <div>
          <div class="text-sm font-medium">{{ $n['judul'] }}</div>
          <div class="text-xs text-gray-500">{{ $n['ket'] }}</div>
        </div>
      </div>
      <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-[#e2ecd7] text-[#4b6b2f]">Baru</span>
    </div>
  @endforeach
</div>

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Sudah Dibaca</h3>
  @foreach ($sudahDibaca as $n)
    <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">✅</div>
        <div>
          <div class="text-sm font-medium">{{ $n['judul'] }}</div>
          <div class="text-xs text-gray-500">{{ $n['ket'] }}</div>
        </div>
      </div>
      <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-pale text-oliveDark">Selesai</span>
    </div>
  @endforeach
</div>

<div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500 bg-white">
  🔁 Notifikasi ini dijalankan otomatis di latar belakang (cron job), memuat: jam kelas &amp; nama Laoshi, link Zoom / nomor ruangan, materi terakhir yang dipelajari, dan materi yang harus disiapkan sebelum kelas.
</div>

@endsection