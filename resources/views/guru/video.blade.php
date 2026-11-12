@extends('layouts.guru')

@section('title', 'Video Pembelajaran')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-circle-play text-warning me-2"></i> Video Pembelajaran</h4>
            <p class="text-muted small mb-0">Tonton video kurikulum dan materi sesuai dengan program & kelas yang Anda ampu.</p>
        </div>
    </div>

    @if($groupedVideos->isEmpty())
        <div class="card border-0 shadow-sm rounded-4 text-center p-5">
            <i class="fa-solid fa-film fs-1 text-muted mb-3"></i>
            <h5 class="fw-bold text-secondary">Belum ada video pembelajaran untuk kelas Anda</h5>
            <p class="text-muted small">Video dari Kurikulum yang relevan dengan kelas Anda akan otomatis muncul di halaman ini.</p>
        </div>
    @else
        @foreach($groupedVideos as $categoryName => $videos)
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6">{{ $categoryName }}</span>
                    <span class="text-muted small">({{ $videos->count() }} video)</span>
                </div>

                <div class="row g-4">
                    @foreach($videos as $video)
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden hover-shadow transition">
                                <div class="position-relative bg-dark" style="height: 180px;">
                                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="w-100 h-100 object-fit-cover" onerror="this.src='{{ asset('assets/img/video-placeholder.png') }}'">
                                    <span class="position-absolute bottom-0 end-0 bg-dark text-white px-2 py-1 m-2 rounded small" style="opacity: 0.85;">
                                        @if($video->is_youtube)
                                            <i class="fa-brands fa-youtube text-danger me-1"></i> YouTube
                                        @else
                                            <i class="fa-solid fa-file-video text-warning me-1"></i> Video File
                                        @endif
                                    </span>
                                </div>
                                <div class="card-body p-3 d-flex flex-col flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex gap-1 mb-2">
                                            @if($video->level)
                                                <span class="badge bg-light text-dark border">{{ $video->level->level_name }}</span>
                                            @else
                                                <span class="badge bg-light text-muted border">Semua Level</span>
                                            @endif
                                        </div>
                                        <h6 class="fw-bold mb-1 text-dark" style="line-height: 1.3;">{{ $video->title }}</h6>
                                        <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $video->description ?: 'Tidak ada deskripsi.' }}
                                        </p>
                                    </div>
                                    <div class="pt-2 border-top">
                                        @if($video->is_youtube)
                                            <a href="{{ $video->watch_url }}" target="_blank" class="btn btn-danger btn-sm w-100 rounded-pill fw-semibold">
                                                <i class="fa-brands fa-youtube me-1"></i> Tonton di YouTube
                                            </a>
                                        @else
                                            <a href="{{ route('teacher.video.stream', $video->id) }}" target="_blank" class="btn btn-warning btn-sm w-100 rounded-pill fw-semibold text-dark">
                                                <i class="fa-solid fa-play me-1"></i> Putar Video
                                            </a>
                                        @endif
                                    </div>
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

