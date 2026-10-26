@extends('layouts.dashboard')

@section('dashboard-content')
@php
  $username = $user->display_name;
  $nama     = $username;
  $ini      = collect(explode(' ', trim($username)))->filter()->take(2)->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $foto     = !empty($user->foto) ? asset('storage/' . $user->foto) : null;
  $card     = 'bg-white border border-[#F0EBDD] rounded-3xl p-6 md:p-7 shadow-[0_4px_18px_rgba(31,27,22,.08)]';
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

<div class="flex flex-col gap-6 max-w-3xl">

  {{-- ============ EDIT PROFIL (FOTO) : PILIH, HAPUS, SIMPAN ============ --}}
  <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data" class="{{ $card }}">
    @csrf @method('PUT')
    <h3 class="text-xl font-bold mb-5">Edit profil</h3>

    <div class="flex items-center gap-5">
      <div class="w-20 h-20 rounded-full bg-[#FFF3B8] text-[#5c4500] text-2xl font-bold flex items-center justify-center overflow-hidden flex-shrink-0">
        <img id="fotoPreview" src="{{ $foto ?? '' }}" alt="{{ $nama }}" class="w-full h-full object-cover {{ $foto ? '' : 'hidden' }}">
        <span id="fotoInisial" class="{{ $foto ? 'hidden' : '' }}">{{ $ini }}</span>
      </div>
      <div>
        <div class="flex flex-wrap items-center gap-2.5">
          <label for="foto" class="inline-flex items-center min-h-[44px] px-5 rounded-2xl bg-[#FFF8D6] border border-[#F2D84A] text-sm font-semibold text-[#5c4500] cursor-pointer hover:bg-[#FFEFA0] transition">
            Pilih foto
          </label>
          <input id="foto" name="foto" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only">

          {{-- Selalu tampil; nonaktif (redup) jika belum ada foto. Memakai form terpisah agar tidak ada form di dalam form --}}
          <button type="submit" form="formHapusFoto" {{ $foto ? '' : 'disabled' }}
                  class="inline-flex items-center min-h-[44px] px-5 rounded-2xl border border-[#E6E0C8] text-sm font-medium text-[#C0392B] transition {{ $foto ? 'hover:bg-[#FDECEA]' : 'opacity-40 cursor-not-allowed' }}">
            Hapus foto
          </button>
        </div>
        <p class="text-xs text-[#6b6652] mt-2">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
      </div>
    </div>

    <div class="mt-6 flex justify-end">
      <button type="submit" class="{{ $btn }}">Simpan perubahan</button>
    </div>
  </form>

  @if ($foto)
    <form id="formHapusFoto" method="POST" action="{{ route('profil.foto.destroy') }}"
          onsubmit="return confirm('Hapus foto profil?')" class="hidden">
      @csrf @method('DELETE')
    </form>
  @endif

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