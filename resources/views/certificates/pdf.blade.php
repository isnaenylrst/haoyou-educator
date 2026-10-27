@php
    $passed   = $c->isPassed();
    $cols     = count($c->scores) + 1;
    $tableW   = $cols * 120;
    $label    = $c->level_name;
    $title    = $passed ? 'Sertifikat Kelulusan' : 'Sertifikat Hasil Ujian';
    $desc     = $passed
        ? 'atas keberhasilan menyelesaikan program dan mengikuti ujian akhir <b>' . e($label) . '</b>'
        : 'atas keikutsertaan dalam ujian akhir <b>' . e($label) . '</b>';
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
    <img src="{{ $logo }}" style="height:78pt;">
  </div>

  {{-- Judul --}}
  <div class="abs" style="top:130pt; font-size:28pt; letter-spacing:1pt;">{{ $title }}</div>
  <div class="abs sans" style="top:168pt; font-size:8.5pt; letter-spacing:2pt; color:#8A6508;">
    {{ $c->level_name }} &nbsp;&middot;&nbsp; NO. {{ $c->certificate_number }}
  </div>

  {{-- Nama --}}
  <div class="abs sans" style="top:196pt; font-size:9.5pt; color:#5F5E5A;">Dengan bangga diberikan kepada</div>
  <div class="abs" style="top:212pt;">
    <span style="display:inline-block; font-size:28pt; font-style:italic; padding:0 24pt 3pt 24pt; border-bottom:1pt solid #B8860B;">{{ $c->student_name }}</span>
  </div>
  <div class="abs sans" style="top:258pt; font-size:10pt; color:#3D3D3A;">{!! $desc !!}</div>

  {{-- Nilai --}}
  <div class="abs" style="top:288pt;">
    <table class="sans" style="margin:0 auto; border-collapse:collapse; width:{{ $tableW }}pt;">
      <tr>
        @foreach ($c->scores as $name => $value)
          <td style="border:0.75pt solid #CFCBBE; padding:6pt 4pt; text-align:center;">
            <div style="font-size:7.5pt; color:#5F5E5A; letter-spacing:1pt;">{{ strtoupper($name) }}</div>
            <div style="font-size:16pt; font-weight:bold;">{{ $value }}</div>
          </td>
        @endforeach
        <td style="border:0.75pt solid #CFCBBE; padding:6pt 4pt; text-align:center; background:#FAEEDA; color:#633806;">
          <div style="font-size:7.5pt; letter-spacing:1pt;">TOTAL SCORE</div>
          <div style="font-size:16pt; font-weight:bold;">{{ $c->total_score }} <span style="font-size:9pt; font-weight:normal;">/ {{ $c->max_total }}</span></div>
        </td>
      </tr>
    </table>
    <div class="sans" style="font-size:8pt; color:#5F5E5A; margin-top:4pt;">
      Standar kelulusan: minimal {{ $c->passing_score }} dari {{ $c->max_total }}
    </div>
  </div>

  {{-- Status --}}
  <div class="abs sans" style="top:352pt;">
    <span style="display:inline-block; font-size:10pt; font-weight:bold; padding:3pt 16pt;
                 background:{{ $passed ? '#EAF3DE' : '#FCEBEB' }}; color:{{ $passed ? '#27500A' : '#791F1F' }};">
      {{ $passed ? 'LULUS' : 'TIDAK LULUS' }} &middot; {{ $passed ? 'Memenuhi standar' : 'Belum memenuhi standar' }} {{ $c->level_name }}
    </span>
  </div>

  {{-- Footer: tanggal | QR | tanda tangan --}}
  <div class="abs sans" style="top:408pt;">
    <table style="margin:0 auto; width:700pt; border-collapse:collapse;">
      <tr>
        <td style="width:220pt; text-align:left; vertical-align:bottom; font-size:8.5pt; color:#5F5E5A;">
          Diterbitkan di {{ config('certificate.issue_place') }}<br>
          <span style="font-size:10pt; color:#23211C;">{{ $c->issued_at->translatedFormat('d F Y') }}</span>
        </td>
        <td style="width:260pt; text-align:center; vertical-align:bottom;">
          <img src="{{ $qr }}" style="width:52pt; height:52pt;"><br>
          <span style="font-size:7pt; color:#5F5E5A;">Pindai untuk verifikasi keaslian</span>
        </td>
        <td style="width:220pt; text-align:center; vertical-align:bottom;">
          <img src="{{ $signature }}" style="height:50pt;"><br>
          <div style="border-top:0.75pt solid #23211C; padding-top:2pt; font-size:10pt;">{{ config('certificate.signer_name') }}</div>
          <div style="font-size:8pt; color:#5F5E5A;">{{ config('certificate.signer_title') }}</div>
        </td>
      </tr>
    </table>
  </div>

</body>
</html>