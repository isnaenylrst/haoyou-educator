@extends('layouts.app')

@section('title', 'Masuk Portal Siswa — Haoyou Educator')

@section('content')

<div class="hy-page min-h-[80vh] flex items-center justify-center px-5 py-10">

    @include('partials.hy-bg')

    <div class="hy-card w-full max-w-[400px]">

        {{-- LOGO --}}
        <div class="flex justify-center mb-5">
            <img src="{{ asset('assets/img/logo.png') }}"
                 alt="Haoyou Educator"
                 class="w-24 object-contain">
        </div>


        {{-- JUDUL --}}
        <h2 class="text-center text-[22px] font-semibold mb-1.5"
            style="color:#000000">
            Masuk Portal Siswa
        </h2>

        <p class="text-center text-[12.5px] mb-7"
           style="color:#788575">
            Akses diberikan setelah admin memverifikasi
            pendaftaran kamu.
        </p>


        {{-- VALIDATION ERROR --}}
        @if ($errors->any())

            <div class="border text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4"
                 style="background:#F9E8E2;
                        border-color:#EE9F8F;
                        color:#985B4F">

                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach

            </div>

        @endif


        {{-- SESSION ERROR --}}
        @if (session('error'))

            <div class="border text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4"
                 style="background:#F9E8E2;
                        border-color:#EE9F8F;
                        color:#985B4F">

                {{ session('error') }}

            </div>

        @endif


        {{-- SESSION STATUS --}}
        @if (session('status'))

            <div class="border text-[12.5px] px-3.5 py-2.5 rounded-[10px] mb-4"
                 style="background:#E8EDE2;
                        border-color:#B5C2A0;
                        color:#2F4B3B">

                {{ session('status') }}

            </div>

        @endif


        {{-- FORM LOGIN --}}
        <form method="POST" action="{{ route('login.process') }}">

            @csrf

            {{-- USERNAME --}}
            <div class="mb-4">

                <label class="hy-lbl">
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan username"
                    class="hy-in"
                >

            </div>


            {{-- PASSWORD --}}
            <div class="mb-4">

                <label class="hy-lbl">
                    Password
                </label>

                <div class="relative">

                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        class="hy-in"
                        style="padding-right:44px;"
                    >

                    {{-- TOGGLE PASSWORD --}}
                    <button
                        type="button"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 transition"
                        style="color:#788575">

                        {{-- EYE OPEN --}}
                        <svg id="eyeOpen"
                             width="20"
                             height="20"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             stroke-linecap="round"
                             stroke-linejoin="round">

                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>

                            <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>

                        </svg>


                        {{-- EYE CLOSED --}}
                        <svg id="eyeClosed"
                             class="hidden"
                             width="20"
                             height="20"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             stroke-linecap="round"
                             stroke-linejoin="round">

                            <path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/>

                            <path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87"/>

                            <path d="M3 3l18 18"/>

                        </svg>

                    </button>

                </div>

            </div>


            {{-- REMEMBER ME --}}
            <label class="flex items-center gap-2 text-[12px] mb-4 cursor-pointer"
                   style="color:#788575">

                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="accent-[#2F4B3B]">

                Ingat saya

            </label>


            {{-- BUTTON LOGIN --}}
            <button type="submit" class="hy-btn">
                Masuk Dashboard
            </button>

        </form>


        {{-- REGISTER LINK --}}
        <p class="text-center text-xs mt-5"
           style="color:#788575">

            Belum punya akun?

            <a href="{{ route('pendaftaran.create') }}"
               class="hy-link">

                Daftar sebagai calon siswa

            </a>

        </p>

    </div>

</div>


{{-- =====================================
     TOGGLE PASSWORD SCRIPT
====================================== --}}

<script>
    document.getElementById('togglePassword')
        .addEventListener('click', function () {

            const input = document.getElementById('password');

            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';

            document.getElementById('eyeOpen')
                .classList.toggle('hidden', show);

            document.getElementById('eyeClosed')
                .classList.toggle('hidden', !show);

            this.setAttribute(
                'aria-label',
                show ? 'Sembunyikan password' : 'Tampilkan password'
            );

        });
</script>

@endsection
