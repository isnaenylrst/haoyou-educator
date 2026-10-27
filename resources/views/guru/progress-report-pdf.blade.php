<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @font-face {
        font-family: 'NotoSansSC';
        src: url('{{ storage_path('fonts/NotoSansSC-Regular.ttf') }}') format('truetype');
        font-weight: normal;
    }
    body { font-family: 'NotoSansSC', 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
    h1 { text-align: center; font-size: 16px; margin: 6px 0 14px; }
    .head td { padding: 2px 6px; }
    table.grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.grid th, table.grid td { border: 1px solid #444; padding: 6px; vertical-align: top; }
    table.grid th { background: #f3e3e3; }
    .center { text-align: center; }
    .nl { white-space: pre-line; }
    .avg { margin-top: 10px; font-weight: bold; }
</style>
</head>
<body>
    <h1>{{ strtoupper($report->report_type) }}</h1>

    <table class="head">
        <tr><td>Teacher</td><td>: {{ $teacher->name }}</td></tr>
        <tr><td>Student</td><td>: {{ $e->student_name }}</td></tr>
        <tr><td>Class</td><td>: {{ $e->class_name }}</td></tr>
        <tr><td>Period</td><td>: {{ $report->report_period }}</td></tr>
        <tr><td>Present</td><td>: {{ $report->attendance_present }}/{{ $report->attendance_total }}</td></tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th style="width:5%">NO</th>
                <th style="width:27%">Learning Competency</th>
                <th style="width:14%">Achievement Level</th>
                <th style="width:9%">Score</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        @foreach($report->content as $i => $row)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                {{-- report lama memakai kunci "materials" --}}
                <td class="nl">{{ $row['competency'] ?? ($row['materials'] ?? '-') }}</td>
                <td class="center">{{ $row['level'] ?? '-' }}</td>
                <td class="center">{{ isset($row['score']) && $row['score'] !== '' ? rtrim(rtrim(number_format((float) $row['score'], 1), '0'), '.') : '-' }}</td>
                <td class="nl">{{ $row['description'] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if($report->average_score !== null)
        <div class="avg">Average Score: {{ $report->average_score }}</div>
    @endif
</body>
</html>