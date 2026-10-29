@extends('layouts.app')

@section('title', 'Formulir Pendaftaran — Haoyou Educator')

@section('content')
<div class="hy-page min-h-[80vh] flex items-center justify-center px-5 py-10">
  @include('partials.hy-bg', ['tall' => true])

  <div class="hy-card w-full max-w-[520px]">

    <h2 class="text-center text-[22px] font-semibold mb-1.5 text-[#1F1B4D]">Formulir Pendaftaran</h2>
    <p class="text-center text-[12.5px] text-[#6B6A85] mb-7">
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
      <p class="hy-sec" style="margin-top:4px;">DATA SISWA</p>

      <div class="mb-4">
        <label class="hy-lbl">Nama Lengkap Siswa</label>
        <input type="text" name="name" value="{{ old('name') }}" required
               placeholder="Nama lengkap siswa" class="hy-in">
      </div>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
          <label class="hy-lbl">Jenis Kelamin</label>
          <select name="gender" required class="hy-in">
            <option value="">Pilih</option>
            <option value="Male" @selected(old('gender') === 'Male')>Laki-laki</option>
            <option value="Female" @selected(old('gender') === 'Female')>Perempuan</option>
          </select>
        </div>

        <div>
          <label class="hy-lbl">Tanggal Lahir</label>
          <input type="date" name="birth_date" value="{{ old('birth_date') }}" required class="hy-in">
          <p class="text-[11px] text-[#6B6A85] mt-1">Usia dihitung otomatis dari tanggal ini.</p>
        </div>
      </div>

      <div class="mb-4">
        <label class="hy-lbl">No. HP Siswa</label>
        <input type="text" name="phone" value="{{ old('phone') }}" required
               placeholder="08xx xxxx xxxx" class="hy-in">
      </div>

      <div class="mb-4">
        <label class="hy-lbl">Sekolah (opsional)</label>
        <input type="text" name="school" value="{{ old('school') }}"
               placeholder="Nama sekolah saat ini" class="hy-in">
      </div>

      <div class="mb-4">
        <label class="hy-lbl">Alamat (opsional)</label>
        <textarea name="address" rows="2" placeholder="Alamat domisili"
                  class="hy-in resize-y">{{ old('address') }}</textarea>
      </div>

      <div class="mb-4">
        <label class="hy-lbl">Alergi / Kondisi Khusus (opsional)</label>
        <input type="text" name="allergy" value="{{ old('allergy') }}"
               placeholder="Misal: alergi kacang, dsb — kosongkan jika tidak ada" class="hy-in">
      </div>

      {{-- ===== DATA ORANG TUA/WALI ===== --}}
      <p class="hy-sec">DATA ORANG TUA / WALI</p>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
          <label class="hy-lbl">Nama Orang Tua/Wali</label>
          <input type="text" name="parent_name" value="{{ old('parent_name') }}"
                 placeholder="Nama orang tua / wali" class="hy-in">
        </div>
        <div>
          <label class="hy-lbl">No. HP Orang Tua/Wali</label>
          <input type="text" name="parent_phone" value="{{ old('parent_phone') }}"
                 placeholder="08xx xxxx xxxx" class="hy-in">
        </div>
      </div>

      {{-- ===== KEBUTUHAN BELAJAR ===== --}}
      <p class="hy-sec">KEBUTUHAN BELAJAR</p>

      <div class="mb-4">
        <label class="hy-lbl">Deskripsi Kebutuhan</label>
        <textarea name="interested_program" rows="3" required maxlength="255"
                  placeholder="Ceritakan kebutuhan belajar — misal: level Mandarin saat ini, tujuan belajar (sekolah/kerja/hobi), dsb."
                  class="hy-in resize-y">{{ old('interested_program') }}</textarea>
      </div>

      {{-- ===== JADWAL TERSEDIA CALON SISWA ===== --}}
      <div class="mb-6">
        <p class="hy-sec" style="margin-bottom:8px;">JADWAL TERSEDIA CALON SISWA</p>

        <p class="text-[11px] font-semibold text-[#2B5FA8] mb-4">
          Dipakai admin untuk mencocokkan kelas yang cocok saat konversi jadi siswa.
        </p>

        <div id="schedule-container">
          <div class="schedule-row mb-4">
            <div class="grid grid-cols-[1fr_1fr_1fr_46px] gap-3 items-end">
              <div>
                <label class="hy-lbl">Hari</label>
                <select name="schedule_day[]" class="hy-in" style="height:50px;padding:0 12px;">
                  <option value="">Pilih hari</option>
                  <option value="Senin">Senin</option>
                  <option value="Selasa">Selasa</option>
                  <option value="Rabu">Rabu</option>
                  <option value="Kamis">Kamis</option>
                  <option value="Jumat">Jumat</option>
                  <option value="Sabtu">Sabtu</option>
                </select>
              </div>

              <div>
                <label class="hy-lbl">Jam Mulai</label>
                <input type="time" name="schedule_start[]" class="hy-in" style="height:50px;padding:0 12px;">
              </div>

              <div>
                <label class="hy-lbl">Jam Selesai</label>
                <input type="time" name="schedule_end[]" class="hy-in" style="height:50px;padding:0 12px;">
              </div>

              <button type="button" onclick="removeSchedule(this)" title="Hapus jadwal"
                      class="h-[46px] w-[46px] rounded-[12px] flex items-center justify-center border border-[#f1caca] bg-[#fff5f5] transition hover:bg-[#ffe5e5]">
                <span style="color:#dc2626; font-size:18px;">🗑</span>
              </button>
            </div>
          </div>
        </div>

        <button type="button" onclick="addSchedule()"
                class="inline-flex items-center gap-1.5 mt-1 text-[12.5px] font-semibold transition hover:opacity-70"
                style="color:#1F1B4D;">
          <span style="font-size:17px; line-height:1; font-weight:500; color:#E5A800;">+</span>
          Tambah Jadwal Lain
        </button>
      </div>

      {{-- Sumber --}}
      <div class="mb-6">
        <label class="hy-lbl">Tahu Haoyou dari mana? (opsional)</label>
        <select name="source" class="hy-in">
          <option value="">Pilih sumber</option>
          <option value="Instagram" @selected(old('source') === 'Instagram')>Instagram</option>
          <option value="Website" @selected(old('source') === 'Website')>Website</option>
          <option value="Rekomendasi Teman" @selected(old('source') === 'Rekomendasi Teman')>Rekomendasi Teman</option>
          <option value="Event/Pameran" @selected(old('source') === 'Event/Pameran')>Event/Pameran</option>
          <option value="Lainnya" @selected(old('source') === 'Lainnya')>Lainnya</option>
        </select>
      </div>

      <button type="submit" class="hy-btn">Submit Pendaftaran</button>
    </form>

  </div>
</div>

<script>
  function addSchedule() {
    const container = document.getElementById('schedule-container');
    const row = container.querySelector('.schedule-row').cloneNode(true);
    row.querySelectorAll('input, select').forEach(el => el.value = '');
    container.appendChild(row);
  }

  function removeSchedule(button) {
    // Jangan sampai semua jadwal terhapus
    if (document.querySelectorAll('.schedule-row').length <= 1) return;
    button.closest('.schedule-row').remove();
  }
</script>
@endsection