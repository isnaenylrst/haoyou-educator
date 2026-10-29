{{-- Maskot bintang (pengganti panda). Pakai: @include('student.vocab._star', ['size' => 52]) --}}
<svg viewBox="0 0 64 64" style="width:{{ $size ?? 52 }}px;height:{{ $size ?? 52 }}px;flex-shrink:0" aria-hidden="true">
  <path d="M32 5l7.6 17.2 18.7 1.8-14.1 12.4 4.1 18.3L32 44.9 15.7 54.7l4.1-18.3L5.7 24l18.7-1.8z" fill="#FFD60A" stroke="#E0A62F" stroke-width="2.5" stroke-linejoin="round"/>
  <circle cx="25.5" cy="30" r="2.4" fill="#2C2C2A"/><circle cx="38.5" cy="30" r="2.4" fill="#2C2C2A"/>
  <path d="M27 36.5c2.8 2.6 7.2 2.6 10 0" stroke="#2C2C2A" stroke-width="2" stroke-linecap="round" fill="none"/>
</svg>
