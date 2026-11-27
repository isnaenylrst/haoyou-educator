{{--
    Masa cuti & expired per program siswa.
    Dipasang di admin/siswa/detail.blade.php (setelah kartu "Program yang Diikuti") dengan:
        @include('admin.siswa.partials.program-expiry', ['programs' => $programs])
--}}
@php
    $expiryRows = collect($programs)
        ->filter(fn ($p) => ! empty($p['phase']))
        ->sortByDesc(fn ($p) => $p['expired_raw'] ?? '')
        ->values();

    // Program dengan expired paling akhir yang belum dilanjutkan = penentu status Cuti/Lulus siswa
    $deciding = $expiryRows->first(fn ($p) => ! empty($p['expired_raw']) && $p['phase'] !== 'Dilanjutkan');
@endphp

@if ($expiryRows->isNotEmpty())
    <div class="sd-card">
        <div class="form-section" style="margin-bottom:0;">
            <div class="form-section-title"><i class="fa-solid fa-hourglass-half"></i> Program yang diikuti</div>

            <div class="sd-scroll">
                <table class="table-mini">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th style="text-align: center; vertical-align: middle;">Tipe</th>
                            <th style="text-align: center; vertical-align: middle;">Pertemuan Terakhir</th>
                            <th style="text-align: center; vertical-align: middle;">Masa Cuti</th>
                            <th style="text-align: center; vertical-align: middle;">Expired Mulai</th>
                            <th style="text-align: center; vertical-align: middle;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expiryRows as $p)
                            @php $continued = $p['phase'] === 'Dilanjutkan'; @endphp
                            <tr>
                                <td>
                                    {{ $p['program_name'] }}
                                    @if ($deciding && $deciding['enrollment_id'] === $p['enrollment_id'])
                                        <div class="sd-muted">Menentukan expired siswa</div>
                                    @endif
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $p['course_type'] }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $p['last_class_date'] ?? '-' }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    @if ($continued || ! $p['leave_start'])
                                        -
                                    @else
                                        {{ $p['leave_start'] }} &ndash; {{ $p['leave_end'] }}
                                        @if ($p['expiry_basis'] === 'private')
                                            <div class="sd-muted">Expired paket {{ $p['leave_start'] }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $continued ? '-' : ($p['expired_start'] ?? '-') }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    {{ $p['phase'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif