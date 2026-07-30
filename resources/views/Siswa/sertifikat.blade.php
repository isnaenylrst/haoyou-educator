@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-pale rounded-xl px-4 py-3 mb-5 flex items-center justify-between gap-3 flex-wrap">
  <span class="text-xs text-oliveDark">🔎 Demo: lihat tampilan untuk jenis program lain —</span>
  <span class="flex gap-2">
    <button class="tabbtn active" style="padding:7px 14px;" onclick="setProgram('hsk', this)">Program HSK</button>
    <button class="tabbtn" style="padding:7px 14px;" onclick="setProgram('nonhsk', this)">Program Non-HSK (Reguler)</button>
  </span>
</div>

<div class="bg-white border border-line rounded-2xl p-5 mb-5" id="sertifikat-panel">
  <h3 class="font-semibold mb-4">Sertifikat Saya</h3>
  <div class="flex items-center justify-between py-3 border-b border-line mb-4">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">🏅</div>
      <div>
        <div class="text-sm font-medium">Sertifikat Kelulusan — HSK 2</div>
        <div class="text-xs text-gray-500">Diterbitkan Jan 2026 · Program HSK</div>
      </div>
    </div>
    <button class="text-xs font-semibold bg-gold text-white rounded-full px-4 py-2 hover:brightness-105 transition">Download PDF</button>
  </div>
  <div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500">
    Sertifikat hanya tersedia untuk siswa program <strong>HSK</strong>. Program non-HSK (kelas Reguler biasa) tidak menerbitkan sertifikat.
  </div>
</div>

<div class="bg-white border border-line rounded-2xl p-5 mb-5 hidden" id="sertifikat-kosong">
  <h3 class="font-semibold mb-4">Sertifikat Saya</h3>
  <div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500">
    🚫 Sertifikat tidak tersedia untuk program ini — sertifikat hanya diberikan untuk siswa yang mengambil program <strong>HSK</strong>.
  </div>
</div>

<div class="alumni-banner bg-pale border border-gold rounded-xl p-4">
  <h3 class="font-semibold mb-1">Status: Alumni</h3>
  <p class="text-xs text-oliveDark">Kamu login sebagai alumni. Sertifikat lama tetap bisa dilihat &amp; diunduh kapan saja sebagai riwayat kelulusan.</p>
</div>

<style>.tabbtn{ padding:10px 20px; border-radius:999px; border:1.5px solid #ddd8c6; background:#fff; font-size:13px; font-weight:600; cursor:pointer; color:#8a8571; } .tabbtn.active{ background:#1c1a14; color:#fff; border-color:#1c1a14; }</style>
<script>
  function setProgram(type, btn){
    btn.parentElement.querySelectorAll('.tabbtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('sertifikat-panel').classList.toggle('hidden', type !== 'hsk');
    document.getElementById('sertifikat-kosong').classList.toggle('hidden', type === 'hsk');
  }
</script>

@endsection