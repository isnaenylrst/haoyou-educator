@extends('layouts.dashboard')

@section('page-heading')
  <h1 class="mt-2 text-[28px] md:text-[34px] font-bold leading-tight">Materi Huahua</h1>
  <p class="text-sm text-[#6b6652] mt-1">Materi belajar dari Kurikulum. Kosakatanya sudah masuk ke Vocab List dan Test.</p>
@endsection

@push('styles') @include('Siswa.vocab._styles') @endpush

@section('dashboard-content')

@forelse ($groups as $groupName => $materials)
  <div class="bg-white border border-line rounded-2xl p-5 mb-5">
    <h3 class="font-semibold mb-4">{{ trim($groupName) ?: 'Umum' }}</h3>

    @foreach ($materials as $material)
      <div class="flex items-center justify-between gap-3 py-4 border-b border-line last:border-0">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🌸</div>
          <div>
            <div class="text-sm font-medium text-black">{{ $material->title }}</div>
            <div class="text-xs text-gray-500 mt-1">
              Materi {{ $material->meeting_number }}
              @if ($material->huahua_words_count > 0) · {{ $material->huahua_words_count }} kosakata @endif
            </div>
            @if ($material->description)
              <div class="text-xs text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($material->description, 140) }}</div>
            @endif
          </div>
        </div>

        <div class="flex items-center gap-2">
          @if ($material->is_pdf)
            <a href="{{ route('student.vocab.materi.lihat', $material) }}" target="_blank"
               class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition">Buka →</a>
          @endif
          <a href="{{ route('student.vocab.materi.unduh', $material) }}"
             class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition">Unduh</a>
        </div>
      </div>
    @endforeach
  </div>
@empty
  <div class="bg-white border border-dashed border-line rounded-2xl p-6 text-sm text-gray-500">
    Belum ada materi Huahua dari Kurikulum.
  </div>
@endforelse

@endsection