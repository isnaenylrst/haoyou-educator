@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  @foreach ($kelasSaya as $kelas)
    <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">
          {{ $kelas['platform'] === 'online' ? '🀄' : '📖' }}
        </div>
        <div>
          <div class="text-sm font-medium">{{ $kelas['judul'] }}</div>
          <div class="text-xs text-gray-500">{{ $kelas['waktu'] }} · {{ ucfirst($kelas['platform']) }} · {{ $kelas['detail'] }}</div>
        </div>
      </div>
      @if ($kelas['platform'] === 'online')
        <button class="text-xs font-semibold border border-ink rounded-full  px-4 py-2 hover:bg-ink hover:text-white transition">Join Zoom →</button>
      @else
        <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-pale text-oliveDark">{{ $kelas['detail'] }}</span>
      @endif
    </div>
  @endforeach
</div>

<div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500 bg-white">
  📍 Untuk kelas Offline, sistem menampilkan nomor ruangan. Untuk kelas Online, sistem menampilkan link Zoom otomatis.
</div>

@endsection