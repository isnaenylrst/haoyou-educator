@extends('layouts.dashboard')

@section('dashboard-content')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .font-poppins {
        font-family: 'Poppins', sans-serif;
    }
</style>
@endpush


<div class="bg-[#f7f8fa] min-h-screen font-poppins">

    {{-- =========================================================
        STATISTIK
    ========================================================== --}}

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        {{-- TOTAL POIN --}}
        <div class="bg-white border border-line rounded-2xl p-5">

            <div class="text-3xl font-bold text-black">
                {{ number_format($stats['total_poin'], 0, ',', '.') }}
            </div>

            <div class="text-xs text-gray-500 mt-1">
                Total Poin
            </div>

        </div>


        {{-- RANKING --}}
        <div class="bg-white border border-line rounded-2xl p-5">

            <div class="text-3xl font-bold text-black">
                #{{ $stats['peringkat'] }}
            </div>

            <div class="text-xs text-gray-500 mt-1">
                Peringkat Leaderboard
            </div>

        </div>


        {{-- KELAS MINGGU INI --}}
        <div class="bg-white border border-line rounded-2xl p-5">

            <div class="text-3xl font-bold text-black">
                {{ $stats['kelas_minggu_ini'] }}
            </div>

            <div class="text-xs text-gray-500 mt-1">
                Kelas Terjadwal Minggu Ini
            </div>

        </div>

    </div>



    {{-- =========================================================
        JADWAL TERDEKAT
    ========================================================== --}}

    <div class="bg-white border border-line rounded-2xl p-5 mb-5">

        <div class="flex items-center justify-between mb-4">

            <h3 class="font-semibold text-black">
                Jadwal Terdekat
            </h3>

            <a href="{{ route('kelassaya.index') }}"
               class="text-xs text-black font-semibold hover:underline">
                Lihat semua →
            </a>

        </div>


        @forelse ($jadwalTerdekat as $jadwal)

            <div class="flex items-center justify-between py-3 border-b border-line last:border-0">

                <div class="flex items-center gap-3">

                    {{-- ICON --}}
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center text-lg"
                        style="background:#FFF4B8;"
                    >
                        {{ $jadwal['platform'] === 'online' ? '🀄' : '📖' }}
                    </div>


                    {{-- INFORMASI KELAS --}}
                    <div>

                        <div class="text-sm font-medium text-black">
                            {{ $jadwal['judul'] }}
                        </div>

                        <div class="text-xs text-gray-500">
                            {{ $jadwal['waktu'] }}
                        </div>

                    </div>

                </div>


                {{-- ONLINE / OFFLINE --}}
                <span
                    class="text-[11px] font-semibold px-3 py-1 rounded-full
                    {{ $jadwal['platform'] === 'online'
                        ? 'bg-[#e2ecd7] text-black'
                        : 'bg-[#fff4b8] text-black' }}"
                >
                    {{ ucfirst($jadwal['platform']) }}
                </span>

            </div>

        @empty

            <div class="py-8 text-center">

                <div class="text-3xl mb-2">
                    📅
                </div>

                <p class="text-sm text-gray-500">
                    Belum ada jadwal kelas.
                </p>

            </div>

        @endforelse

    </div>



    {{-- =========================================================
        LEADERBOARD
    ========================================================== --}}

    <div class="bg-white border border-line rounded-2xl p-5">

        <h3 class="font-semibold text-black mb-4">
            Leaderboard Poin
        </h3>


        @forelse ($leaderboard as $item)

            <div
                class="flex items-center gap-3 py-2.5 border-b border-line last:border-0 text-sm"
            >

                {{-- RANK --}}
                <div
                    class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $item['rank'] === 1
                        ? 'bg-[#FFDD05] text-black'
                        : 'bg-[#f1f1ed] text-black' }}"
                >
                    {{ $item['rank'] }}
                </div>


                {{-- NAMA --}}
                <span class="text-black">

                    {{ $item['nama'] }}

                    @if ($item['nama'] === $student->name)
                        <span class="text-xs text-gray-500">
                            (kamu)
                        </span>
                    @endif

                </span>


                {{-- POIN --}}
                <span class="ml-auto font-bold text-black">

                    {{ number_format($item['poin'], 0, ',', '.') }}

                </span>

            </div>

        @empty

            <div class="py-6 text-center text-sm text-gray-500">

                Belum ada data leaderboard.

            </div>

        @endforelse

    </div>

</div>

@endsection