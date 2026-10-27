{{--
    Bar khusus Owner. Muncul di atas konten area Admin, Kurikulum, dan Guru
    supaya Owner selalu bisa kembali ke dashboard Owner (dan ganti guru di area Guru).
    Tidak tampil untuk role lain.
--}}
@if (auth()->check() && (auth()->user()->level->nama_level ?? null) === 'Owner')

    @php
        $__area = request()->is('guru', 'guru/*')
            ? 'Guru'
            : (request()->is('kurikulum', 'kurikulum/*')
                ? 'Kurikulum'
                : (request()->is('admin', 'admin/*') ? 'Admin' : null));

        $__teachers = $__area === 'Guru'
            ? \App\Models\Teacher::where('status', 'Active')->orderBy('name')->get(['id', 'name'])
            : collect();

        $__current = session('owner_view_teacher_id');
    @endphp

    <style>
        .owner-bar {
            display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px;
            background: #fff7cc; border: 1px solid #f1d96b; color: #5a4a00;
            border-radius: 12px; padding: 10px 16px; margin-bottom: 18px; font-size: 13px;
        }
        [data-theme="dark"] .owner-bar { background: #3a3410; border-color: #6b5a14; color: #f3e29a; }
        .owner-bar .ob-title { font-weight: 700; }
        .owner-bar select {
            border: 1px solid #d9c25a; border-radius: 8px; padding: 5px 10px; font-size: 12.5px;
            background: #fff; color: #222; min-width: 190px;
        }
        .owner-bar .ob-spacer { margin-left: auto; display: flex; gap: 8px; flex-wrap: wrap; }
        .owner-bar .ob-btn {
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none; cursor: pointer;
            border: 1px solid #1d1d27; background: #1d1d27; color: #fff; border-radius: 8px;
            padding: 5px 12px; font-size: 12.5px; font-weight: 600; font-family: inherit;
        }
        .owner-bar .ob-btn.light { background: transparent; color: inherit; border-color: currentColor; }
    </style>

    <div class="owner-bar">
        <span class="ob-title"><i class="fa-solid fa-crown me-1"></i> Mode Owner</span>

        <span>
            Anda membuka area <strong>{{ $__area ?? 'lain' }}</strong> dengan akses penuh.
        </span>

        @if ($__area === 'Guru')
            <form method="POST" action="{{ route('owner.guru.switch') }}" class="d-flex align-items-center gap-2 m-0">
                @csrf
                <label class="mb-0 fw-semibold" for="obTeacher">Sebagai guru:</label>
                <select name="teacher_id" id="obTeacher" onchange="this.form.submit()">
                    @foreach ($__teachers as $__t)
                        <option value="{{ $__t->id }}" @selected((int) $__current === (int) $__t->id)>{{ $__t->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <div class="ob-spacer">
            @if ($__area === 'Guru')
                <a href="{{ route('owner.guru') }}" class="ob-btn light">
                    <i class="fa-solid fa-users"></i> Daftar Guru
                </a>
            @endif

            <a href="{{ route('owner.dashboard') }}" class="ob-btn">
                <i class="fa-solid fa-arrow-left"></i> Dashboard Owner
            </a>
        </div>
    </div>

@endif