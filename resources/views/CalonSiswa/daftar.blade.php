@extends('layouts.app')

@section('title', 'Formulir Pendaftaran — Haoyou Educator')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-paper px-5 py-10">
  <div class="w-full max-w-[520px] bg-white border border-line rounded-[20px] p-9">

    <h2 class="text-center text-[22px] font-semibold mb-1.5">Formulir Pendaftaran</h2>
    <p class="text-center text-[12.5px] text-[#8a8571] mb-7">
      Data akan diverifikasi manual oleh admin (maks. 1×24 jam) via WhatsApp/Email.
    </p>

    @if ($errors->any())
      <div class="bg-[#f6e3df] border border-[#e4b6ab] text-[#8a3b2c] text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('pendaftaran.store') }}">
      @csrf

      {{-- ===== DATA SISWA ===== --}}
      {{-- <p class="text-[11px] font-bold tracking-widest text-gold mb-3 mt-1">DATA SISWA</p> --}}
      <p class="text-[11px] font-bold tracking-widest mb-3 mt-1" style="color:#ffbd08;">
    DATA SISWA
    </p>
      <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">Nama Lengkap Siswa</label>
        <input type="text" name="nama" value="{{ old('nama') }}" required
               placeholder="Nama lengkap siswa"
               class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-color:#ffdd05; focus:bg-white">
      </div>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
          <label class="text-xs font-semibold text-black block mb-1.5">Jenis Kelamin</label>
          <select name="jenis_kelamin" required
                  class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
            <option value="">Pilih</option>
            <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
            <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
          </select>
        </div>
        <div>
          <label class="text-xs font-semibold text-black block mb-1.5">Tanggal Lahir</label>
          <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}"
                 class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
          <p class="text-[11px] text-[#8a8571] mt-1">Usia dihitung otomatis dari tanggal ini.</p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
          <label class="text-xs font-semibold text-black block mb-1.5">No. HP Siswa</label>
          <input type="text" name="no_hp" value="{{ old('no_hp') }}" required
                 placeholder="08xx xxxx xxxx"
                 class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
        </div>
        <div>
          <label class="text-xs font-semibold text-black block mb-1.5">Email (opsional)</label>
          <input type="email" name="email" value="{{ old('email') }}"
                 placeholder="nama@email.com"
                 class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
        </div>
      </div>

      <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">Sekolah (opsional)</label>
        <input type="text" name="sekolah" value="{{ old('sekolah') }}"
               placeholder="Nama sekolah saat ini"
               class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
      </div>

      <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">Alamat (opsional)</label>
        <textarea name="alamat" rows="2"
                  placeholder="Alamat domisili"
                  class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white resize-y">{{ old('alamat') }}</textarea>
      </div>

      <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">Alergi / Kondisi Khusus (opsional)</label>
        <input type="text" name="alergi" value="{{ old('alergi') }}"
               placeholder="Misal: alergi kacang, dsb — kosongkan jika tidak ada"
               class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
      </div>

      {{-- ===== DATA ORANG TUA/WALI ===== --}}
      {{-- <p class="text-[11px] font-bold tracking-widest text-gold mb-3 mt-6">DATA ORANG TUA / WALI</p> --}}
      <p class="text-[11px] font-bold tracking-widest mb-3 mt-6" style="color:#ffbd08;">
      DATA ORANG TUA / WALI
     </p>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
          <label class="text-xs font-semibold text-black block mb-1.5">Nama Orang Tua/Wali</label>
          <input type="text" name="nama_ortu" value="{{ old('nama_ortu') }}"
                 placeholder="Nama orang tua / wali"
                 class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
        </div>
        <div>
          <label class="text-xs font-semibold text-black block mb-1.5">No. HP Orang Tua/Wali</label>
          <input type="text" name="no_hp_ortu" value="{{ old('no_hp_ortu') }}"
                 placeholder="08xx xxxx xxxx"
                 class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
        </div>
      </div>

      {{-- ===== KEBUTUHAN BELAJAR ===== --}}
      {{-- <p class="text-[11px] font-bold tracking-widest text-gold mb-3 mt-6">KEBUTUHAN BELAJAR</p> --}}
      <p class="text-[11px] font-bold tracking-widest mb-3 mt-6" style="color:#ffbd08;">
      KEBUTUHAN BELAJAR
      </p>

      {{-- Pengganti field "Program Kelas" — kolom: kebutuhan_belajar --}}
      <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">Deskripsi Kebutuhan</label>
        <textarea name="kebutuhan_belajar" rows="3" required maxlength="255"
                  placeholder="Ceritakan kebutuhan belajar — misal: level Mandarin saat ini, tujuan belajar (sekolah/kerja/hobi), dsb. (maks. 255 karakter)"
                  class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white resize-y">{{ old('kebutuhan_belajar') }}</textarea>
      </div>

      <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">Jadwal yang Tersedia (opsional)</label>
        <textarea name="available_schedule" rows="2"
                  placeholder="Misal: Senin & Rabu sore, atau weekend pagi"
                  class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white resize-y">{{ old('available_schedule') }}</textarea>
      </div>

      <div class="mb-6">
        <label class="text-xs font-semibold text-black block mb-1.5">Tahu Haoyou dari mana? (opsional)</label>
        <select name="sumber"
                class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:border-gold focus:bg-white">
          <option value="">Pilih sumber</option>
          <option value="Instagram" @selected(old('sumber') === 'Instagram')>Instagram</option>
          <option value="Website" @selected(old('sumber') === 'Website')>Website</option>
          <option value="Rekomendasi Teman" @selected(old('sumber') === 'Rekomendasi Teman')>Rekomendasi Teman</option>
          <option value="Event/Pameran" @selected(old('sumber') === 'Event/Pameran')>Event/Pameran</option>
          <option value="Lainnya" @selected(old('sumber') === 'Lainnya')>Lainnya</option>
        </select>
      </div>

      {{-- <button type="submit" class="w-full py-3.5 rounded-full text-[13.5px] font-semibold bg-gold text-ink hover:brightness-105 transition">
        Submit Pendaftaran
      </button> --}}
      <button
    type="submit"
    class="w-full py-3.5 rounded-full text-[13.5px] font-semibold transition hover:brightness-105"
    style="background:#FFDD05; color:#000;">
    Submit Pendaftaran
</button>
    </form>

    <p class="text-center text-xs text-[#8a8571] mt-5">
      Sudah punya akses?
      {{-- <a href="{{ route('login') }}" class="text-gold font-semibold no-underline">Masuk di sini</a> --}}
      <a href="{{ route('login') }}"
   class="font-semibold no-underline"
   style="color:#ffbd08;">
    Masuk di sini
    </a>
    </p>
  </div>
</div>
@endsection