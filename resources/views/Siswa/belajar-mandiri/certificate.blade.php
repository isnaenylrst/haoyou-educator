@php
    $fmt     = fn ($d) => \Carbon\Carbon::parse($d)->translatedFormat('d F Y');
    $label   = $c->level_name ? 'Vocab dan Grammar ' . $c->level_name : 'Vocab dan Grammar';
    $periode = $c->period_start->isSameDay($c->period_end)
        ? $fmt($c->period_start)
        : $fmt($c->period_start) . ' s/d ' . $fmt($c->period_end);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 0; size: 842pt 595pt; }
  body { margin: 0; padding: 0; font-family: 'DejaVu Serif', serif; color: #23211C; background: #ffffff; }
  .abs { position: absolute; left: 28pt; width: 786pt; text-align: center; }
  .sans { font-family: 'DejaVu Sans', sans-serif; }
</style>
</head>
<body>

  {{-- Bingkai --}}
  <div style="position:absolute; left:20pt; top:20pt; width:800pt; height:553pt; border:1pt solid #23211C;"></div>
  <div style="position:absolute; left:28pt; top:28pt; width:782pt; height:535pt; border:2pt solid #B8860B;"></div>
  <div style="position:absolute; left:30pt; top:30pt; width:782pt; height:8pt; background:#FFD60A;"></div>

  {{-- Logo --}}
  <div class="abs" style="top:46pt;">
    @if ($logo)<img src="{{ $logo }}" style="height:78pt;">@endif
  </div>

  {{-- Judul --}}
  <div class="abs" style="top:130pt; font-size:28pt; letter-spacing:1pt;">Sertifikat Belajar Mandiri</div>
  <div class="abs sans" style="top:168pt; font-size:8.5pt; letter-spacing:2pt; color:#8A6508;">
    BELAJAR MANDIRI &nbsp;&middot;&nbsp; NO. {{ $c->certificate_number }}
  </div>

  {{-- Nama --}}
  <div class="abs sans" style="top:196pt; font-size:9.5pt; color:#5F5E5A;">Dengan bangga diberikan kepada</div>
  <div class="abs" style="top:212pt;">
    <span style="display:inline-block; font-size:28pt; font-style:italic; padding:0 24pt 3pt 24pt; border-bottom:1pt solid #B8860B;">{{ $c->student_name }}</span>
  </div>
  <div class="abs sans" style="top:258pt; font-size:10pt; color:#3D3D3A;">
    atas pencapaian dalam program belajar mandiri Mandarin <b>{{ $label }}</b>
  </div>
  <div class="abs sans" style="top:275pt; font-size:8.5pt; color:#5F5E5A;">Periode {{ $periode }}</div>

  {{-- Nilai --}}
  <div class="abs" style="top:296pt;">
    <table class="sans" style="margin:0 auto; border-collapse:collapse; width:420pt;">
      <tr>
        <td style="border:0.75pt solid #CFCBBE; padding:6pt 4pt; text-align:center; width:140pt;">
          <div style="font-size:7.5pt; color:#5F5E5A; letter-spacing:1pt;">VOCAB</div>
          <div style="font-size:16pt; font-weight:bold;">{{ $c->vocab_pct }}</div>
          <div style="font-size:7pt; color:#888780;">{{ number_format($c->vocab_stars, 0, ',', '.') }} / {{ number_format($c->vocab_max, 0, ',', '.') }} bintang</div>
        </td>
        <td style="border:0.75pt solid #CFCBBE; padding:6pt 4pt; text-align:center; width:140pt;">
          <div style="font-size:7.5pt; color:#5F5E5A; letter-spacing:1pt;">GRAMMAR</div>
          <div style="font-size:16pt; font-weight:bold;">{{ $c->grammar_pct }}</div>
          <div style="font-size:7pt; color:#888780;">{{ number_format($c->grammar_stars, 0, ',', '.') }} / {{ number_format($c->grammar_max, 0, ',', '.') }} bintang</div>
        </td>
        <td style="border:0.75pt solid #CFCBBE; padding:6pt 4pt; text-align:center; width:140pt; background:#FAEEDA; color:#633806;">
          <div style="font-size:7.5pt; letter-spacing:1pt;">TOTAL SCORE</div>
          <div style="font-size:16pt; font-weight:bold;">{{ $c->total_score }} <span style="font-size:9pt; font-weight:normal;">/ 100</span></div>
          <div style="font-size:7pt;">Predikat: {{ $c->predicate }}</div>
        </td>
      </tr>
    </table>
    <div class="sans" style="font-size:8pt; color:#5F5E5A; margin-top:4pt;">
      Standar kelulusan: minimal {{ $c->passing_score }} dari 100 &middot; bobot Vocab {{ $c->weight_vocab }}%, Grammar {{ $c->weight_grammar }}%
    </div>
  </div>

  {{-- Status --}}
  <div class="abs sans" style="top:376pt;">
    <span style="display:inline-block; font-size:10pt; font-weight:bold; padding:3pt 16pt; background:#EAF3DE; color:#27500A;">
      LULUS &middot; Memenuhi standar kelulusan belajar mandiri
    </span>
  </div>

  {{-- Dua penandatangan --}}
  <div class="abs sans" style="top:410pt;">
    <table style="margin:0 auto; width:640pt; border-collapse:collapse;">
      <tr>
        @foreach ([$curriculum, $owner] as $s)
          <td style="width:320pt; text-align:center; vertical-align:bottom; padding:0 30pt;">
            @if ($s['signature'])
              <img src="{{ $s['signature'] }}" style="height:50pt;">
            @else
              <div style="height:50pt;"></div>
            @endif

            <div style="width:150pt; margin:0 auto; border-top:0.75pt solid #23211C; padding-top:2pt; font-size:10pt;">{{ $s['name'] }}</div>
            <div style="font-size:8pt; color:#5F5E5A;">{{ $s['title'] }}</div>

            </td>
        @endforeach
      </tr>
    </table>
  </div>

  {{-- Tanggal terbit --}}
  <div class="abs sans" style="top:500pt; font-size:9pt; color:#5F5E5A;">
    Diterbitkan di {{ config('certificate.issue_place') }}, <span style="font-size:10pt; color:#23211C;">{{ $fmt($c->issued_at) }}</span>
  </div>

</body>
</html>