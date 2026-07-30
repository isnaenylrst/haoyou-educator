@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">
  <h3 class="font-semibold mb-4">Daftar Video Pembelajaran</h3>
  @foreach ($videoList as $video)
    <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">▶️</div>
        <div>
          <div class="text-sm font-medium">{{ $video['judul'] }}</div>
          <div class="text-xs text-gray-500">{{ $video['durasi'] }} · {{ $video['status'] === 'ditonton' ? 'Ditonton' : 'Belum ditonton' }}</div>
        </div>
      </div>
      @if ($video['status'] === 'ditonton')
        <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-[#e2ecd7] text-[#4b6b2f]">+{{ $video['poin'] }} poin</span>
      @else
        <button class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition">Tonton</button>
      @endif
    </div>
  @endforeach

  <div class="flex items-center justify-between py-3 border-t border-line mt-1">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">📄</div>
      <div>
        <div class="text-sm font-medium">Modul PDF — Unit 1</div>
        <div class="text-xs text-gray-500">Sesuai level siswa</div>
      </div>
    </div>
    <button class="text-xs font-semibold bg-ink text-white rounded-full px-4 py-2 hover:bg-oliveDark transition">Download</button>
  </div>
</div>

<div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500 bg-white">
  🔒 Jika level modul tidak sesuai, sistem menampilkan pesan "Modul Tidak Bisa Diakses".
</div>

@endsection