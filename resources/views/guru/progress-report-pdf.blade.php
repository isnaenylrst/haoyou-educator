<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Progress Report - {{ $e->student_name ?? $report->student->name }}</title>
<style>
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 20px; }
    .header-table { width: 100%; border-bottom: 2px solid #ffd600; padding-bottom: 12px; margin-bottom: 15px; }
    .title { text-align: center; font-size: 16px; font-weight: bold; margin: 5px 0 15px; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-table { width: 100%; margin-bottom: 15px; }
    .info-table td { padding: 3px 6px; font-size: 11px; }
    table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 18px; }
    table.grid th, table.grid td { border: 1px solid #ddd; padding: 6px 8px; vertical-align: top; }
    table.grid th { background: #fff8d6; color: #3a2e00; font-weight: bold; text-align: left; }
    .center { text-align: center; }
    .section-title { font-size: 12px; font-weight: bold; color: #1d1d27; margin-top: 15px; margin-bottom: 4px; border-bottom: 1px solid #ffd600; padding-bottom: 3px; }
    .suggestion-box { border: 1px solid #e0e0e0; background: #fafafa; border-radius: 4px; padding: 10px; min-height: 40px; margin-bottom: 20px; font-style: italic; }
    .sig-table { width: 100%; margin-top: 25px; border-collapse: collapse; }
    .sig-table td { width: 50%; text-align: center; vertical-align: top; padding: 10px; }
    .sig-img { height: 60px; max-width: 140px; object-fit: contain; margin: 5px auto; }
</style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 70px;">
                <img src="{{ public_path('image/Logo-Haoyou.jpeg') }}" style="max-height: 55px;" alt="Logo">
            </td>
            <td>
                <div style="font-size: 15px; font-weight: bold; color: #1d1d27;">HAOYOU MANDARIN EDUCATOR</div>
                <div style="font-size: 10px; color: #666;">Progress & Competency Academic Report</div>
            </td>
            <td style="text-align: right; font-size: 10px; color: #666;">
                Tanggal Terbit: <strong>{{ $report->published_at ? $report->published_at->translatedFormat('d F Y') : ($report->reviewed_at ? $report->reviewed_at->translatedFormat('d F Y') : 'Draft') }}</strong>
            </td>
        </tr>
    </table>

    <div class="title">{{ strtoupper($report->report_type ?: 'STUDENT PROGRESS REPORT') }}</div>

    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Student Name</strong></td>
            <td style="width: 35%;">: {{ $e->student_name ?? $report->student->name }}</td>
            <td style="width: 18%;"><strong>Class / Level</strong></td>
            <td style="width: 32%;">: {{ $e->class_name ?? $report->enrollment?->class?->class_name }}</td>
        </tr>
        <tr>
            <td><strong>Teacher</strong></td>
            <td>: {{ $teacher->name ?? $report->teacher->name }}</td>
            <td><strong>Period</strong></td>
            <td>: {{ $report->report_period ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Kehadiran</strong></td>
            <td>: {{ $report->attendance_present }} / {{ $report->attendance_total }} Pertemuan</td>
            <td><strong>Kelas Pengganti</strong></td>
            <td>: <strong>{{ $report->replacement_class_count ?? 0 }} kali</strong></td>
        </tr>
    </table>

    {{-- BAGIAN 1: LEARNING MATERIALS --}}
    <div class="section-title">1. LEARNING MATERIALS & ACHIEVEMENT LEVEL</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">NO</th>
                <th style="width: 35%;">Learning Materials</th>
                <th style="width: 20%; text-align: center;">Achievement Level</th>
                <th style="width: 40%;">Description</th>
            </tr>
        </thead>
        <tbody>
            @php $materials = $report->content ?? []; @endphp
            @forelse($materials as $idx => $row)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $row['material'] ?? ($row['competency'] ?? '-') }}</strong></td>
                    <td class="center">
                        <span style="font-weight: bold; color: {{ ($row['level'] ?? '') === 'Excellent' ? '#16a34a' : (($row['level'] ?? '') === 'Proficient' ? '#2563eb' : '#d97706') }}">
                            {{ $row['level'] ?? '-' }}
                        </span>
                    </td>
                    <td>{{ $row['description'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center text-muted">Belum ada materi pembelajaran yang diinput.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- BAGIAN 2: ASSESSMENT --}}
    <div class="section-title">2. ASSESSMENT & EVALUATION</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">NO</th>
                <th style="width: 35%;">Assessment</th>
                <th style="width: 15%; text-align: center;">Score</th>
                <th style="width: 15%; text-align: center;">Grade</th>
                <th style="width: 30%;">Note</th>
            </tr>
        </thead>
        <tbody>
            @php $assessments = $report->assessment_data ?? []; @endphp
            @forelse($assessments as $aIdx => $aRow)
                <tr>
                    <td class="center">{{ $aIdx + 1 }}</td>
                    <td><strong>{{ $aRow['assessment'] ?? '-' }}</strong></td>
                    <td class="center"><strong>{{ $aRow['score'] ?? '-' }}</strong></td>
                    <td class="center"><span style="font-weight: bold;">{{ $aRow['grade'] ?? '-' }}</span></td>
                    <td>{{ $aRow['note'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center">1</td>
                    <td>Speaking & Pronunciation</td>
                    <td class="center">{{ $report->listening_score ?? 85 }}</td>
                    <td class="center">A</td>
                    <td>Good pronunciation and confidence</td>
                </tr>
                <tr>
                    <td class="center">2</td>
                    <td>Reading Comprehension</td>
                    <td class="center">{{ $report->reading_score ?? 88 }}</td>
                    <td class="center">A</td>
                    <td>Understands sentences clearly</td>
                </tr>
                <tr>
                    <td class="center">3</td>
                    <td>Writing (Hanyu Pinyin / Hanzi)</td>
                    <td class="center">{{ $report->writing_score ?? 82 }}</td>
                    <td class="center">B</td>
                    <td>Keep practicing stroke orders</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- BAGIAN 3: SUGGESTION BOX --}}
    <div class="section-title">3. SUGGESTION BOX & FEEDBACK</div>
    <div class="suggestion-box">
        {{ $report->suggestion_box ?: 'Pertahankan semangat belajar dan tingkatkan latihan percakapan Mandarin secara aktif di rumah!' }}
    </div>

    {{-- BAGIAN 4: TANDA TANGAN --}}
    <table class="sig-table">
        <tr>
            <td>
                <div>Disetujui Oleh (Kurikulum):</div>
                <div style="height: 65px; margin: 5px 0;">
                    @if($report->curriculum_signature_path && file_exists(storage_path('app/public/' . $report->curriculum_signature_path)))
                        <img src="{{ storage_path('app/public/' . $report->curriculum_signature_path) }}" class="sig-img" alt="TTD Kurikulum">
                    @elseif($report->reviewed_at)
                        <div style="margin-top: 20px; font-weight: bold; color: green;">[ACC DIGITAL KURIKULUM]</div>
                    @else
                        <div style="margin-top: 20px; color: #999;">-</div>
                    @endif
                </div>
                <div style="font-weight: bold; text-decoration: underline;">BAGIAN KURIKULUM</div>
                <div style="font-size: 10px; color: #666;">Haoyou Mandarin Educator</div>
            </td>
            <td>
                <div>Mengetahui (Owner):</div>
                <div style="height: 65px; margin: 5px 0;">
                    @if($report->owner_signature_path && file_exists(storage_path('app/public/' . $report->owner_signature_path)))
                        <img src="{{ storage_path('app/public/' . $report->owner_signature_path) }}" class="sig-img" alt="TTD Owner">
                    @elseif($report->published_at)
                        <div style="margin-top: 20px; font-weight: bold; color: green;">[VERIFIED OWNER]</div>
                    @else
                        <div style="margin-top: 20px; color: #999;">-</div>
                    @endif
                </div>
                <div style="font-weight: bold; text-decoration: underline;">DIREKTUR / OWNER</div>
                <div style="font-size: 10px; color: #666;">Haoyou Mandarin Educator</div>
            </td>
        </tr>
    </table>

</body>
</html>
