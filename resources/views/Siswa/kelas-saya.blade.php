@extends('layouts.dashboard')

@section('dashboard-content')

<div>

    <div class="bg-white border border-line rounded-2xl p-5 mb-5">

        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-semibold text-black">
                    Kelas Saya
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Daftar kelas yang sedang Anda ikuti.
                </p>
            </div>
        </div>

        @forelse ($enrollments as $enrollment)

            @php
                $kelas = $enrollment->class;
            @endphp

            @if ($kelas)

                <div class="flex items-center justify-between py-4 border-b border-line last:border-0">

                    {{-- ICON + INFORMASI KELAS --}}
                    <div class="flex items-center gap-3">

                        <div class="w-11 h-11 rounded-xl bg-[#fff4b8] flex items-center justify-center text-lg">
                            {{ strtolower($kelas->delivery_mode) === 'online' ? '🀄' : '📖' }}
                        </div>

                        <div>

                            <div class="text-sm font-semibold text-black">
                                {{ $kelas->class_name }}
                            </div>

                            @if ($kelas->programPackage && $kelas->programPackage->program)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $kelas->programPackage->program->program_name }}
                                </div>
                            @endif

                            {{-- JADWAL --}}
                            <div class="text-xs text-gray-500 mt-1">
                                @forelse ($kelas->schedules as $schedule)
                                    {{ $schedule->day }}
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                    -
                                    {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                    @if (!$loop->last)
                                        ,
                                    @endif
                                @empty
                                    Jadwal belum tersedia
                                @endforelse
                            </div>

                        </div>

                    </div>

                    {{-- STATUS + AKSI --}}
                    <div class="flex flex-col items-end gap-2">

                        @if (strtolower($kelas->delivery_mode) === 'online')

                            <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-[#e2ecd7] text-black">
                                Online
                            </span>

                            @if ($kelas->zoom_link)
                                <a href="{{ $kelas->zoom_link }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-xs font-semibold px-4 py-2 rounded-lg bg-[#2D8CFF] text-white hover:opacity-90">
                                    Buka Zoom
                                </a>

                                @if ($kelas->zoom_passcode)
                                    <span class="text-[11px] text-gray-500">
                                        Passcode: <b>{{ $kelas->zoom_passcode }}</b>
                                    </span>
                                @endif
                            @else
                                <span class="text-[11px] text-gray-400">
                                    Link Zoom belum tersedia
                                </span>
                            @endif

                        @else

                            <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-[#fff4b8] text-black">
                                Offline
                            </span>

                            @php
                                $rooms = $kelas->schedules->pluck('room')->filter()->unique();
                            @endphp

                            @if ($rooms->isNotEmpty())
                                <span class="text-[11px] text-gray-500">
                                    Ruangan: <b>{{ $rooms->implode(', ') }}</b>
                                </span>
                            @endif

                        @endif

                    </div>

                </div>

            @endif

        @empty

            {{-- BELUM ADA KELAS --}}
            <div class="text-center py-10">

                <div class="text-4xl mb-3">
                    📚
                </div>

                <h3 class="font-semibold text-black">
                    Belum ada kelas
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    Anda belum terdaftar pada kelas apa pun.
                </p>

            </div>

        @endforelse

    </div>

    {{-- KETERANGAN --}}
    <div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500 bg-white">

        📍 Untuk kelas <b>Offline</b>, sistem menampilkan ruangan kelas di Haoyou.
        Untuk kelas <b>Online</b>, sistem menampilkan tombol <b>Buka Zoom</b>.

    </div>

</div>

@endsection