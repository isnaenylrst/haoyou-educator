{{-- Dipakai di Panduan Vocab & Grammar. Variabel: $selfVideo (model/null), $videoId (fallback lama) --}}
@php $sv = $selfVideo ?? null; @endphp
<div class="fl-video">
  @if($sv && $sv->is_upload)
    <div class="ratio">
      <video controls controlsList="nodownload" preload="metadata" playsinline
             style="position:absolute;inset:0;width:100%;height:100%;background:#000">
        <source src="{{ route('student.self-video.stream', $sv) }}" type="{{ $sv->mime_type ?: 'video/mp4' }}">
        Browser kamu tidak mendukung pemutar video.
      </video>
    </div>
  @elseif($sv && $sv->is_youtube)
    <div class="ratio">
      <iframe src="{{ $sv->embed_url }}" title="{{ $sv->title }}"
              loading="lazy" allowfullscreen
              allow="accelerometer; encrypted-media; picture-in-picture"></iframe>
    </div>
  @elseif(!empty($videoId))
    <div class="ratio">
      <iframe src="https://www.youtube-nocookie.com/embed/{{ $videoId }}" title="Video Tutorial"
              loading="lazy" allowfullscreen
              allow="accelerometer; encrypted-media; picture-in-picture"></iframe>
    </div>
  @else
    <div class="fl-empty">🎬 Video tutorial belum tersedia.</div>
  @endif

  @if($sv && ($sv->title || $sv->description))
    <div style="margin-top:12px">
      <div style="font-weight:700;font-size:14.5px;color:#1d1a0f">{{ $sv->title }}</div>
      @if($sv->description)
        <div style="font-size:12.5px;color:#6b6652;margin-top:2px;line-height:1.5">{{ $sv->description }}</div>
      @endif
    </div>
  @endif
</div>