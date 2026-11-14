@extends('layouts.dashboard')

@section('dashboard-content')

{{-- ===================== MATERI PEMBELAJARAN ===================== --}}
@php
    // Kelompokkan materi per level (program + nama level)
    $groupedMaterials = $materials->groupBy(function ($m) {
        if ($m->level) {
            return trim(($m->level->program?->program_name ?? '') . ' ' . $m->level->level_name);
        }
        return 'Lainnya';
    });
@endphp

<div class="bg-white border border-line rounded-2xl p-5 mb-5">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
        <div>
            <h3 class="font-semibold mb-1 text-black">
                Materi Pembelajaran
            </h3>
            <p class="text-xs text-gray-500">
                Materi sesuai dengan program belajar kamu.
            </p>
        </div>

        @unless ($materials->isEmpty())
            <input
                type="text"
                id="materialSearch"
                placeholder="Cari pertemuan / materi..."
                class="text-xs border border-line rounded-full px-4 py-2 w-full md:w-64 focus:outline-none focus:border-ink"
            >
        @endunless
    </div>

    @if ($materials->isEmpty())

        <div class="text-center py-10">
            <div class="text-4xl mb-3">📚</div>
            <p class="text-sm font-medium text-black">
                Belum ada materi pembelajaran
            </p>
            <p class="text-xs text-gray-500 mt-1">
                Materi akan muncul setelah kamu terdaftar pada kelas.
            </p>
        </div>

    @else

        <div id="materialGroups" class="space-y-3">

            @foreach ($groupedMaterials as $levelName => $items)

                <div class="material-group border border-line rounded-xl overflow-hidden">

                    {{-- Header accordion --}}
                    <button
                        type="button"
                        class="group-toggle w-full flex items-center justify-between px-4 py-3  bg-[#d5dfcc] text-left"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-black">{{ $levelName }}</span>
                            <span class="text-[11px] text-gray-500">
                                · {{ $items->count() }} pertemuan
                            </span>
                        </div>
                        <span class="group-arrow text-xs transition-transform {{ $loop->first ? 'rotate-180' : '' }}">▼</span>
                    </button>

                    {{-- Isi accordion --}}
                    <div class="group-body px-4 {{ $loop->first ? '' : 'hidden' }}">

                        @foreach ($items->sortBy('meeting_number') as $material)

                            <div
                                class="material-item flex items-center justify-between gap-3 py-4 border-b border-line last:border-0"
                                data-search="{{ strtolower($material->title . ' pertemuan ' . $material->meeting_number) }}"
                            >

                                <div class="flex items-center gap-3">

                                    <div class="w-11 h-11 rounded-xl bg-[#ffe135] flex items-center justify-center text-lg shrink-0">
                                        📖
                                    </div>

                                    <div>
                                        <div class="text-sm font-medium text-black">
                                            {{ $material->title }}
                                        </div>

                                        <div class="text-xs text-gray-500 mt-1">
                                            Pertemuan {{ $material->meeting_number ?? '-' }}

                                            @if ($material->program)
                                                · {{ $material->program->category_name }}
                                            @endif

                                            @if ($material->level)
                                                · {{ $material->level->level_name }}
                                            @endif

                                            @if ($material->vocabs->isNotEmpty())
                                                · {{ $material->vocabs->count() }} kosakata
                                            @endif
                                        </div>

                                        @if ($material->description)
                                            <div class="text-xs text-gray-600 mt-1">
                                                {{ \Illuminate\Support\Str::limit($material->description, 140) }}
                                            </div>
                                        @endif
                                    </div>

                                </div>

                                @if ($material->file_exists)

                                    <div class="flex items-center gap-2">
                                        @if ($material->is_pdf)
                                            <a href="{{ route('program.materi.lihat', $material) }}" target="_blank"
                                               class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition whitespace-nowrap">
                                                Buka Materi →
                                            </a>
                                        @endif
                                        <a href="{{ route('program.materi.unduh', $material) }}"
                                           class="text-xs font-semibold border border-ink rounded-full px-4 py-2 hover:bg-ink hover:text-white transition whitespace-nowrap">
                                            Unduh
                                        </a>
                                    </div>

                                @else

                                    <span class="text-[11px] font-semibold px-3 py-1 rounded-full bg-gray-100 text-gray-500 whitespace-nowrap">
                                        File belum tersedia
                                    </span>

                                @endif

                            </div>

                        @endforeach

                    </div>

                </div>

            @endforeach

        </div>

        <p id="materialEmpty" class="hidden text-center text-xs text-gray-500 py-6">
            Materi tidak ditemukan.
        </p>

    @endif

</div>


<div class="border border-dashed border-line rounded-xl p-4 text-xs text-gray-500 bg-white mb-5">
    📚 Materi pembelajaran disesuaikan dengan program dan kelas yang kamu ikuti.
</div>


{{-- ===================== VIDEO PEMBELAJARAN ===================== --}}
@php
    // Urut dari yang paling lama supaya Bab 1, Bab 2, ... berurutan
    $cultureList = $learningVideos->sortBy('id')->values();
    $firstCulture = $cultureList->first();
@endphp

<div class="bg-white border border-line rounded-2xl p-5 mb-5">

    <h3 class="font-semibold mb-1 text-black">Video Pembelajaran</h3>
    <p class="text-xs text-gray-500 mb-5">Video budaya dan replay materi sesuai level kamu.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- ===== VIDEO BUDAYA (per bab, dropdown) ===== --}}
        <div class="border border-line rounded-2xl p-4">

            @if ($firstCulture)

                <div class="aspect-video rounded-xl overflow-hidden bg-black">
         <iframe id="cultureFrame"
            class="w-full h-full {{ $firstCulture->is_youtube ? '' : 'hidden' }}"
            src="{{ $firstCulture->is_youtube ? $firstCulture->embed_url : 'about:blank' }}"
            title="{{ $firstCulture->title }}"
            allow="encrypted-media; picture-in-picture; fullscreen"
            allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"></iframe>
        <video id="cultureFile"
           class="w-full h-full {{ $firstCulture->is_youtube ? 'hidden' : '' }}"
           controls preload="metadata"
           @unless ($firstCulture->is_youtube) src="{{ route('video.stream', $firstCulture) }}" @endunless></video>
        </div>

                <div class="mt-4">
                    <div class="text-sm font-semibold text-black">Video Budaya China</div>
                    <div id="cultureTitle" class="text-xs text-gray-500 mt-1">
                        {{ $firstCulture->title }}
                        @if ($firstCulture->level) · {{ $firstCulture->level->level_name }} @endif
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2">

                    <button type="button" id="culturePrev"
                            class="text-xs font-semibold border border-ink rounded-full px-3 py-2 hover:bg-ink hover:text-white transition disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-inherit">
                        ←
                    </button>

                    <select id="cultureSelect"
                            class="flex-1 text-xs font-semibold border border-ink rounded-full px-4 py-2 bg-white focus:outline-none cursor-pointer">
                        @foreach ($cultureList as $cv)
                            <option value="{{ $cv->is_youtube ? $cv->embed_url : route('video.stream', $cv) }}"
                                    data-type="{{ $cv->is_youtube ? 'youtube' : 'file' }}"
                                    data-title="{{ $cv->title }}{{ $cv->level ? ' · ' . $cv->level->level_name : '' }}">
                                {{ $cv->title }}{{ $cv->level ? ' — ' . $cv->level->level_name : '' }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" id="cultureNext"
                            class="text-xs font-semibold border border-ink rounded-full px-3 py-2 hover:bg-ink hover:text-white transition disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-inherit">
                        →
                    </button>

                </div>

            @else

                <div class="w-full aspect-video rounded-xl bg-pale flex flex-col items-center justify-center text-center px-4">
                    <div class="text-3xl mb-2">🏮</div>
                    <p class="text-xs font-medium text-black">Video budaya belum tersedia</p>
                    <p class="text-[11px] text-gray-500 mt-1">Akan muncul setelah diunggah oleh kurikulum.</p>
                </div>
                <div class="mt-4">
                    <div class="text-sm font-semibold text-black">Video Budaya China</div>
                    <div class="text-xs text-gray-500 mt-1">Video budaya per bab sesuai level kamu.</div>
                </div>

            @endif

        </div>

        {{-- ===== VIDEO REPLAY ===== --}}
        <div class="border border-line rounded-2xl p-4">

    @if ($replayVideo)
        <div class="aspect-video rounded-xl overflow-hidden bg-black">
            @if ($replayVideo->is_youtube)
                <iframe class="w-full h-full"
                        src="{{ $replayVideo->embed_url }}"
                        title="{{ $replayVideo->title }}"
                        loading="lazy"
                        allow="encrypted-media; picture-in-picture; fullscreen"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"></iframe>
            @else
                <video class="w-full h-full" controls preload="metadata"
                       src="{{ route('video.stream', $replayVideo) }}"></video>
            @endif
        </div>
    @else
        <div class="w-full aspect-video rounded-xl bg-pale flex flex-col items-center justify-center text-center px-4">
            <div class="text-3xl mb-2">🔁</div>
            <p class="text-xs font-medium text-black">Video belum tersedia</p>
            <p class="text-[11px] text-gray-500 mt-1">Akan muncul setelah diunggah oleh kurikulum.</p>
        </div>
    @endif

    <div class="mt-4">
        <div class="text-sm font-semibold text-black">{{ $replayVideo->title ?? 'Video Replay Materi' }}</div>
        <div class="text-xs text-gray-500 mt-1">Rangkuman untuk mengingat kembali materi yang sudah diajarkan.</div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ================= ACCORDION MATERI ================= */
    document.querySelectorAll('.group-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var group = btn.closest('.material-group');
            group.querySelector('.group-body').classList.toggle('hidden');
            group.querySelector('.group-arrow').classList.toggle('rotate-180');
        });
    });

    /* ================= PENCARIAN MATERI ================= */
    var searchInput = document.getElementById('materialSearch');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var keyword = searchInput.value.trim().toLowerCase();
            var totalVisible = 0;

            document.querySelectorAll('.material-group').forEach(function (group) {
                var visibleInGroup = 0;

                group.querySelectorAll('.material-item').forEach(function (item) {
                    var match = item.dataset.search.indexOf(keyword) !== -1;
                    item.classList.toggle('hidden', !match);
                    if (match) visibleInGroup++;
                });

                // Sembunyikan grup kosong; buka grup yang punya hasil saat mencari
                group.classList.toggle('hidden', visibleInGroup === 0);

                if (keyword !== '' && visibleInGroup > 0) {
                    group.querySelector('.group-body').classList.remove('hidden');
                    group.querySelector('.group-arrow').classList.add('rotate-180');
                }

                totalVisible += visibleInGroup;
            });

            document.getElementById('materialEmpty').classList.toggle('hidden', totalVisible > 0);
        });
    }

    /* ================= DROPDOWN VIDEO BUDAYA ================= */
    var select = document.getElementById('cultureSelect');

    if (select) {
        var frame = document.getElementById('cultureFrame');
        var fileVideo = document.getElementById('cultureFile');
        var title = document.getElementById('cultureTitle');
        var prev  = document.getElementById('culturePrev');
        var next  = document.getElementById('cultureNext');

        function updateCulture() {
            var opt = select.options[select.selectedIndex];

           if (opt.dataset.type === 'youtube') {
                fileVideo.pause();
                fileVideo.removeAttribute('src');
                fileVideo.classList.add('hidden');
                frame.classList.remove('hidden');
                frame.src = opt.value;
            } else {
                frame.src = 'about:blank';
                frame.classList.add('hidden');
                fileVideo.classList.remove('hidden');
                fileVideo.src = opt.value;
                fileVideo.load();
            }

            title.textContent = opt.dataset.title;
            prev.disabled = select.selectedIndex === 0;
            next.disabled = select.selectedIndex === select.options.length - 1;
        }

        select.addEventListener('change', updateCulture);

        prev.addEventListener('click', function () {
            if (select.selectedIndex > 0) {
                select.selectedIndex--;
                updateCulture();
            }
        });

        next.addEventListener('click', function () {
            if (select.selectedIndex < select.options.length - 1) {
                select.selectedIndex++;
                updateCulture();
            }
        });
        
    }

        // Set kondisi awal tombol prev/next (tanpa reload iframe)
        prev.disabled = true;
        next.disabled = select.options.length <= 1;
    }

);
</script>

@endsection