@extends('layouts.owner')

@section('title', 'Dashboard Owner')

@push('styles')
<style>
    .sec-title { font-size: 17px; font-weight: 700; margin: 8px 0 14px; }

    .role-card { margin-bottom: 0 !important; overflow: hidden; }
    .role-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px 4px; }
    .role-chip { color: #fff; font-size: 11.5px; font-weight: 700; letter-spacing: .06em; padding: 4px 12px; border-radius: 99px; }
    .role-count { font-size: 12px; color: var(--muted); }
    .role-desc { padding: 0 18px 10px; font-size: 12.5px; color: var(--muted); border-bottom: 1px solid var(--line); }
    .feat { display: flex; align-items: center; gap: 12px; padding: 10px 18px; text-decoration: none; color: var(--text); border-bottom: 1px solid var(--line); }
    .feat:last-child { border-bottom: 0; }
    .feat:hover { background: var(--accent-soft); color: var(--text); }
    .feat-ico { width: 22px; text-align: center; font-size: 15px; flex-shrink: 0; }
    .feat-txt { display: flex; flex-direction: column; min-width: 0; flex: 1; line-height: 1.3; }
    .feat-name { font-size: 13.5px; font-weight: 600; }
    .feat-desc { font-size: 11.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .feat-go { font-size: 10px; color: var(--muted); }
</style>
@endpush

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Dashboard Owner</h2>
        <p class="text-muted mb-0">
            Anda memiliki akses penuh ke semua fitur Admin, Kurikulum, dan Guru. Pilih fitur di bawah atau lewat menu di kiri.
        </p>
    </div>

    @php $roleGroups = collect(\App\Support\OwnerMenu::groups())->reject(fn ($g) => $g['key'] === 'owner'); @endphp

    <div class="sec-title"><i class="fa-solid fa-grip me-2"></i>Semua Fitur per Role</div>

    <div class="row g-3 mb-4">
        @foreach($roleGroups as $g)
            <div class="col-xl-4 col-lg-6">
                <div class="card role-card h-100" style="border-top: 4px solid {{ $g['color'] }}">

                    <div class="role-head">
                        <span class="role-chip" style="background: {{ $g['color'] }}">{{ $g['label'] }}</span>
                        <span class="role-count">{{ count($g['items']) }} fitur</span>
                    </div>
                    <div class="role-desc">{{ $g['desc'] }}</div>

                    <div class="list-group list-group-flush">
                        @foreach($g['items'] as $it)
                            <a href="{{ url($it['url']) }}" class="feat">
                                <span class="feat-ico" style="color: {{ $g['color'] }}"><i class="fa-solid {{ $it['icon'] }}"></i></span>
                                <span class="feat-txt">
                                    <span class="feat-name">{{ $it['label'] }}</span>
                                    <span class="feat-desc">{{ $it['desc'] }}</span>
                                </span>
                                <i class="fa-solid fa-chevron-right feat-go"></i>
                            </a>
                        @endforeach
                    </div>

                </div>
            </div>
        @endforeach
    </div>

</div>

@endsection