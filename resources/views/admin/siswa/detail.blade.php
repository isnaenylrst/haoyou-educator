{{-- resources/views/admin/siswa/detail.blade.php --}}
@extends('admin.app')

@section('title', 'Detail Siswa | Siswa')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/calonsiswa.css') }}">
@endpush

@section('content')

    @php
        $statusLabels = ['Active' => 'Aktif', 'Inactive' => 'Cuti', 'Graduated' => 'Lulus'];

        $enrollmentLabels = ['Active' => 'Berjalan', 'Completed' => 'Selesai', 'Cancelled' => 'Dibatalkan', 'Waiting Class' => 'Menunggu Kelas'];

        $sessionLabels = [
            'Scheduled'   => 'Terjadwal',
            'Conducted'   => 'Selesai',
            'Cancelled'   => 'Dibatalkan',
            'Rescheduled' => 'Dijadwal ulang',
        ];

        $money = fn ($v) => 'Rp ' . number_format((float) ($v ?? 0), 0, ',', '.');
    @endphp

    <div class="dashboard-header">
        <div>
            <div class="eyebrow">AKADEMIK &raquo; SISWA</div>
            <h1>Detail &amp; Edit Siswa</h1>
            <p>{{ $siswa->name }}</p>
        </div>

        <div class="dashboard-header-actions">
            <a href="{{ route('admin.siswa') }}" class="btn">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali ke Siswa
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="sd-alert ok">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="sd-alert err">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="sd-alert err">
            Data belum bisa disimpan:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== Data siswa (bisa diedit) ===== --}}
    <form method="POST" action="{{ route('admin.siswa.update', $siswa) }}" class="sd-card">
        @csrf
        @method('PUT')
        <input type="hidden" name="_from" value="detail">

        <div class="form-section" style="margin-bottom:0;">
            <div class="form-section-title"><i class="fa-solid fa-user"></i> Data Siswa</div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="name">Nama Siswa *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $siswa->name) }}" required>
                </div>
                
                <div class="form-field">
                    <label>Usia</label>
                    <div class="sd-info">{{ $siswa->age ? $siswa->age . ' Tahun' : '-' }}</div>
                </div>

                <div class="form-field">
                    <label for="status">Status Siswa *</label>
                    <select name="status" id="status" required>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $siswa->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label>No. Telp</label>
                    <div class="sd-info">{{ $phone }}</div>
                </div>

                <div class="form-field">
                    <label>Tanggal Bergabung</label>
                    <input type="date" value="{{ optional($siswa->join_date)->format('Y-m-d') }}" disabled>
                    <small>
                        <i class="fa-solid fa-circle-info"></i>
                        Otomatis tercatat dari tanggal pembayaran pertama pada program yang sedang diikuti.
                    </small>
                </div>

                <div class="form-field">
                    <label for="points">Poin</label>
                    <input type="number" name="points" id="points" min="0" value="{{ old('points', $siswa->points ?? 0) }}">
                </div>
            </div>
        </div>

        <div class="sd-actions">
            <a href="{{ route('admin.siswa') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i>
                Simpan Perubahan
            </button>
        </div>
    </form>

    {{-- ===== Jadwal kelas aktif ===== --}}
    <div class="sd-card">
        <div class="form-section" style="margin-bottom:0;">
            <div class="form-section-title"><i class="fa-solid fa-calendar-week"></i> Jadwal Siswa</div>

            <div class="sd-scroll">
                <table class="table-mini">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th style="text-align: center; vertical-align: middle;">Jam Mulai</th>
                            <th style="text-align: center; vertical-align: middle;">Jam Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($schedules as $s)
                            <tr>
                                <td>{{ $s->day }}</td>
                                <td style="text-align: center; vertical-align: middle;">{{ substr($s->start_time, 0, 5) }}</td>
                                <td style="text-align: center; vertical-align: middle;">{{ substr($s->end_time, 0, 5) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">Belum ada jadwal kelas aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Program yang diikuti (+ kelas pengganti) ===== --}}
    <div class="sd-card">
        <div class="form-section" style="margin-bottom:0;">
            <div class="form-section-title"><i class="fa-solid fa-graduation-cap"></i> Program yang Diikuti</div>

            <div class="sd-scroll">
                <table class="table-mini">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th style="text-align: center; vertical-align: middle;">Kelas</th>
                            <th style="text-align: center; vertical-align: middle;">Tipe</th>
                            <th style="text-align: center; vertical-align: middle;">Status</th>
                            <th style="text-align: center; vertical-align: middle;">Tanggal Daftar</th>
                            <th style="text-align: center; vertical-align: middle;">Hadir</th>
                            <th style="text-align: center; vertical-align: middle;">Tidak Hadir</th>
                            <th style="text-align: center; vertical-align: middle;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($programs as $p)
                            @php
                                $total = $p['total_meetings'] ? '/' . $p['total_meetings'] : '';
                                $reps  = $p['replacements'];
                            @endphp
                            <tr>
                                <td>
                                    {{ $p['program_name'] }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $p['class_name'] }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $p['course_type'] }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $enrollmentLabels[$p['status']] ?? $p['status'] }}
                                </td style="text-align: center; vertical-align: middle;">
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $p['enrollment_date'] ?? '-' }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    @if ($p['attended'] !== null)
                                        <div class="meet-label"><strong>{{ $p['attended'] }}{{ $total }}</strong> (hadir)</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    @if ($p['absent'] !== null)
                                        <div class="meet-label absent"><strong>{{ $p['absent'] }}{{ $total }}</strong> (tidak hadir)</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div class="sd-icon-row" style="display: flex; justify-content: center; align-items: center;">
                                        <a href="{{ route('admin.siswa.formulir', [$siswa, $p['enrollment_id']]) }}" target="_blank" rel="noopener"
                                        class="sd-icon-btn" title="Formulir pendaftaran (PDF)" aria-label="Formulir pendaftaran">
                                            <i class="fa-regular fa-file-pdf"></i>
                                        </a>

                                        @if (count($reps))
                                            <button type="button" class="sd-icon-btn"
                                                    onclick="openRpModal('rpModal{{ $loop->index }}')"
                                                    title="Riwayat kelas pengganti ({{ count($reps) }})" aria-label="Riwayat kelas pengganti">
                                                <i class="fa-solid fa-calendar-plus"></i>
                                                <span class="sd-count">{{ count($reps) }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr><td colspan="8">Belum ada program yang diikuti.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Modal riwayat kelas pengganti ===== --}}
    @foreach ($programs as $p)
        @continue(! count($p['replacements']))

        <div class="sd-modal-overlay" id="rpModal{{ $loop->index }}" onclick="if (event.target === this) closeRpModal(this.id)">
            <div class="sd-modal" role="dialog" aria-modal="true">
                <div class="sd-modal-head">
                    <div>
                        <div class="sd-modal-title"><i class="fa-solid fa-calendar-plus"></i> Riwayat Kelas Pengganti</div>
                        <div class="sd-muted">{{ $p['program_name'] }} &middot; {{ $p['class_name'] }}</div>
                    </div>
                    <button type="button" class="sd-modal-close" onclick="closeRpModal('rpModal{{ $loop->index }}')" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="sd-modal-body">
                    <div class="sd-scroll">
                        <table class="table-mini">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th style="text-align: center; vertical-align: middle;">Jam</th>
                                    <th style="text-align: center; vertical-align: middle;">Guru</th>
                                    <th style="text-align: center; vertical-align: middle;">Lokasi</th>
                                    <th style="text-align: center; vertical-align: middle;">Status</th>
                                    <th>Invoice</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($p['replacements'] as $r)
                                    <tr>
                                        <td>
                                            {{ $r['date'] }}
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            {{ $r['time'] }}
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            {{ $r['teacher'] ?? 'Belum ada guru' }}
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            {{ $r['location_type'] ?? '-' }}
                                        </td>
                                        <td>
                                            @if ($r['awaiting'])
                                                <strong>Menunggu guru</strong>
                                            @else
                                                {{ $sessionLabels[$r['status']] ?? $r['status'] }}
                                            @endif
                                        </td>
                                        <td>
                                            @forelse ($r['payments'] as $pay)
                                                <div>
                                                    <a href="{{ $pay['invoice_url'] }}" target="_blank" rel="noopener">{{ $pay['invoice_number'] ?? 'Invoice' }}</a>
                                                </div>
                                            @empty
                                                -
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- ===== Progress report ===== --}}
    <div class="sd-card">
        <div class="form-section" style="margin-bottom:0;">
            <div class="form-section-title"><i class="fa-solid fa-chart-line"></i> Progress Report</div>

            <div class="sd-scroll">
                <table class="table-mini">
                    <thead>
                        <tr>
                            <th>Tipe</th>
                            <th style="text-align: center; vertical-align: middle;">Periode</th>
                            <th style="text-align: center; vertical-align: middle;">Status</th>
                            <th style="text-align: center; vertical-align: middle;">Tanggal Upload</th>
                            <th style="text-align: center; vertical-align: middle;">File</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswa->progressReports as $r)
                            <tr>
                                <td>
                                    {{ $r->report_type ?? '-' }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $r->report_period ?? '-' }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $r->status ?? '-' }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ optional($r->uploaded_at)->format('d M Y') ?? '-' }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    @if ($r->file_path)
                                        <a href="{{ Storage::url($r->file_path) }}" target="_blank" rel="noopener"
                                           class="sd-icon-btn" title="Buka file report" aria-label="Buka file report">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; vertical-align: middle;">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Status pembayaran ===== --}}
    <div class="sd-card">
        <div class="form-section" style="margin-bottom:0;">
            <div class="form-section-title"><i class="fa-solid fa-money-bill-wave"></i> Status Pembayaran</div>

            <div class="sd-scroll">
                <table class="table-mini">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Program</th>
                            <th style="text-align: center; vertical-align: middle;">Total Tagihan</th>
                            <th style="text-align: center; vertical-align: middle;">Dibayar</th>
                            <th style="text-align: center; vertical-align: middle;">Sisa</th>
                            <th style="text-align: center; vertical-align: middle;">Status</th>
                            <th style="text-align: center; vertical-align: middle;">Tanggal</th>
                            <th style="text-align: center; vertical-align: middle;">Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $p)
                            <tr>
                                <td>
                                    {{ $p['invoice_number'] ?? '-' }}
                                </td>
                                <td>
                                    {{ $p['program_name'] }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $money($p['total_bill']) }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $money($p['amount_paid']) }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $money($p['remaining_bill']) }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $p['status'] ?? '-' }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $p['payment_date'] ?? '-' }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div class="sd-icon-row" style="display: flex; justify-content: center; align-items: center;">
                                        @if ($p['invoice_url'])
                                            <a href="{{ $p['invoice_url'] }}" target="_blank" rel="noopener"
                                            class="sd-icon-btn" title="Invoice" aria-label="Invoice">
                                                <i class="fa-regular fa-file-lines"></i>
                                            </a>
                                        @endif

                                        @if ($p['proof_url'])
                                            <a href="{{ $p['proof_url'] }}" target="_blank" rel="noopener"
                                            class="sd-icon-btn" title="Bukti pembayaran" aria-label="Bukti pembayaran">
                                                <i class="fa-regular fa-file-image"></i>
                                            </a>
                                        @else
                                            <span class="sd-icon-btn is-disabled" title="Bukti belum ada" aria-label="Bukti belum ada">
                                                <i class="fa-regular fa-file-image"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; vertical-align: middle;">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function openRpModal(id) {
            document.getElementById(id)?.classList.add('open');
        }

        function closeRpModal(id) {
            document.getElementById(id)?.classList.remove('open');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.sd-modal-overlay.open').forEach(el => el.classList.remove('open'));
        });
    </script>
@endpush