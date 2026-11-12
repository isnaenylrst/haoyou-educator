@extends('layouts.dashboard')

@section('title', 'Video Pembelajaran — Haoyou')

@section('content')
<div class="p-4 md:p-8 max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-[#1d1a0f] flex items-center gap-2">
            <span>🎬</span> Video Pembelajaran
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            Tonton video materi dan pembahasan Mandarin yang dikelompokkan sesuai kategori program belajar Anda.
        </p>
    </div>

    @if(!isset($groupedVideos) || $groupedVideos->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center border border-gray-100 shadow-sm">
            <div class="text-5xl mb-3">🎬</div>
            <h3 class="text-lg font-bold text-gray-700">Belum Ada Video Pembelajaran</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto mt-1">
                Video pembelajaran untuk program kelas Anda akan otomatis muncul di sini saat dipublikasikan oleh kurikulum.
            </p>
        </div>
    @else
        @foreach($groupedVideos as $categoryName => $vList)
            <div class="mb-10">
                <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-2">
                    <span class="bg-[#FFEFA0] text-[#5c4500] text-sm font-bold px-3 py-1 rounded-full">
                        {{ $categoryName }}
                    </span>
                    <span class="text-xs text-gray-400 font-medium">({{ $vList->count() }} video)</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    @foreach($vList as $v)
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col hover:shadow-md transition">
                            <div class="relative bg-gray-900 h-44 w-full overflow-hidden flex items-center justify-center">
                                <img src="{{ $v->thumbnail_url }}" alt="{{ $v->title }}" class="w-100 h-100 object-cover" onerror="this.src='{{ asset('assets/img/video-placeholder.png') }}'">
                                <span class="absolute bottom-2 right-2 bg-black/75 text-white text-[11px] px-2 py-0.5 rounded font-medium">
                                    @if($v->is_youtube)
                                        <span class="text-red-500 font-bold">YouTube</span>
                                    @else
                                        <span>Video</span>
                                    @endif
                                </span>
                            </div>
                            <div class="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center gap-1.5 mb-2">
                                        @if($v->level)
                                            <span class="text-[11px] font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                                Level {{ $v->level->level_name }}
                                            </span>
                                        @else
                                            <span class="text-[11px] font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                                Semua Level
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-sm leading-snug line-clamp-2 mb-1">{{ $v->title }}</h4>
                                    <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ $v->description ?: 'Tidak ada deskripsi.' }}</p>
                                </div>
                                <div class="pt-3 border-t border-gray-100">
                                    @if($v->is_youtube)
                                        <a href="{{ $v->watch_url }}" target="_blank" class="w-full inline-flex items-center justify-center gap-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold py-2 px-3 rounded-xl transition">
                                            <span>▶</span> Tonton di YouTube
                                        </a>
                                    @else
                                        <a href="{{ route('video.stream', $v->id) }}" target="_blank" class="w-full inline-flex items-center justify-center gap-1.5 bg-[#FFD60A] hover:bg-[#FFE34D] text-[#3a2e00] text-xs font-semibold py-2 px-3 rounded-xl transition">
                                            <span>▶</span> Putar Video
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

