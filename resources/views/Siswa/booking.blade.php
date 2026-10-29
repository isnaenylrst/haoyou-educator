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


  {{-- ==================== NOTIFIKASI FORM ==================== --}}
  @if (session('success'))
    <div class="rounded-xl px-4 py-3 text-xs mb-4 bg-[#e2ecd7] text-[#4b6b2f]">
      {{ session('success') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="rounded-xl px-4 py-3 text-xs mb-4 bg-[#fde8e4] text-[#9b2c1d]">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif


  {{-- ==================== KELAS PRIVAT ==================== --}}
  <div id="bk-privat">

    <div class="booking-info rounded-xl px-4 py-3 text-xs mb-4">
      Kelas Privat bersifat fleksibel &amp; bisa reschedule minimal H-1
      (24 jam) sebelum kelas dimulai.
    </div>

    {{-- Jadwal privat milik siswa + reschedule --}}
    @if ($jadwalPrivatSaya->isNotEmpty())
      <div class="bg-white border border-line rounded-2xl p-5 mb-4">
        <h3 class="font-semibold text-black mb-4">Jadwal Privat Saya</h3>

        @foreach ($jadwalPrivatSaya as $b)
          <div class="py-3 border-b border-line last:border-0">
            <div class="flex items-center justify-between gap-3 flex-wrap">
              <div>
                <div class="text-sm font-medium text-black">
                  {{ $b->session_date->format('d/m/Y') }} ·
                  {{ substr($b->start_time, 0, 5) }}–{{ substr($b->end_time, 0, 5) }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ $b->teacher?->name }} · {{ $b->delivery_mode }}
                  @if ($b->reschedule_count > 0)
                    · sudah {{ $b->reschedule_count }}× dipindah
                  @endif
                </div>
              </div>

              @if ($b->canReschedule())
                <button type="button"
                        class="text-xs font-semibold border border-black rounded-full px-4 py-2 hover:bg-black hover:text-white transition"
                        onclick="document.getElementById('rs-{{ $b->id }}').classList.toggle('hidden')">
                  Reschedule
                </button>
              @else
                <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-gray-100 text-gray-500">
                  Lewat batas H-1
                </span>
              @endif
            </div>

            @if ($b->canReschedule())
              <form id="rs-{{ $b->id }}" method="POST"
                    action="{{ route('booking.privat.reschedule', $b) }}"
                    class="hidden mt-3">
                @csrf
                @method('PATCH')

                <select name="slot" required
                        class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm mb-3">
                  <option value="">— Pilih slot baru —</option>
                  @foreach ($slotPrivat as $slot)
                    @if ($slot['status'] === 'tersedia')
                      <option value="{{ $slot['key'] }}">{{ $slot['label'] }}</option>
                    @endif
                  @endforeach
                </select>

                <button type="submit"
                        class="booking-btn text-xs font-semibold rounded-full px-5 py-2.5 transition">
                  Pindahkan Jadwal
                </button>
              </form>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    <div class="bg-white border border-line rounded-2xl p-5">

      <h3 class="font-semibold text-black mb-1">
        Slot Kosong Laoshi — 7 Hari ke Depan
      </h3>

      @if ($privateEnrollment)
        <p class="text-xs text-gray-500 mb-4">
          {{ $privateEnrollment->privatePackage?->package_name }} ·
          sisa {{ $sisaPertemuan }} pertemuan
        </p>
      @endif

      @if (! $privateEnrollment)

        <div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500">
          Kamu belum memiliki paket kelas privat yang aktif.
          Hubungi admin untuk membeli paket privat.
        </div>

      @elseif (collect($slotPrivat)->isEmpty())

        <div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500">
          Belum ada slot kosong minggu ini. Coba tab
          <strong>Request Jadwal Privat</strong> — admin akan mengonfirmasi ketersediaan Laoshi.
        </div>

      @else

        <form method="POST" action="{{ route('booking.privat.store') }}">
          @csrf

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 mb-5">
            @foreach ($slotPrivat as $slot)
              @if ($slot['status'] === 'penuh')
                <div class="slot penuh">{{ $slot['label'] }}<br><span class="text-[11px]">Penuh</span></div>
              @else
                <label class="slot slot-opsi" data-mode="{{ $slot['delivery_mode'] }}">
                  <input type="radio" name="slot" value="{{ $slot['key'] }}" class="hidden" required>
                  {{ $slot['label'] }}
                  <br><span class="text-[11px]">{{ $slot['delivery_mode'] === 'Both' ? 'Online / Offline' : $slot['delivery_mode'] }}</span>
                </label>
              @endif
            @endforeach
          </div>

          <div id="mode-wrap" class="mb-5 hidden">
            <label class="booking-label block text-xs font-semibold mb-1.5">Platform</label>
            <select name="delivery_mode"
                    class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">
              <option value="Online">Online</option>
              <option value="Offline">Offline — Lokasi Haoyou</option>
            </select>
          </div>

          <button type="submit"
                  class="booking-btn text-sm font-semibold rounded-full px-6 py-3 transition">
            Konfirmasi Booking
          </button>
        </form>

      @endif

    </div>
  </div>


  {{-- ==================== KELAS REGULER ==================== --}}
  <div id="bk-reguler" class="hidden">

    <div class="booking-info rounded-xl px-4 py-3 text-xs mb-4">
      Jadwal kelas Reguler ditentukan &amp; di-plot permanen oleh Admin.
      Pilih salah satu kelas yang tersedia di bawah ini.
    </div>

    <div class="bg-white border border-line rounded-2xl p-5">

      <h3 class="font-semibold text-black mb-4">
        Kelas Reguler Tersedia
      </h3>

      @forelse ($kelasReguler as $kelas)

        <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0">

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

          @if ($kelas['penuh'])
            <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-gray-100 text-gray-500">
              Penuh
            </span>
          @else
            <form method="POST" action="{{ route('booking.reguler.daftar', $kelas['id']) }}"
                  onsubmit="return confirm('Daftar ke kelas ini?')">
              @csrf
              <button type="submit"
                      class="booking-btn text-xs font-semibold rounded-full px-4 py-2 transition">
                Daftar
              </button>
            </form>
          @endif

        </div>

      @empty

        <div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500">
          Belum ada kelas reguler yang bisa kamu pilih. Kelas muncul di sini bila kamu
          sudah memiliki paket reguler yang menunggu kelas. Atau usulkan jadwal baru
          lewat tab <strong>Request Jadwal Reguler</strong>.
        </div>

      @endforelse

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

      <form method="POST" action="{{ route('booking.request.privat') }}">
        @csrf

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">
            Laoshi yang diinginkan (opsional)
          </label>

          <select name="preferred_teacher_id"
                  class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">
            <option value="">Bebas / sesuai ketersediaan</option>
            @foreach ($teachers as $t)
              <option value="{{ $t->id }}" @selected(old('preferred_teacher_id') == $t->id)>{{ $t->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="booking-label block text-xs font-semibold mb-1.5">Tanggal yang diinginkan</label>
            <input type="date" name="preferred_date" required
                   min="{{ now()->addDay()->toDateString() }}"
                   value="{{ old('preferred_date') }}"
                   class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">
          </div>

          <div>
            <label class="booking-label block text-xs font-semibold mb-1.5">Jam yang diinginkan</label>
            <input type="time" name="preferred_start_time" required
                   value="{{ old('preferred_start_time') }}"
                   class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">
          </div>
        </div>

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">Platform</label>

          <select name="delivery_mode"
                  class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">
            <option value="Online" @selected(old('delivery_mode') === 'Online')>Online</option>
            <option value="Offline" @selected(old('delivery_mode') === 'Offline')>Offline — Lokasi Haoyou</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">Catatan Tambahan</label>

          <textarea name="note" rows="3"
                    placeholder="Materi yang ingin difokuskan, dsb."
                    class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm resize-y">{{ old('note') }}</textarea>
        </div>

        <button type="submit"
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

      <form method="POST" action="{{ route('booking.request.reguler') }}">
        @csrf

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">Program</label>

          <select name="program_id" required
                  class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">
            @foreach ($programs as $program)
              <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>
                {{ $program->program_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">Hari yang diusulkan</label>

          <input type="text" name="preferred_days" required
                 value="{{ old('preferred_days') }}"
                 placeholder="Misal: Selasa &amp; Kamis"
                 class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">
        </div>

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">Jam yang diusulkan</label>

          <input type="time" name="preferred_start_time" required
                 value="{{ old('preferred_start_time') }}"
                 class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm">
        </div>

        <div class="mb-4">
          <label class="booking-label block text-xs font-semibold mb-1.5">Catatan Tambahan</label>

          <textarea name="note" rows="3"
                    placeholder="Alasan/permintaan khusus"
                    class="booking-input w-full px-3.5 py-3 border border-line rounded-lg text-sm resize-y">{{ old('note') }}</textarea>
        </div>

        <button type="submit"
                class="booking-btn text-sm font-semibold rounded-full px-6 py-3 transition">
          Kirim Request ke Admin
        </button>

      </form>

    </div>
  </div>

</div>


<script>
  document.addEventListener('change', function (e) {
    if (e.target.name !== 'slot' || e.target.type !== 'radio') return;

    document.querySelectorAll('.slot-opsi').forEach(function (el) {
      el.classList.toggle('dipilih', el.contains(e.target));
    });

    var wrap = document.getElementById('mode-wrap');
    if (wrap) {
      wrap.classList.toggle('hidden', e.target.closest('.slot-opsi').dataset.mode !== 'Both');
    }
  });

  // buka tab sesuai hasil submit (agar pesan error terlihat di tab yang benar)
  @php
    $tabAwal = match (true) {
        old('program_id') || old('preferred_days') => 'reqreguler',
        old('preferred_date') => 'reqprivat',
        default => null,
    };
  @endphp
  @if ($tabAwal)
    document.addEventListener('DOMContentLoaded', function () {
      var idx = ['privat','reguler','reqprivat','reqreguler'].indexOf('{{ $tabAwal }}');
      showBooking('{{ $tabAwal }}', document.querySelectorAll('.tabbtn')[idx]);
    });
  @endif

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