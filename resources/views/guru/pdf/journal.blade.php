<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Jurnal Harian Laoshi</title>
<style>
    /* DejaVu Sans mendukung tanda nada pinyin. Hanzi butuh font tambahan (mis. Noto Sans SC). */
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; margin: 0 0 2px; letter-spacing: 1px; }
    .sub { color: #666; margin-bottom: 14px; }
    h2 { font-size: 12px; background: #E2C54C; padding: 5px 8px; margin: 14px 0 0; }
    table { width: 100%; border-collapse: collapse; }
    td { border: 1px solid #ccc; padding: 6px 8px; vertical-align: top; }
    td.l { width: 30%; background: #faf6e6; font-weight: bold; }
    .foot { margin-top: 18px; color: #888; font-size: 9px; }
</style>
</head>
<body>
@php
    $labels  = config('journal.attendance_status');
    $date    = \Illuminate\Support\Carbon::parse($journal->session_date);
    $running = $journal->class_status === 'Conducted';

    // Nama murid: pakai isian yang diketik guru, kalau kosong ambil dari data absensi
    $names   = $journal->student_names
                ?: $journal->attendances->map(fn ($a) => $a->student->name ?? '-')->implode(', ');

    $absent  = $journal->attendances->where('status', '!=', 'Present')
                 ->map(fn ($a) => ($a->student->name ?? '-').' ('.($labels[$a->status] ?? $a->status).')'
                      .($a->note ? ': '.$a->note : ''))
                 ->implode('; ');
@endphp

<h1>JURNAL HARIAN LAOSHI</h1>
<div class="sub">Haoyou Mandarin Course</div>

<h2>Informasi Guru &amp; Murid</h2>
<table>
    <tr><td class="l">Nama Guru pengajar</td><td>{{ $journal->teacher->name ?? '-' }}</td></tr>
    <tr><td class="l">Menggantikan Mengajar</td>
        <td>{{ $journal->is_substitute ? 'Ya (menggantikan '.($journal->substituteTeacher->name ?? '-').')' : 'Tidak' }}</td></tr>
    <tr><td class="l">Nama Murid</td><td>{{ $names ?: '-' }}</td></tr>
</table>

<h2>Jadwal Kelas</h2>
<table>
    <tr><td class="l">Pertemuan terakhir</td><td>{{ $journal->is_last_meeting ? 'Ya' : 'Tidak' }}</td></tr>
    <tr><td class="l">Pertemuan ke</td><td>{{ $journal->meeting_number ?? '-' }}</td></tr>
    <tr><td class="l">Tanggal</td><td>{{ $date->format('d/m/Y') }}</td></tr>
    <tr><td class="l">Jam mulai - selesai</td>
        <td>{{ substr($journal->start_time ?? '', 0, 5) }} - {{ substr($journal->end_time ?? '', 0, 5) }}</td></tr>
    <tr><td class="l">Kelas Kursus Mandarin Haoyou</td>
        <td>{{ $journal->course_type }}{{ $journal->course_level ? ' - '.$journal->course_level : '' }}</td></tr>
</table>

<h2>Keterangan Berjalannya Kelas</h2>
<table>
    <tr><td class="l">Fokus Materi</td><td>{{ collect($journal->focus_materials)->implode(', ') ?: '-' }}</td></tr>
    <tr><td class="l">Kelas berjalan</td><td>{{ $running ? 'Berjalan' : 'Tidak Berjalan' }}</td></tr>
</table>

<h2>Materi</h2>
<table>
    <tr><td class="l">Materi</td><td>{{ $journal->material_taught ?: '-' }}</td></tr>
    <tr><td class="l">Aktivitas</td><td>{!! nl2br(e($journal->learning_activities ?: '-')) !!}</td></tr>
    <tr><td class="l">Problem</td><td>{!! nl2br(e($journal->problems ?: '-')) !!}</td></tr>
    <tr><td class="l">Solusi</td><td>{!! nl2br(e($journal->solutions ?: '-')) !!}</td></tr>
    <tr><td class="l">Hasil</td><td>{!! nl2br(e($journal->results ?: '-')) !!}</td></tr>
    <tr><td class="l">Point</td><td>{{ $journal->session_score ?? '-' }}</td></tr>
    <tr><td class="l">Yang Tidak Masuk</td><td>{{ $absent ?: '-' }}</td></tr>
</table>

<div class="foot">Dicetak {{ now()->format('d/m/Y H:i') }} &middot; Status jurnal: {{ $journal->status }}</div>
</body>
</html>