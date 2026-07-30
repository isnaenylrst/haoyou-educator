<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Haoyou Educator — Les Mandarin Game Based Learning')</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Shrikhand&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

{{-- Tailwind via CDN untuk kemudahan development.
     Untuk produksi, ganti dengan build Vite (npm run build) + tailwind.config.js
     yang sudah disiapkan di root project ini. --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ink: '#1c1a14',
          olive: '#5c6b2e',
          oliveDark: '#454f22',
          gold: '#a6862a',
          pale: '#f3e6a0',
          cream: '#efece3',
          paper: '#faf8f2',
          line: '#ddd8c6',
        },
        fontFamily: {
          display: ['Shrikhand', 'cursive'],
          serif2: ['"Cormorant Garamond"', 'serif'],
          sans: ['Poppins', 'sans-serif'],
        }
      }
    }
  }
</script>
<style>
  body { font-family: 'Poppins', sans-serif; background: #efece3; color: #1c1a14; }
  .ring-deco{
    width:60px; height:60px; border:3px solid #1c1a14; border-radius:9999px;
    display:flex; align-items:center; justify-content:center; margin:0 auto;
  }
</style>
@stack('styles')
</head>
<body>
  @yield('content')
</body>
</html>