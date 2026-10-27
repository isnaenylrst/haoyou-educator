<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verifikasi Sertifikat | HaoYou</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-50 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white border border-stone-200 rounded-2xl p-6 w-full max-w-md">
    <div class="flex items-center gap-2 text-green-700 text-sm font-semibold mb-4">
      <span>✔</span> Sertifikat asli dan terdaftar
      <p>Hasil: <strong>{{ $certificate->result_label }}</strong></p>
      <p>Total nilai: {{ $certificate->total_score }} / {{ $certificate->max_total }}</p>
    </div>
    <dl class="text-sm space-y-2">
      <div class="flex justify-between gap-4"><dt class="text-stone-500">Nama</dt><dd class="font-medium text-right">{{ $certificate->student_name }}</dd></div>
      <div class="flex justify-between gap-4"><dt class="text-stone-500">Level</dt><dd class="font-medium">{{ $certificate->level_name }}</dd></div>
      <div class="flex justify-between gap-4"><dt class="text-stone-500">No. sertifikat</dt><dd class="font-medium">{{ $certificate->certificate_number }}</dd></div>
      <div class="flex justify-between gap-4"><dt class="text-stone-500">Diterbitkan</dt><dd class="font-medium">{{ $certificate->issued_at->translatedFormat('d F Y') }}</dd></div>
      @foreach ($certificate->scores as $name => $value)
        <div class="flex justify-between gap-4"><dt class="text-stone-500">{{ ucfirst($name) }}</dt><dd class="font-medium">{{ $value }}</dd></div>
      @endforeach
      <div class="flex justify-between gap-4"><dt class="text-stone-500">Total score</dt><dd class="font-medium">{{ $certificate->total_score }} / {{ $certificate->max_total }}</dd></div>
      <div class="flex justify-between gap-4"><dt class="text-stone-500">Hasil</dt>
        <dd class="font-medium">{{ $certificate->isPassed() ? 'Memenuhi standar' : 'Belum memenuhi standar' }} {{ $certificate->level_name }}</dd></div>
    </dl>
  </div>
</body>
</html>
