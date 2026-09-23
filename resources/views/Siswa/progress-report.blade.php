@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">

    {{-- Informasi Progress Report --}}
    <div class="bg-pale rounded-xl px-4 py-3 text-xs text-oliveDark mb-4">
        Laporan perkembangan belajar diterbitkan otomatis secara berkala —
        setiap <strong>3 bulan</strong> untuk kelas Reguler,
        dan setiap <strong>2 bulan</strong> untuk kelas HSK.
    </div>


    {{-- Daftar laporan --}}
    @forelse ($laporan as $item)

        <div class="flex items-center justify-between py-3 border-b border-line last:border-0">

            {{-- Icon + Informasi --}}
            <div class="flex items-center gap-3">

                <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg flex-shrink-0">
                    📊
                </div>

                <div>
                    <div class="text-sm font-semibold text-black">
                        {{ $item['judul'] ?? 'Progress Report' }}
                    </div>

                    <div class="text-xs text-gray-500 mt-1">
                        {{ $item['periode'] ?? 'Periode laporan belum tersedia' }}
                    </div>

                </div>

            </div>


            {{-- Tombol --}}
            <button
                type="button"
                class="text-xs font-semibold border border-black rounded-full px-5 py-2
                       hover:bg-black hover:text-white transition whitespace-nowrap">
                Lihat Laporan
            </button>

        </div>

    @empty

        {{-- Jika belum ada laporan --}}
        <div class="flex items-center justify-between py-3">

            <div class="flex items-center gap-3">

                <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">
                    📊
                </div>

                <div>
                    <div class="text-sm font-semibold text-black">
                        Progress Report
                    </div>

                    <div class="text-xs text-gray-500 mt-1">
                        Belum ada progress report yang diterbitkan.
                    </div>
                </div>

            </div>

            <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-pale text-oliveDark">
                Belum Terbit
            </span>

        </div>

    @endforelse


    {{-- Progress Report berikutnya --}}
    <div class="flex items-center justify-between py-3 border-t border-line mt-1">

        <div class="flex items-center gap-3">

            <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">
                ⏳
            </div>

            <div>
                <div class="text-sm font-semibold text-black">
                    Progress Report Berikutnya
                </div>

                <div class="text-xs text-gray-500 mt-1">
                    Reguler: terbit Apr 2026 · HSK: terbit Mar 2026
                </div>
            </div>

        </div>

        <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-pale text-oliveDark whitespace-nowrap">
            Belum Terbit
        </span>

    </div>

</div>


{{-- Informasi Alumni --}}
<div class="alumni-banner bg-pale border border-gold rounded-xl p-4">

    <h3 class="font-semibold mb-1">
        Status: Alumni
    </h3>

    <p class="text-xs text-oliveDark">
        Riwayat progress report tetap bisa diakses walau kamu sudah menjadi alumni
        dan tidak lagi memiliki akses ke kelas, materi, atau video pembelajaran.
    </p>

</div>

@endsection