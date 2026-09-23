@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-[#f7f8fa] min-h-screen">

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

                    {{-- ICON --}}
                    <div class="flex items-center gap-3">

                        <div class="w-11 h-11 rounded-xl bg-[#fff4b8] flex items-center justify-center text-lg">
                            {{ strtolower($kelas->delivery_mode) === 'online' ? '🀄' : '📖' }}
                        </div>

                        {{-- INFORMASI KELAS --}}
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


                    {{-- STATUS ONLINE / OFFLINE --}}
                    <div>

                        @if (strtolower($kelas->delivery_mode) === 'online')

                            <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-[#e2ecd7] text-black">
                                Online
                            </span>

                        @else

                            <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-[#fff4b8] text-black">
                                Offline
                            </span>

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

        📍 Untuk kelas <b>Offline</b>, sistem menampilkan informasi kelas.
        Untuk kelas <b>Online</b>, sistem menampilkan status online.

    </div>

</div>

@endsection