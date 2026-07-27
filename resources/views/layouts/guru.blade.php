<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','Haoyou Educator')</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        html,
        body{
            width:100%;
            height:100%;
            background:#FFFFFF;
            font-family:'Segoe UI',sans-serif;
        }

        .wrapper{
            display:flex;
            min-height:100vh;
            background:#FFFFFF;
        }

        /* =========================
                SIDEBAR
        ========================= */

        .sidebar{
            width:240px;
            min-height:100vh;
            background:#FFFFFF;
            border-right:1px solid #E6E6E0;
            position:fixed;
            left:0;
            top:0;
            bottom:0;
            z-index:100;
        }

        /* =========================
                LOGO
        ========================= */

        .logo{
            text-align:center;
            padding:30px 20px;
        }

        .logo img{
            width:70px;
            margin-bottom:10px;
        }

        .logo h5{
            color:#A9822C;
            font-weight:bold;
            letter-spacing:2px;
        }

        /* =========================
                MENU
        ========================= */

        .menu{
            margin-top:20px;
        }

        .menu a{
            display:block;
            text-decoration:none;
            color:#444;
            padding:14px 25px;
            margin:6px 10px;
            border-radius:10px;
            transition:.3s;
            font-size:15px;
        }

        .menu a:hover{
            background:#FFF6BF;
            color:#A9822C;
        }

        .menu a.active{
            background:#FFDD00;
            color:#A9822C;
            font-weight:700;
        }

        /* =========================
                CONTENT
        ========================= */

        .content{
            margin-left:240px;
            width:calc(100% - 240px);
            min-height:100vh;
            background:#FFFFFF;
            padding:35px;
        }

        /* =========================
                CARD
        ========================= */

        .card{
            background:#FFFFFF;
            border:none;
            border-radius:16px;
            box-shadow:0 3px 12px rgba(0,0,0,.08);
            margin-bottom:25px;
        }

        /* =========================
            SMALL BOX
        ========================= */

        .small-box{
            background:#FFFFFF !important;
            border:none;
            border-radius:15px;
            box-shadow:0 3px 10px rgba(0,0,0,.08);
        }

        /* =========================
                BUTTON
        ========================= */

        .btn-primary{
            background:#FFDD00;
            border-color:#FFDD00;
            color:#A9822C;
            font-weight:600;
        }

        .btn-primary:hover{
            background:#FFD000;
            border-color:#FFD000;
            color:#A9822C;
        }

        .btn-outline-primary{
            border-color:#FFDD00;
            color:#A9822C;
        }

        .btn-outline-primary:hover{
            background:#FFDD00;
            color:#A9822C;
        }

        /* =========================
                BADGE
        ========================= */

        .badge-success{
            background:#6CC070;
            color:white;
        }

        .badge-warning{
            background:#FFDD00;
            color:#A9822C;
        }

        .badge-danger{
            background:#E95A5A;
            color:white;
        }

        /* =========================
                TABLE
        ========================= */

        .table td{
            vertical-align:middle;
        }

    </style>

</head>

<body>

<div class="wrapper">

    <!-- Sidebar -->

    <div class="sidebar">

        <div class="logo">

            <img src="{{ asset('image/Logo-Haoyou.jpeg') }}" alt="Logo">

            <h5>HAOYOU</h5>

        </div>

        <div class="menu">

            <a href="{{ url('/guru/dashboard') }}"
               class="{{ request()->is('guru/dashboard') ? 'active' : '' }}">
                <i class="fas fa-home me-2"></i>
                Dashboard
            </a>

            <a href="{{ url('/guru/notifikasi') }}"
               class="{{ request()->is('guru/notifikasi') ? 'active' : '' }}">
                <i class="fas fa-bell me-2"></i>
                Notifikasi
            </a>

            <a href="{{ url('/guru/sop') }}"
               class="{{ request()->is('guru/sop') ? 'active' : '' }}">
                <i class="fas fa-book me-2"></i>
                SOP
            </a>

            <a href="{{ url('/guru/materi') }}"
               class="{{ request()->is('guru/materi') ? 'active' : '' }}">
                <i class="fas fa-folder-open me-2"></i>
                Materi & Silabus
            </a>

            <a href="{{ url('/guru/kelas') }}"
               class="{{ request()->is('guru/kelas') ? 'active' : '' }}">
                <i class="fas fa-chalkboard me-2"></i>
                Kelas
            </a>

            <a href="{{ url('/guru/attendance') }}"
               class="{{ request()->is('guru/attendance') ? 'active' : '' }}">
                <i class="fas fa-user-check me-2"></i>
                Attendance & Journal
            </a>

            <a href="{{ url('/guru/progress-report') }}"
               class="{{ request()->is('guru/progress-report') ? 'active' : '' }}">
                <i class="fas fa-chart-bar me-2"></i>
                Progress Report
            </a>

            <a href="{{ url('/guru/schedule') }}"
               class="{{ request()->is('guru/schedule') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt me-2"></i>
                Schedule
            </a>

            <a href="{{ url('/guru/teaching-log') }}"
               class="{{ request()->is('guru/teaching-log') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list me-2"></i>
                Teaching Log
            </a>

            <a href="{{ url('/guru/cuti') }}"
               class="{{ request()->is('guru/cuti') ? 'active' : '' }}">
                <i class="fas fa-plane-departure me-2"></i>
                Cuti / Ganti Kelas
            </a>

        </div>

    </div>

    <!-- Content -->

    <div class="content">

        @yield('content')

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>