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
  
  $firstCulture = $cultureVideos->first(); 
  
  @endphp

  <div class="bg-white border border-line rounded-2xl p-5 mb-5">

    <h3 class="font-semibold mb-1 text-black">Video Pembelajaran</h3>
    <p class="text-xs text-gray-500 mb-5">Video budaya dan replay materi sesuai level kamu.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- ===== VIDEO BUDAYA (per bab) ===== --}}
        <div class="border border-line rounded-2xl p-4">

            @if ($firstCulture)
                <div class="aspect-video rounded-xl overflow-hidden bg-black">
                    <iframe id="cultureFrame"
                            class="w-full h-full"
                            src="{{ $firstCulture->embed_url }}"
                            title="{{ $firstCulture->title }}"
                            loading="lazy"
                            allow="encrypted-media; picture-in-picture; fullscreen"
                            allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>

                <div class="mt-4">
                    <div class="text-sm font-semibold text-black">Video Budaya China</div>
                    <div id="cultureTitle" class="text-xs text-gray-500 mt-1">
                        Bab {{ $firstCulture->chapter_number }} · {{ $firstCulture->title }}
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($cultureVideos as $cv)
                        <button type="button"
                                class="culture-btn text-xs font-semibold border border-ink rounded-full px-3 py-1.5 transition
                                       {{ $loop->first ? 'bg-ink text-white' : 'hover:bg-ink hover:text-white' }}"
                                data-src="{{ $cv->embed_url }}"
                                data-title="Bab {{ $cv->chapter_number }} · {{ $cv->title }}">
                            Bab {{ $cv->chapter_number }}
                        </button>
                    @endforeach
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
                    <iframe class="w-full h-full"
                            src="{{ $replayVideo->embed_url }}"
                            title="{{ $replayVideo->title }}"
                            loading="lazy"
                            allow="encrypted-media; picture-in-picture; fullscreen"
                            allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
            @else
                <div class="w-full aspect-video rounded-xl bg-pale flex flex-col items-center justify-center text-center px-4">
                    <div class="text-3xl mb-2">🔁</div>
                    <p class="text-xs font-medium text-black">Video belum tersedia</p>
                    <p class="text-[11px] text-gray-500 mt-1">Akan muncul setelah diunggah oleh pengajar.</p>
                </div>
            @endif

            <div class="mt-4">
                <div class="text-sm font-semibold text-black">{{ $replayVideo->title ?? 'Video Replay Materi' }}</div>
                <div class="text-xs text-gray-500 mt-1">Rangkuman untuk mengingat kembali materi yang sudah diajarkan.</div>
            </div>

        </div>

    </div>
</div>

<script>
  document.querySelectorAll('.culture-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('cultureFrame').src = btn.dataset.src;
      document.getElementById('cultureTitle').textContent = btn.dataset.title;
      document.querySelectorAll('.culture-btn').forEach(function (b) {
        b.classList.remove('bg-ink', 'text-white');
      });
      btn.classList.add('bg-ink', 'text-white');
    });
  });
</script>
@endsection