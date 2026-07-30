<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Haoyou Educator')
    </title>


    {{-- Google Fonts: Inter & Poppins --}}
    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">


    {{-- Global CSS --}}
    <link rel="stylesheet"
          href="{{ asset('css/app.css') }}">

    @stack('layout-styles')
    {{-- CSS tambahan dari halaman --}}
    @stack('styles')

</head>


<body>

    @yield('body')


    {{-- Global JavaScript --}}
    <script src="{{ asset('js/app.js') }}"></script>


    {{-- JavaScript tambahan dari halaman --}}
    @stack('scripts')

</body>

</html>