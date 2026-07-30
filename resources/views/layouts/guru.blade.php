<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title','Haoyou Educator')</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>

/* =====================================================
                    RESET
===================================================== */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,
body{

    width:100%;

    min-height:100%;

    background:#f7f8fa;

    font-family:'Poppins',sans-serif;

    color:#333;

}

body{

    overflow-x:hidden;

}

/* =====================================================
                    WRAPPER
===================================================== */

.wrapper{

    display:flex;

    min-height:100vh;

    background:#f7f8fa;

}

/* =====================================================
                    SIDEBAR
===================================================== */

.sidebar{

    width:240px;

    min-height:100vh;

    position:fixed;

    left:0;

    top:0;

    bottom:0;

    z-index:100;

    background:#FFFFFF;

    border-right:1px solid #ECECEC;

    overflow-y:auto;

}

/* =====================================================
                    LOGO
===================================================== */

.logo{

    text-align:center;

    padding:30px 20px;

    border-bottom:1px solid #f3f3f3;

}

.logo img{

    width:72px;

    margin-bottom:10px;

}

.logo h5{

    color:#A9822C;

    font-weight:700;

    letter-spacing:2px;

    margin:0;

}

/* =====================================================
                    MENU
===================================================== */

.menu{

    padding:18px 10px;

}

.menu a{

    display:flex;

    align-items:center;

    gap:12px;

    text-decoration:none;

    color:#555;

    padding:13px 16px;

    margin-bottom:8px;

    border-radius:12px;

    transition:.25s;

    font-size:14px;

    font-weight:500;

}

.menu a i{

    width:22px;

    text-align:center;

}

.menu a:hover{

    background:#FFF8CC;

    color:#A9822C;

}

.menu a.active{

    background:#FFDD00;

    color:#A9822C;

    font-weight:600;

}

/* =====================================================
                    CONTENT
===================================================== */

.content{

    margin-left:240px;

    width:calc(100% - 240px);

    min-height:100vh;

    background:#f7f8fa;

    padding:35px;

}

/* =====================================================
                    TYPOGRAPHY
===================================================== */

h1,h2,h3,h4,h5,h6{

    font-weight:600;

    color:#222;

}

p{

    color:#6B7280;

}

/* =====================================================
                    CARD
===================================================== */

.card{

    background:#FFFFFF;

    border:none;

    border-radius:16px;

    box-shadow:0 4px 15px rgba(0,0,0,.06);

    margin-bottom:25px;

}

/* =====================================================
                    SMALL BOX
===================================================== */

.small-box{

    background:#FFFFFF !important;

    border:none;

    border-radius:16px;

    box-shadow:0 4px 15px rgba(0,0,0,.06);

}

/* =====================================================
                    BUTTON
===================================================== */

.btn-primary{

    background:#FFDD00;

    border-color:#FFDD00;

    color:#A9822C;

    font-weight:600;

}

.btn-primary:hover{

    background:#F5D300;

    border-color:#F5D300;

    color:#A9822C;

}

.btn-outline-primary{

    border:1px solid #FFDD00;

    color:#A9822C;

}

.btn-outline-primary:hover{

    background:#FFDD00;

    color:#A9822C;

}

/* =====================================================
                    BADGE
===================================================== */

.badge-success{

    background:#6CC070;

    color:#fff;

}

.badge-warning{

    background:#FFDD00;

    color:#A9822C;

}

.badge-danger{

    background:#E95A5A;

    color:#fff;

}

/* =====================================================
                    TABLE
===================================================== */

.table{

    background:#FFFFFF;

}

.table td{

    vertical-align:middle;

}

.table thead{

    background:#fafafa;

}

/* =====================================================
                    SCROLLBAR
===================================================== */

::-webkit-scrollbar{

    width:8px;

}

::-webkit-scrollbar-thumb{

    background:#d8d8d8;

    border-radius:20px;

}

::-webkit-scrollbar-thumb:hover{

    background:#bfbfbf;

}

</style>

</head>
<body>

<div class="wrapper">

    <!-- =====================================
                SIDEBAR
    ====================================== -->

    <div class="sidebar">

        <!-- Logo -->
        <div class="logo">

            <img src="{{ asset('image/Logo-Haoyou.jpeg') }}" alt="Logo">

            <h5>HAOYOU</h5>

        </div>

        <!-- Menu -->
        <div class="menu">

            <a href="{{ url('/guru/dashboard') }}"
               class="{{ request()->is('guru/dashboard') ? 'active' : '' }}">

                <i class="fas fa-home"></i>

                <span>Dashboard</span>

            </a>

            <a href="{{ url('/guru/notifikasi') }}"
               class="{{ request()->is('guru/notifikasi') ? 'active' : '' }}">

                <i class="fas fa-bell"></i>

                <span>Notifikasi</span>

            </a>

            <a href="{{ url('/guru/sop') }}"
               class="{{ request()->is('guru/sop') ? 'active' : '' }}">

                <i class="fas fa-book"></i>

                <span>SOP</span>

            </a>

            <a href="{{ url('/guru/materi') }}"
               class="{{ request()->is('guru/materi') ? 'active' : '' }}">

                <i class="fas fa-folder-open"></i>

                <span>Materi & Silabus</span>

            </a>

            <a href="{{ url('/guru/kelas') }}"
               class="{{ request()->is('guru/kelas') ? 'active' : '' }}">

                <i class="fas fa-chalkboard"></i>

                <span>Kelas</span>

            </a>

            <a href="{{ url('/guru/attendance') }}"
               class="{{ request()->is('guru/attendance') ? 'active' : '' }}">

                <i class="fas fa-user-check"></i>

                <span>Attendance & Journal</span>

            </a>

            <a href="{{ url('/guru/progress-report') }}"
               class="{{ request()->is('guru/progress-report') ? 'active' : '' }}">

                <i class="fas fa-chart-bar"></i>

                <span>Progress Report</span>

            </a>

            <a href="{{ url('/guru/schedule') }}"
               class="{{ request()->is('guru/schedule') ? 'active' : '' }}">

                <i class="fas fa-calendar-alt"></i>

                <span>Schedule</span>

            </a>

            <a href="{{ url('/guru/teaching-log') }}"
               class="{{ request()->is('guru/teaching-log') ? 'active' : '' }}">

                <i class="fas fa-clipboard-list"></i>

                <span>Teaching Log</span>

            </a>

            <a href="{{ url('/guru/cuti') }}"
               class="{{ request()->is('guru/cuti') ? 'active' : '' }}">

                <i class="fas fa-plane-departure"></i>

                <span>Cuti / Ganti Kelas</span>

            </a>

        </div>

    </div>

    <!-- =====================================
                CONTENT
    ====================================== -->

    <div class="content">

        @yield('content')

    </div>

</div>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>