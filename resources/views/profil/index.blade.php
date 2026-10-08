@extends('layouts.dashboard')

@section('dashboard-content')
@php
  $username = $user->display_name;
  $nama     = $username;
  $ini      = collect(explode(' ', trim($username)))->filter()->take(2)->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $foto     = !empty($user->foto) ? asset('storage/' . $user->foto) : null;
  $card     = 'bg-white border border-[#F3EFDD] rounded-3xl p-6 md:p-7 shadow-[0_2px_12px_rgba(120,95,0,.08)]';
  $input    = 'w-full min-h-[46px] rounded-2xl border border-[#E6E0C8] bg-white px-4 text-[15px] focus:outline-none focus:ring-2 focus:ring-[#FFD60A] focus:border-[#FFD60A]';
  $btn      = 'min-h-[46px] px-6 rounded-2xl bg-[#FFD60A] text-[#3a2e00] text-sm font-semibold hover:bg-[#FFE34D] transition';
@endphp

@if (session('success'))
  <div class="mb-6 rounded-2xl bg-[#e2ecd7] text-[#1d1a0f] text-sm font-medium px-5 py-3.5">{{ session('success') }}</div>
@endif

@if ($errors->any())
  <div class="mb-6 rounded-2xl bg-[#FDECEA] text-[#8a2a20] text-sm px-5 py-3.5">
    <div class="font-semibold mb-1">Ada yang perlu diperbaiki:</div>
    <ul class="list-disc pl-5">
      @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- ============ KARTU PROFIL (LIHAT) ============ --}}
  <div class="{{ $card }} lg:col-span-1 flex flex-col items-center text-center">
    <div class="w-32 h-32 rounded-full bg-[#FFF3B8] text-[#5c4500] text-4xl font-bold flex items-center justify-center overflow-hidden border-4 border-[#FFD60A]">
      @if ($foto)
        <img id="fotoPreviewCard" src="{{ $foto }}" alt="{{ $nama }}" class="w-full h-full object-cover">
      @else
        <span id="fotoInisialCard">{{ $ini }}</span>
      @endif
    </div>

    {{-- Username dari database --}}
    <h2 class="mt-4 text-xl font-bold">{{ $username }}</h2>
    {{-- Program --}}
    <p class="text-sm text-[#6b6652]">HSK 2 · Hybrid</p>

    <dl class="mt-6 w-full text-center text-sm">
      <div class="py-3">
        <dt class="text-[#6b6652]">Bergabung sejak</dt>
        <dd class="font-medium">{{ optional($user->created_at)->translatedFormat('d F Y') ?? '-' }}</dd>
      </div>
    </dl>

    @if ($foto)
      <form method="POST" action="{{ route('profil.foto.destroy') }}" class="mt-4 w-full"
            onsubmit="return confirm('Hapus foto profil?')">
        @csrf @method('DELETE')
        <button type="submit" class="w-full min-h-[44px] rounded-2xl border border-[#E6E0C8] text-sm font-medium text-[#C0392B] hover:bg-[#FDECEA] transition">
          Hapus foto
        </button>
      </form>
    @endif
  </div>

  <div class="lg:col-span-2 flex flex-col gap-6">

    {{-- ============ EDIT PROFIL + FOTO ============ --}}
    <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data" class="{{ $card }}">
      @csrf @method('PUT')
      <h3 class="text-xl font-bold mb-5">Edit profil</h3>

      <div class="flex items-center gap-5 mb-6">
        <div class="w-20 h-20 rounded-full bg-[#FFF3B8] text-[#5c4500] text-2xl font-bold flex items-center justify-center overflow-hidden flex-shrink-0">
          <img id="fotoPreview" src="{{ $foto ?? '' }}" alt="" class="w-full h-full object-cover {{ $foto ? '' : 'hidden' }}">
          <span id="fotoInisial" class="{{ $foto ? 'hidden' : '' }}">{{ $ini }}</span>
        </div>
        <div>
          <label for="foto" class="inline-flex items-center min-h-[44px] px-5 rounded-2xl bg-[#FFF8D6] border border-[#F2D84A] text-sm font-semibold text-[#5c4500] cursor-pointer hover:bg-[#FFEFA0] transition">
            Pilih foto
          </label>
          <input id="foto" name="foto" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only">
          <p class="text-xs text-[#6b6652] mt-2">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
        </div>
      </div>

      <div class="mt-6 flex justify-end">
        <button type="submit" class="{{ $btn }}">Simpan perubahan</button>
      </div>
    </form>

    {{-- ============ GANTI PASSWORD ============ --}}
    <form method="POST" action="{{ route('profil.password') }}" class="{{ $card }}">
      @csrf @method('PUT')
      <h3 class="text-xl font-bold mb-5">Ganti password</h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="md:col-span-2">
          <label for="current_password" class="block text-sm font-medium mb-1.5">Password saat ini</label>
          <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="{{ $input }}" required>
        </div>
        <div>
          <label for="password" class="block text-sm font-medium mb-1.5">Password baru</label>
          <input id="password" name="password" type="password" autocomplete="new-password" class="{{ $input }}" required>
          <p class="text-xs text-[#6b6652] mt-1.5">Minimal 8 karakter.</p>
        </div>
        <div>
          <label for="password_confirmation" class="block text-sm font-medium mb-1.5">Ulangi password baru</label>
          <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="{{ $input }}" required>
        </div>
      </div>

      <div class="mt-6 flex justify-end">
        <button type="submit" class="{{ $btn }}">Ganti password</button>
      </div>
    </form>

  </div>
</div>

<script>
  // Pratinjau foto sebelum disimpan
  document.getElementById('foto').addEventListener('change', function(e){
    var file = e.target.files[0];
    if(!file) return;
    var url = URL.createObjectURL(file);
    var img = document.getElementById('fotoPreview');
    img.src = url; img.classList.remove('hidden');
    document.getElementById('fotoInisial').classList.add('hidden');
  });
</script>
@endsection