@extends('layouts.dashboard')

@section('dashboard-content')

<div class="flex items-center gap-5 mb-6">
  <div class="w-[76px] h-[76px] rounded-full bg-pale border-2 border-ink flex items-center justify-center text-2xl">👩‍🎓</div>
  <div>
    <h3 class="text-lg font-semibold">{{ auth()->user()->nama_lengkap ?? 'Dina Anggraini' }}</h3>
    <p class="text-xs text-gray-500">HSK 2 · Hybrid · Bergabung Jan 2026</p>
  </div>
</div>

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Riwayat Perolehan Poin</h3>
  @foreach ($riwayatPoin as $riwayat)
    <div class="flex items-center justify-between py-2.5 border-b border-line last:border-0">
      <div>
        <div class="text-sm font-medium">{{ $riwayat['poin'] }} poin</div>
        <div class="text-xs text-gray-500">{{ $riwayat['ket'] }}</div>
      </div>
      <span class="text-[11px] font-semibold px-3 py-1 rounded-full {{ $riwayat['tipe'] === 'tambah' ? 'bg-[#e2ecd7] text-[#4b6b2f]' : 'bg-pale text-oliveDark' }}">
        {{ $riwayat['tipe'] === 'tambah' ? 'Tambah' : 'Kurang' }}
      </span>
    </div>
  @endforeach
</div>

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Akses Lainnya</h3>
  <div class="flex items-center justify-between py-3 border-b border-line">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🏅</div>
      <div>
        <div class="text-sm font-medium">Sertifikat</div>
        <div class="text-xs text-gray-500">Download sertifikat kelulusan program HSK</div>
      </div>
    </div>
    <a href="{{ route('sertifikat.index') }}" class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition">Buka Halaman →</a>
  </div>
  <div class="flex items-center justify-between py-3">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">📊</div>
      <div>
        <div class="text-sm font-medium">Progress Report</div>
        <div class="text-xs text-gray-500">Lihat laporan perkembangan belajar berkala</div>
      </div>
    </div>
    <a href="{{ route('progresreport.index') }}" class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition">Buka Halaman →</a>
  </div>
</div>

<form method="POST" action="{{ route('logout') }}">
  @csrf
  <button type="submit" class="text-sm font-semibold border border-ink rounded-full px-6 py-3 hover:bg-ink hover:text-white transition">
    Logout — Kembali ke Landing Page
  </button>
</form>

@endsection