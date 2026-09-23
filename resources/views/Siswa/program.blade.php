@extends('layouts.dashboard')

@section('dashboard-content')

<div class="bg-white border border-line rounded-2xl p-5 mb-5">

    <h3 class="font-semibold mb-1 text-black">
        Materi Pembelajaran
    </h3>

    <p class="text-xs text-gray-500 mb-5">
        Materi sesuai dengan program belajar kamu.
    </p>


    @if ($materials->isEmpty())

        <div class="text-center py-10">

            <div class="text-4xl mb-3">
                📚
            </div>

            <p class="text-sm font-medium text-black">
                Belum ada materi pembelajaran
            </p>

            <p class="text-xs text-gray-500 mt-1">
                Materi akan muncul setelah kamu terdaftar pada kelas.
            </p>

        </div>

    @else

        @foreach ($materials as $material)

            <div class="flex items-center justify-between py-4 border-b border-line last:border-0">

                <div class="flex items-center gap-3">

                    <div class="w-11 h-11 rounded-xl bg-pale flex items-center justify-center text-lg">
                        📖
                    </div>

                    <div>

                        <div class="text-sm font-medium text-black">
                            {{ $material->title }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1">

                            Pertemuan
                            {{ $material->meeting_number ?? '-' }}

                            @if ($material->programPackage)
                                · {{ $material->programPackage->package_name }}
                            @endif

                        </div>

                    </div>

                </div>


                @if ($material->material_file_path)

                    <a
                        href="{{ asset('storage/' . $material->material_file_path) }}"
                        target="_blank"
                        class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition"
                    >
                        Buka Materi →
                    </a>

                @else

                    <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-gray-100 text-gray-500">
                        File belum tersedia
                    </span>

                @endif

            </div>

        @endforeach

    @endif

</div>


<div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500 bg-white">

    📚 Materi pembelajaran disesuaikan dengan program dan kelas yang kamu ikuti.

</div>

@endsection