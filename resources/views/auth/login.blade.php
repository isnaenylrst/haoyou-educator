<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login | Haoyou Educator
    </title>

    <link
        rel="stylesheet"
        href="{{ asset('css/auth/login.css') }}"
    >

</head>


<body>

<div class="login-container">

    <div class="login-card">

        <div class="login-header">

            <h1>
                Haoyou Educator
            </h1>

            <p>
                Silakan masuk ke akun Anda
            </p>

        </div>


        {{-- Error --}}

        @if (session('error'))

            <div class="alert alert-error">

                {{ session('error') }}

            </div>

        @endif


        {{-- Validation Error --}}

        @if ($errors->any())

            <div class="alert alert-error">

                {{ $errors->first() }}

            </div>

        @endif


        <form
            action="{{ route('login.process') }}"
            method="POST"
        >

            @csrf


            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username') }}"
                    placeholder="Masukkan username"
                    required
                    autofocus
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                >

            </div>


            <div class="remember">

                <label>

                    <input
                        type="checkbox"
                        name="remember"
                    >

                    Ingat saya

                </label>

            </div>


            <button
                type="submit"
                class="login-button"
            >

                Login

            </button>

        </form>

    </div>

</div>

</body>

</html>