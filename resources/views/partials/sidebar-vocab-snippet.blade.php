{{-- =====================================================================
  TEMPEL di layout dashboard kamu, di dalam <nav>, SETELAH link "Sertifikat".
  (Alumni: class alumni-restricted membuat menu ini terkunci seperti menu lain.)
  Nanti grup "Belajar Grammar" tinggal ditambah dengan pola yang sama.
===================================================================== --}}
@php $inVocab = request()->routeIs('student.vocab.*'); @endphp
<div id="grpVocab" class="side-group {{ $inVocab ? 'open' : '' }}">
  <button type="button" data-side-toggle="grpVocab" aria-expanded="{{ $inVocab ? 'true' : 'false' }}"
          class="side-link alumni-restricted w-full" style="border:0;background:none;cursor:pointer;text-align:left">
    <span class="ico">📖</span> Belajar Vocab
    <svg class="side-chev ml-auto" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 5 7 7-7 7"/></svg>
  </button>
  <div class="side-subwrap">
    <a href="{{ route('student.vocab.plan') }}"  class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.plan') ? 'is-active' : '' }}"><span>📅</span> Study Plan</a>
    <a href="{{ route('student.vocab.list') }}"  class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.list') ? 'is-active' : '' }}"><span>📖</span> Vocab List</a>
    <a href="{{ route('student.vocab.test') }}"  class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.test*') ? 'is-active' : '' }}"><span>✍️</span> Test</a>
    <a href="{{ route('student.vocab.traps') }}" class="side-sub alumni-restricted {{ request()->routeIs('student.vocab.traps*') ? 'is-active' : '' }}"><span>⚠️</span> Vocab Traps</a>
  </div>
</div>

{{-- ===== TAMBAHKAN ke @push('styles') / <style> layout ===== --}}
<style>
  .side-subwrap{ display:none; padding-left:18px; margin:-2px 0 6px; }
  .side-group.open .side-subwrap{ display:block; }
  .side-group.open .side-chev{ transform:rotate(90deg); }
  .side-sub{ display:flex; align-items:center; gap:10px; padding:8px 12px; margin-bottom:3px; font-size:13.5px; font-weight:500; color:#4a4636; border-left:2px solid #F0EDE0; border-radius:0 10px 10px 0; text-decoration:none; }
  .side-sub:hover{ background:#FFF8D6; }
  .side-sub.is-active{ background:#FFEC9E; border-left-color:#FFD60A; color:#1A1A1A; font-weight:600; }
  .alumni-mode .side-sub.alumni-restricted{ opacity:.35; pointer-events:none; }
</style>

{{-- ===== TAMBAHKAN ke <script> layout (buka/tutup grup) ===== --}}
<script>
  document.querySelectorAll('[data-side-toggle]').forEach(function(b){
    b.addEventListener('click', function(){
      var g = document.getElementById(b.dataset.sideToggle);
      b.setAttribute('aria-expanded', g.classList.toggle('open') ? 'true' : 'false');
    });
  });
</script>
