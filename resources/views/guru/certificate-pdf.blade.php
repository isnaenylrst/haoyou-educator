<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @font-face {
        font-family: 'NotoSansSC';
        src: url('{{ storage_path('fonts/NotoSansSC-Regular.ttf') }}') format('truetype');
    }
    @page { margin: 0; }
    body { font-family: 'NotoSansSC', 'DejaVu Sans', sans-serif; margin: 0; text-align: center; }
    .frame { margin: 24px; border: 6px double #b3261e; height: 520px; padding: 40px 50px; }
    .brand { font-size: 14px; letter-spacing: 3px; color: #b3261e; }
    .title { font-size: 36px; margin: 18px 0 4px; }
    .name  { font-size: 30px; margin: 22px 0 10px; border-bottom: 1px solid #999; display: inline-block; padding: 0 30px 6px; }
    .text  { font-size: 14px; line-height: 1.7; }
    .score { font-size: 18px; margin-top: 10px; }
    .sign  { margin-top: 40px; font-size: 13px; }
    .no    { font-size: 10px; color: #777; margin-top: 18px; }
</style>
</head>
<body>
<div class="frame">
    <div class="brand">HAOYOU EDUCATOR</div>
    <div class="title">CERTIFICATE OF COMPLETION</div>
    <div class="text">This is to certify that</div>
    <div class="name">{{ $e->student_name }}</div>
    <div class="text">
        has successfully completed the class<br>
        <strong>{{ !empty($cert['course']) ? $cert['course'] : $e->class_name }}</strong><br>
        from {{ $cert['start']->translatedFormat('d F Y') }}
        to {{ $cert['end']->translatedFormat('d F Y') }}
    </div>
    @if($cert['score'] !== null)
        <div class="score">Final Score: <strong>{{ $cert['score'] }}</strong></div>
    @endif
    <div class="sign">
        Teacher<br><br><br>
        <strong>{{ $teacher->name }}</strong>
    </div>
    <div class="no">No. {{ $number }}</div>
</div>
</body>
</html>