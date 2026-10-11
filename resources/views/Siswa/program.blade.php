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

                            @if ($material->level)
                                · {{ $material->level->program?->program_name }}
                                {{ $material->level->level_name }}
                            @endif

                            @if ($material->vocabs->isNotEmpty())
                                · {{ $material->vocabs->count() }} kosakata
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

{{-- ===================== VIDEO PEMBELAJARAN ===================== --}}
@php
    $isKids = ($audience ?? 'hsk') === 'kids';

    $videoSlots = [
        [
            'label' => $isKids ? 'Video Budaya China (Animasi)' : 'Video Budaya China',
            'desc'  => $isKids
                ? 'Video animasi budaya China yang seru, bertambah seiring kamu belajar.'
                : 'Video jangka panjang yang bertambah sepanjang periode les kamu.',
            'icon'  => $isKids ? '🐼' : '🏮',
            'video' => $cultureVideo ?? null,
        ],
        [
            'label' => 'Video Replay Materi',
            'desc'  => 'Rangkuman untuk mengingat kembali materi yang sudah diajarkan.',
            'icon'  => '🔁',
            'video' => $replayVideo ?? null,
        ],
    ];
@endphp

<div class="bg-white border border-line rounded-2xl p-5 mb-5">

    <h3 class="font-semibold mb-1 text-black">Video Pembelajaran</h3>
    <p class="text-xs text-gray-500 mb-5">Video hanya bisa ditonton, tidak dapat diunduh.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        @foreach ($videoSlots as $slot)
            @php $video = $slot['video']; @endphp

            <div class="border border-line rounded-2xl p-4">

                @if ($video && $video->video_path)
                    <video
                        class="w-full aspect-video rounded-xl bg-black"
                        controls
                        preload="metadata"
                        controlsList="nodownload noremoteplayback"
                        disablePictureInPicture
                        oncontextmenu="return false;"
                    >
                        <source src="{{ route('program.video.stream', $video->id) }}" type="video/mp4">
                        Browser kamu tidak mendukung pemutar video.
                    </video>
                @else
                    <div class="w-full aspect-video rounded-xl bg-pale flex flex-col items-center justify-center text-center px-4">
                        <div class="text-3xl mb-2">{{ $slot['icon'] }}</div>
                        <p class="text-xs font-medium text-black">Video belum tersedia</p>
                        <p class="text-[11px] text-gray-500 mt-1">Akan muncul setelah diunggah oleh pengajar.</p>
                    </div>
                @endif

                <div class="mt-4">
                    <div class="text-sm font-semibold text-black">{{ $video->title ?? $slot['label'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $slot['desc'] }}</div>

                    @if ($video && $video->duration_minutes)
                        <div class="text-[11px] text-gray-400 mt-2">{{ $video->duration_minutes }} menit</div>
                    @endif
                </div>

            </div>
        @endforeach

    </div>
</div>

@endsection