@extends('layouts.app')

@section('title', 'Formulir Pendaftaran — Haoyou Educator')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center bg-paper px-5 py-10">
  <div class="w-full max-w-[520px] bg-white border border-line rounded-[20px] p-9">

    <h2 class="text-center text-[22px] font-semibold mb-1.5">Formulir Pendaftaran</h2>
    <p class="text-center text-[12.5px] text-[#8a8571] mb-7">
      Data akan diverifikasi oleh admin (maks. 1×24 jam) via WhatsApp
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
    <p class="text-[11px] font-bold tracking-widest mb-3 mt-1" style="color:#ffbd08;">
        DATA SISWA
    </p>

    <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">
            Nama Lengkap Siswa
        </label>

        <input
            type="text"
            name="name"
            value="{{ old('name') }}"
            required
            placeholder="Nama lengkap siswa"
            class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
        >
    </div>


    <div class="grid grid-cols-2 gap-3 mb-4">

        {{-- Jenis Kelamin --}}
        <div>
            <label class="text-xs font-semibold text-black block mb-1.5">
                Jenis Kelamin
            </label>

            <select
                name="gender"
                required
                class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
            >
                <option value="">Pilih</option>

                <option
                    value="Male"
                    @selected(old('gender') === 'Male')
                >
                    Laki-laki
                </option>

                <option
                    value="Female"
                    @selected(old('gender') === 'Female')
                >
                    Perempuan
                </option>
            </select>
        </div>


        {{-- Tanggal Lahir --}}
        <div>
            <label class="text-xs font-semibold text-black block mb-1.5">
                Tanggal Lahir
            </label>

            <input
                type="date"
                name="birth_date"
                value="{{ old('birth_date') }}"
                class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
            >

            <p class="text-[11px] text-[#8a8571] mt-1">
                Usia dihitung otomatis dari tanggal ini.
            </p>
        </div>

    </div>


    {{-- No HP --}}
    <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">
            No. HP Siswa
        </label>

        <input
            type="text"
            name="phone"
            value="{{ old('phone') }}"
            required
            placeholder="08xx xxxx xxxx"
            class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
        >
    </div>


    {{-- Email --}}
    {{-- DIHAPUS karena tabel candidate_students kamu tidak mempunyai kolom email --}}


    {{-- Sekolah --}}
    <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">
            Sekolah (opsional)
        </label>

        <input
            type="text"
            name="school"
            value="{{ old('school') }}"
            placeholder="Nama sekolah saat ini"
            class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
        >
    </div>


    {{-- Alamat --}}
    <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">
            Alamat (opsional)
        </label>

        <textarea
            name="address"
            rows="2"
            placeholder="Alamat domisili"
            class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white resize-y"
        >{{ old('address') }}</textarea>
    </div>


    {{-- Alergi --}}
    <div class="mb-4">
        <label class="text-xs font-semibold text-black block mb-1.5">
            Alergi / Kondisi Khusus (opsional)
        </label>

        <input
            type="text"
            name="allergy"
            value="{{ old('allergy') }}"
            placeholder="Misal: alergi kacang, dsb — kosongkan jika tidak ada"
            class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
        >
    </div>


    {{-- ===== DATA ORANG TUA/WALI ===== --}}

    <p class="text-[11px] font-bold tracking-widest mb-3 mt-6" style="color:#ffbd08;">
        DATA ORANG TUA / WALI
    </p>


    <div class="grid grid-cols-2 gap-3 mb-4">

        {{-- Nama Orang Tua --}}
        <div>
            <label class="text-xs font-semibold text-black block mb-1.5">
                Nama Orang Tua/Wali
            </label>

            <input
                type="text"
                name="parent_name"
                value="{{ old('parent_name') }}"
                placeholder="Nama orang tua / wali"
                class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
            >
        </div>


        {{-- No HP Orang Tua --}}
        <div>
            <label class="text-xs font-semibold text-black block mb-1.5">
                No. HP Orang Tua/Wali
            </label>

            <input
                type="text"
                name="parent_phone"
                value="{{ old('parent_phone') }}"
                placeholder="08xx xxxx xxxx"
                class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
            >
        </div>

    </div>


    {{-- ===== KEBUTUHAN BELAJAR ===== --}}

    <p class="text-[11px] font-bold tracking-widest mb-3 mt-6" style="color:#ffbd08;">
        KEBUTUHAN BELAJAR
    </p>


    {{-- Program / Kebutuhan Belajar --}}
    <div class="mb-4">

        <label class="text-xs font-semibold text-black block mb-1.5">
            Deskripsi Kebutuhan
        </label>

        <textarea
            name="interested_program"
            rows="3"
            required
            maxlength="255"
            placeholder="Ceritakan kebutuhan belajar — misal: level Mandarin saat ini, tujuan belajar (sekolah/kerja/hobi), dsb."
            class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white resize-y"
        >{{ old('interested_program') }}</textarea>

    </div>

    {{-- ===== JADWAL TERSEDIA CALON SISWA ===== --}}
<div class="mb-6 mt-6">

    {{-- Judul --}}
    <div class="flex items-center gap-2 mb-4">  
      <p
            class="text-[11px] font-bold tracking-widest"
            style="color:#ffbd08;"
        >
          JADWAL TERSEDIA CALON SISWA
        </p>
    </div>

    <p class="text-[10px] font-semibold text-[#252735] mb-6">
        Dipakai admin untuk mencocokkan kelas yang cocok saat konversi jadi siswa.
    </p>


    {{-- Container semua jadwal --}}
    <div id="schedule-container">

        {{-- Jadwal pertama --}}
        <div class="schedule-row mb-4">

            <div class="grid grid-cols-[1fr_1fr_1fr_46px] gap-3 items-end">

                {{-- Hari --}}
                <div>
                    <label class="text-xs font-semibold text-[#4c5060] block mb-2">
                        Hari
                    </label>

                    <select
                        name="schedule_day[]"
                        class="w-full h-[50px] px-4 border border-[#e3e3e3] rounded-[11px] bg-white text-[13px] text-[#555] focus:outline-none focus:border-[#FFDD05]"
                    >
                        <option value="">Pilih hari</option>
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                        <option value="Rabu">Rabu</option>
                        <option value="Kamis">Kamis</option>
                        <option value="Jumat">Jumat</option>
                        <option value="Sabtu">Sabtu</option>
                    </select>
                </div>


                {{-- Jam Mulai --}}
                <div>
                    <label class="text-xs font-semibold text-[#4c5060] block mb-2">
                        Jam Mulai
                    </label>

                    <input
                        type="time"
                        name="schedule_start[]"
                        class="w-full h-[50px] px-4 border border-[#e3e3e3] rounded-[11px] bg-white text-[13px] text-[#555] focus:outline-none focus:border-[#FFDD05]"
                    >
                </div>


                {{-- Jam Selesai --}}
                <div>
                    <label class="text-xs font-semibold text-[#4c5060] block mb-2">
                        Jam Selesai
                    </label>

                    <input
                        type="time"
                        name="schedule_end[]"
                        class="w-full h-[50px] px-4 border border-[#e3e3e3] rounded-[11px] bg-white text-[13px] text-[#555] focus:outline-none focus:border-[#FFDD05]"
                    >
                </div>


                {{-- Tombol hapus --}}
               <button
                type="button"
                onclick="removeSchedule(this)"
                class="delete-schedule h-[46px] w-[46px] rounded-[11px] flex items-center justify-center border border-[#f1caca] bg-[#fff5f5] transition hover:bg-[#ffe5e5]"
                title="Hapus jadwal"
            >
                <span style="color:#dc2626; font-size:18px;">🗑</span>
            </button>

            </div>

        </div>

    </div>


    {{-- Tambah jadwal --}}
    <button
        type="button"
        onclick="addSchedule()"
        class="inline-flex items-center gap-1.5 mt-1 text-[12.5px] font-semibold transition hover:opacity-70"
        style="color:#ffbd08;"
    >
        <span
            style="
                font-size:17px;
                line-height:1;
                font-weight:500;
            "
        >
            +
        </span>

        Tambah Jadwal Lain
    </button>
</div>


{{-- ===== JAVASCRIPT JADWAL ===== --}}
<script>

function addSchedule() {

    const container = document.getElementById('schedule-container');

    const row = document.createElement('div');

    row.className = 'schedule-row mb-4';

    row.innerHTML = `
        <div class="grid grid-cols-[1fr_1fr_1fr_46px] gap-3 items-end">

            {{-- Hari --}}
            <div>
                <label class="text-xs font-semibold text-[#4c5060] block mb-2">
                    Hari
                </label>

                <select
                    name="schedule_day[]"
                    class="w-full h-[50px] px-4 border border-[#e3e3e3] rounded-[11px] bg-white text-[13px] text-[#555] focus:outline-none focus:border-[#FFDD05]"
                >
                    <option value="">Pilih hari</option>
                    <option value="Senin">Senin</option>
                    <option value="Selasa">Selasa</option>
                    <option value="Rabu">Rabu</option>
                    <option value="Kamis">Kamis</option>
                    <option value="Jumat">Jumat</option>
                    <option value="Sabtu">Sabtu</option>
                    <option value="Minggu">Minggu</option>
                </select>
            </div>


            {{-- Jam Mulai --}}
            <div>
                <label class="text-xs font-semibold text-[#4c5060] block mb-2">
                    Jam Mulai
                </label>

                <input
                    type="time"
                    name="schedule_start[]"
                    class="w-full h-[50px] px-4 border border-[#e3e3e3] rounded-[11px] bg-white text-[13px] text-[#555] focus:outline-none focus:border-[#FFDD05]"
                >
            </div>


            {{-- Jam Selesai --}}
            <div>
                <label class="text-xs font-semibold text-[#4c5060] block mb-2">
                    Jam Selesai
                </label>

                <input
                    type="time"
                    name="schedule_end[]"
                    class="w-full h-[50px] px-4 border border-[#e3e3e3] rounded-[11px] bg-white text-[13px] text-[#555] focus:outline-none focus:border-[#FFDD05]"
                >
            </div>


            {{-- Hapus --}}
            <button
                type="button"
                onclick="removeSchedule(this)"
                class="delete-schedule h-[46px] w-[46px] rounded-[11px] flex items-center justify-center border border-[#f1caca] bg-[#fff5f5] transition hover:bg-[#ffe5e5]"
                title="Hapus jadwal"
            >
                <span style="color:#dc2626; font-size:18px;">🗑</span>
            </button>

        </div>
    `;

    container.appendChild(row);
}


function removeSchedule(button) {

    const rows = document.querySelectorAll('.schedule-row');

    // Jangan sampai semua jadwal terhapus
    if (rows.length <= 1) {
        return;
    }

    button.closest('.schedule-row').remove();
}

</script>

    {{-- Jadwal tersedia --}}
    {{-- <div class="mb-4">

        <label class="text-xs font-semibold text-black block mb-1.5">
            Jadwal yang Tersedia (opsional)
        </label>

        <textarea
            name="available_schedule"
            rows="2"
            placeholder="Misal: Senin & Rabu sore, atau weekend pagi"
            class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white resize-y"
        >{{ old('available_schedule') }}</textarea>

    </div>
 --}}

    {{-- Sumber --}}
    <div class="mb-6">

        <label class="text-xs font-semibold text-black block mb-1.5">
            Tahu Haoyou dari mana? (opsional)
        </label>

        <select
            name="source"
            class="w-full px-3.5 py-3 border-[1.5px] border-line rounded-[10px] bg-cream text-[13.5px] focus:outline-none focus:bg-white"
        >
            <option value="">Pilih sumber</option>

            <option value="Instagram"
                @selected(old('source') === 'Instagram')>
                Instagram
            </option>

            <option value="Website"
                @selected(old('source') === 'Website')>
                Website
            </option>

            <option value="Rekomendasi Teman"
                @selected(old('source') === 'Rekomendasi Teman')>
                Rekomendasi Teman
            </option>

            <option value="Event/Pameran"
                @selected(old('source') === 'Event/Pameran')>
                Event/Pameran
            </option>

            <option value="Lainnya"
                @selected(old('source') === 'Lainnya')>
                Lainnya
            </option>

        </select>

    </div>


    {{-- Submit --}}
    <button
        type="submit"
        class="w-full py-3.5 rounded-full text-[13.5px] font-semibold transition hover:brightness-105"
        style="background:#FFDD05; color:#000;"
    >
        Submit Pendaftaran
    </button>

</form>

  </div>
</div>
@endsection