@extends('layouts.dashboard')

@push('styles')
<style>
  .tabbtn{ padding:10px 20px; border-radius:999px; border:1.5px solid #ddd8c6; background:#fff; font-size:13px; font-weight:600; cursor:pointer; color:#8a8571; }
  .tabbtn.active{ background:#1c1a14; color:#fff; border-color:#1c1a14; }
  .slot{ padding:12px; border:1.5px solid #ddd8c6; border-radius:10px; text-align:center; font-size:12.5px; background:#fff; cursor:pointer; }
  .slot.penuh{ background:#efece3; color:#b3ad99; cursor:not-allowed; border-style:dashed; }
  .slot.dipilih{ background:#5c6b2e; color:#fff; border-color:#5c6b2e; }
</style>
@endpush

@section('dashboard-content')

<div class="flex flex-wrap gap-2.5 mb-6">
  <button class="tabbtn active" onclick="showBooking('privat', this)">Kelas Privat</button>
  <button class="tabbtn" onclick="showBooking('reguler', this)">Kelas Reguler</button>
  <button class="tabbtn" onclick="showBooking('reqprivat', this)">Request Jadwal Privat</button>
  <button class="tabbtn" onclick="showBooking('reqreguler', this)">Request Jadwal Reguler</button>
</div>

{{-- ===== KELAS PRIVAT ===== --}}
<div id="bk-privat">
  <div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-4">
    Kelas Privat bersifat fleksibel &amp; bisa reschedule minimal H-1 (24 jam) sebelum kelas dimulai.
  </div>
  <div class="bg-white border border-line rounded-2xl p-5">
    <h3 class="font-semibold mb-4">Slot Kosong Laoshi — Minggu Ini</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 mb-5">
      @foreach ($slotPrivat as $slot)
        <div class="slot {{ $slot['status'] === 'penuh' ? 'penuh' : ($slot['status'] === 'dipilih' ? 'dipilih' : '') }}">
          {{ $slot['label'] }}
        </div>
      @endforeach
    </div>
    <button class="bg-gold text-white text-sm font-semibold rounded-full px-6 py-3 hover:brightness-105 transition">
      Konfirmasi Booking — Link Zoom Otomatis Dibuat
    </button>
  </div>
</div>

{{-- ===== KELAS REGULER ===== --}}
<div id="bk-reguler" class="hidden">
  <div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-4">
    Jadwal kelas Reguler ditentukan &amp; di-plot permanen oleh Admin.
  </div>
  <div class="bg-white border border-line rounded-2xl p-5">
    <h3 class="font-semibold mb-4">Kelas Reguler Tersedia</h3>
    @foreach ($kelasReguler as $kelas)
      <div class="flex items-center justify-between py-3 border-b border-line last:border-0">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🀄</div>
          <div>
            <div class="text-sm font-medium">{{ $kelas['judul'] }}</div>
            <div class="text-xs text-gray-500">{{ $kelas['jadwal'] }}</div>
          </div>
        </div>
        <button class="text-xs font-semibold bg-ink text-white rounded-full px-4 py-2 hover:bg-oliveDark transition">Daftar</button>
      </div>
    @endforeach
  </div>
</div>

{{-- ===== REQUEST JADWAL PRIVAT ===== --}}
<div id="bk-reqprivat" class="hidden">
  <div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-4">
    Tidak menemukan slot Laoshi yang cocok? Ajukan permintaan jadwal privat sendiri — admin akan mengonfirmasi ketersediaan Laoshi.
  </div>
  <div class="bg-white border border-line rounded-2xl p-5">
    <h3 class="font-semibold mb-4">Form Request Jadwal — Kelas Privat</h3>
    <form method="POST" action="#">
      @csrf
      <div class="mb-4">
        <label class="text-xs font-semibold text-oliveDark block mb-1.5">Laoshi yang diinginkan (opsional)</label>
        <input type="text" name="laoshi_pilihan" placeholder="Misal: Ms. Dinda, atau kosongkan jika bebas"
               class="w-full px-3.5 py-3 border border-line rounded-lg bg-cream text-sm">
      </div>
      <div class="mb-4">
        <label class="text-xs font-semibold text-oliveDark block mb-1.5">Tanggal &amp; Jam yang diinginkan</label>
        <input type="text" name="tanggal_jam" placeholder="Misal: Kamis, 15 Jan 2026 · 17:00"
               class="w-full px-3.5 py-3 border border-line rounded-lg bg-cream text-sm">
      </div>
      <div class="mb-4">
        <label class="text-xs font-semibold text-oliveDark block mb-1.5">Platform</label>
        <select name="platform" class="w-full px-3.5 py-3 border border-line rounded-lg bg-cream text-sm">
          <option>Online</option>
          <option>Offline — Lokasi Haoyou</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="text-xs font-semibold text-oliveDark block mb-1.5">Catatan Tambahan</label>
        <textarea name="catatan" rows="3" placeholder="Materi yang ingin difokuskan, dsb."
                  class="w-full px-3.5 py-3 border border-line rounded-lg bg-cream text-sm resize-y"></textarea>
      </div>
      <button type="submit" class="bg-gold text-white text-sm font-semibold rounded-full px-6 py-3 hover:brightness-105 transition">
        Kirim Request ke Admin
      </button>
    </form>
  </div>
</div>

{{-- ===== REQUEST JADWAL REGULER ===== --}}
<div id="bk-reqreguler" class="hidden">
  <div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-4">
    Ingin mengusulkan hari/jam kelas Reguler baru? Kirim request berikut — admin akan meninjau &amp; mengonfirmasi via WhatsApp/Email.
  </div>
  <div class="bg-white border border-line rounded-2xl p-5">
    <h3 class="font-semibold mb-4">Form Request Jadwal — Kelas Reguler</h3>
    <form method="POST" action="#">
      @csrf
      <div class="mb-4">
        <label class="text-xs font-semibold text-oliveDark block mb-1.5">Program</label>
        <select name="program" class="w-full px-3.5 py-3 border border-line rounded-lg bg-cream text-sm">
          <option>HSK 1</option>
          <option>HSK 2</option>
          <option>HSK 3</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="text-xs font-semibold text-oliveDark block mb-1.5">Hari yang diusulkan</label>
        <input type="text" name="hari" placeholder="Misal: Selasa &amp; Kamis"
               class="w-full px-3.5 py-3 border border-line rounded-lg bg-cream text-sm">
      </div>
      <div class="mb-4">
        <label class="text-xs font-semibold text-oliveDark block mb-1.5">Jam yang diusulkan</label>
        <input type="text" name="jam" placeholder="Misal: 19:00"
               class="w-full px-3.5 py-3 border border-line rounded-lg bg-cream text-sm">
      </div>
      <div class="mb-4">
        <label class="text-xs font-semibold text-oliveDark block mb-1.5">Catatan Tambahan</label>
        <textarea name="catatan" rows="3" placeholder="Alasan/permintaan khusus"
                  class="w-full px-3.5 py-3 border border-line rounded-lg bg-cream text-sm resize-y"></textarea>
      </div>
      <button type="submit" class="bg-gold text-white text-sm font-semibold rounded-full px-6 py-3 hover:brightness-105 transition">
        Kirim Request ke Admin
      </button>
    </form>
  </div>
</div>

<script>
  function showBooking(type, btn){
    document.querySelectorAll('.tabbtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    ['privat','reguler','reqprivat','reqreguler'].forEach(t => {
      document.getElementById('bk-' + t).classList.toggle('hidden', t !== type);
    });
  }
</script>

@endsection