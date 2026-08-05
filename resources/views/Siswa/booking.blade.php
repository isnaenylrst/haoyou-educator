@extends('layouts.dashboard')

@push('styles')
<style>
  /* ==================== FONT POPPINS ==================== */
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

  .booking-page {
    font-family: 'Poppins', sans-serif;
  }

  /* ==================== TAB ==================== */
  .tabbtn {
    padding:10px 20px;
    border-radius:999px;
    border:1.5px solid #ddd8c6;
    background:#fff;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    color:#000;
    transition:.15s;
  }

  .tabbtn:hover {
    background:#FFDD05;
    color:#000;
    border-color:#FFDD05;
  }

  .tabbtn.active {
    background:#FFDD05;
    color:#000;
    border-color:#FFDD05;
  }

  /* ==================== SLOT ==================== */
  .slot {
    padding:12px;
    border:1.5px solid #ddd8c6;
    border-radius:10px;
    text-align:center;
    font-size:12.5px;
    background:#fff;
    cursor:pointer;
  }

  .slot.penuh {
    background:#f1f1f1;
    color:#999;
    cursor:not-allowed;
    border-style:dashed;
  }

  .slot.dipilih {
    background:#FFDD05;
    color:#000;
    border-color:#FFDD05;
  }

  /* ==================== INFO BOX ==================== */
  .booking-info {
    background:#FFF4B8;
    color:#000;
  }

  /* ==================== BUTTON ==================== */
  /* .booking-btn {
    background:#FFDD05;
    color:#000;
  }

  .booking-btn:hover {
    background:#e6c800;
  } */
   .booking-btn {
    background:#080303;
    color:#ffffff;
    border:1px solid #ddd8c6;
    border-radius:999px;
    transition:all .2s ease;
}

.booking-btn:hover {
    background:#383535;
    color:#fffafa;
    border-color:#000000;
}

  /* ==================== FORM ==================== */
  .booking-label {
    color:#000;
  }

  .booking-input {
    background:#f7f8fa;
    color:#000;
  }
</style>
@endpush


@section('dashboard-content')

<div class="booking-page">

  {{-- ==================== TAB ==================== --}}
  <div class="flex flex-wrap gap-2.5 mb-6">
    <button class="tabbtn active" onclick="showBooking('privat', this)">
      Kelas Privat
    </button>

    <button class="tabbtn" onclick="showBooking('reguler', this)">
      Kelas Reguler
    </button>

    <button class="tabbtn" onclick="showBooking('reqprivat', this)">
      Request Jadwal Privat
    </button>

    <button class="tabbtn" onclick="showBooking('reqreguler', this)">
      Request Jadwal Reguler
    </button>
  </div>


  {{-- ==================== KELAS PRIVAT ==================== --}}
  <div id="bk-privat">

    <div class="booking-info rounded-xl px-4 py-3 text-xs mb-4">
      Kelas Privat bersifat fleksibel &amp; bisa reschedule minimal H-1
      (24 jam) sebelum kelas dimulai.
    </div>

    <div class="bg-white border border-line rounded-2xl p-5">

      <h3 class="font-semibold text-black mb-4">
        Slot Kosong Laoshi — Minggu Ini
      </h3>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 mb-5">

        @foreach ($slotPrivat as $slot)

          <div class="slot
            {{ $slot['status'] === 'penuh'
                ? 'penuh'
                : ($slot['status'] === 'dipilih' ? 'dipilih' : '') }}">

            {{ $slot['label'] }}

          </div>

        @endforeach

      </div>

      <button class="booking-btn text-sm font-semibold rounded-full px-6 py-3 transition">
        Konfirmasi Booking 

    </div>
  </div>


  {{-- ==================== KELAS REGULER ==================== --}}
  <div id="bk-reguler" class="hidden">

    <div class="booking-info rounded-xl px-4 py-3 text-xs mb-4">
      Jadwal kelas Reguler ditentukan &amp; di-plot permanen oleh Admin.
    </div>

    <div class="bg-white border border-line rounded-2xl p-5">

      <h3 class="font-semibold text-black mb-4">
        Kelas Reguler Tersedia
      </h3>

      @foreach ($kelasReguler as $kelas)

        <div class="flex items-center justify-between py-3 border-b border-line last:border-0">

          <div class="flex items-center gap-3">

            <div class="w-11 h-11 rounded-xl bg-[#FFF4B8] flex items-center justify-center text-lg">
              🀄
            </div>

            <div>
              <div class="text-sm font-medium text-black">
                {{ $kelas['judul'] }}
              </div>

              <div class="text-xs text-gray-500">
                {{ $kelas['jadwal'] }}
              </div>
            </div>

          </div>

          <button class="booking-btn text-xs font-semibold rounded-full px-4 py-2 transition">
            Daftar
          </button>

        </div>

      @endforeach

    </div>
  </div>


  {{-- ==================== REQUEST JADWAL PRIVAT ==================== --}}
  <div id="bk-reqprivat" class="hidden">

    <div class="booking-info rounded-xl px-4 py-3 text-xs mb-4">
      Tidak menemukan slot Laoshi yang cocok?
      Ajukan permintaan jadwal privat sendiri — admin akan mengonfirmasi
      ketersediaan Laoshi.
    </div>

    <div class="bg-white border border-line rounded-2xl p-5">

      <h3 class="font-semibold text-black mb-4">
        Form Request Jadwal — Kelas Privat
      </h3>

      <form method="POST" action="#">
        @csrf

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Laoshi yang diinginkan (opsional)
          </label>

          <input
            type="text"
            name="laoshi_pilihan"
            placeholder="Misal: Ms. Dinda, atau kosongkan jika bebas"
            class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm"
          >
        </div>


        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Tanggal &amp; Jam yang diinginkan
          </label>

          <input
            type="text"
            name="tanggal_jam"
            placeholder="Misal: Kamis, 15 Jan 2026 · 17:00"
            class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm"
          >
        </div>


        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Platform
          </label>

          <select
            name="platform"
            class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">

            <option>Online</option>
            <option>Offline — Lokasi Haoyou</option>

          </select>
        </div>


        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Catatan Tambahan
          </label>

          <textarea
            name="catatan"
            rows="3"
            placeholder="Materi yang ingin difokuskan, dsb."
            class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm resize-y"></textarea>
        </div>


        <button
          type="submit"
          class="booking-btn text-sm font-semibold rounded-full px-6 py-3 transition">

          Kirim Request ke Admin

        </button>

      </form>

    </div>
  </div>


  {{-- ==================== REQUEST JADWAL REGULER ==================== --}}
  <div id="bk-reqreguler" class="hidden">

    <div class="booking-info rounded-xl px-4 py-3 text-xs mb-4">
      Ingin mengusulkan hari/jam kelas Reguler baru?
      Kirim request berikut — admin akan meninjau &amp; mengonfirmasi
      via WhatsApp/Email.
    </div>

    <div class="bg-white border border-line rounded-2xl p-5">

      <h3 class="font-semibold text-black mb-4">
        Form Request Jadwal — Kelas Reguler
      </h3>

      <form method="POST" action="#">
        @csrf

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Program
          </label>

          <select
            name="program"
            class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">

            <option>HSK 1</option>
            <option>HSK 2</option>
            <option>HSK 3</option>

          </select>
        </div>


        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Hari yang diusulkan
          </label>

          <input
            type="text"
            name="hari"
            placeholder="Misal: Selasa &amp; Kamis"
            class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm"
          >
        </div>


        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Jam yang diusulkan
          </label>

          <input
            type="text"
            name="jam"
            placeholder="Misal: 19:00"
            class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm"
          >
        </div>


        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Catatan Tambahan
          </label>

          <textarea
            name="catatan"
            rows="3"
            placeholder="Alasan/permintaan khusus"
            class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm resize-y"></textarea>
        </div>


        <button
          type="submit"
          class="booking-btn text-sm font-semibold rounded-full px-6 py-3 transition">

          Kirim Request ke Admin

        </button>

      </form>

    </div>
  </div>

</div>


<script>
  function showBooking(type, btn){

    document.querySelectorAll('.tabbtn').forEach(b => {
      b.classList.remove('active');
    });

    btn.classList.add('active');

    ['privat','reguler','reqprivat','reqreguler'].forEach(t => {
      document.getElementById('bk-' + t)
        .classList.toggle('hidden', t !== type);
    });

  }
</script>

@endsection