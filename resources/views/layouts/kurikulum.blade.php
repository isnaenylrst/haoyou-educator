<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Haoyou Educator</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>

<style>

/* ==========================================
        GLOBAL
========================================== */

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
    color:#2c2c2c;
}

body{
    overflow-x:hidden;
}

.wrapper{
    display:flex;
    min-height:100vh;
    background:#f7f8fa;
}

/* ==========================================
        SIDEBAR
========================================== */

.sidebar{

    width:240px;
    min-height:100vh;

    background:#ffffff;

    border-right:1px solid #ececec;

    position:fixed;

    left:0;
    top:0;
    bottom:0;

    z-index:100;

}

/* ==========================================
        LOGO
========================================== */

.logo{

    text-align:center;

    padding:30px 20px;

    border-bottom:1px solid #f1f1f1;

}

.logo img{

    width:70px;

    margin-bottom:10px;

}

.logo h5{

    color:#A9822C;

    font-weight:700;

    letter-spacing:2px;

    margin:0;

}

/* ==========================================
        MENU
========================================== */

.menu{

    margin-top:20px;

}

.menu a{

    display:block;

    text-decoration:none;

    color:#555;

    padding:13px 18px;

    margin:8px 12px;

    border-radius:12px;

    transition:.25s;

    font-size:14px;

    font-weight:500;

}

.menu a i{

    width:22px;

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

/* ==========================================
        CONTENT
========================================== */

.content{

    margin-left:240px;

    width:calc(100% - 240px);

    min-height:100vh;

    background:#f7f8fa;

    padding:35px;

}

/* ==========================================
        TYPOGRAPHY
========================================== */

h1,h2,h3,h4,h5,h6{

    font-weight:600;

    color:#222;

}

p{

    color:#6b7280;

}

/* ==========================================
        CARD
========================================== */

.card{

    background:#ffffff;

    border:none;

    border-radius:16px;

    box-shadow:0 4px 15px rgba(0,0,0,.06);

    margin-bottom:25px;

}

/* ==========================================
        SMALL BOX
========================================== */

.small-box{

    background:#ffffff !important;

    border:none;

    border-radius:16px;

    box-shadow:0 3px 12px rgba(0,0,0,.06);

}

/* ==========================================
        BUTTON
========================================== */

.btn-primary{

    background:#FFDD00;

    border-color:#FFDD00;

    color:#A9822C;

    font-weight:600;

}

.btn-primary:hover{

    background:#f5d300;

    border-color:#f5d300;

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

/* ==========================================
        BADGE
========================================== */

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

/* ==========================================
        TABLE
========================================== */

.table{

    background:white;

}

.table td{

    vertical-align:middle;

}

.table thead{

    background:#fafafa;

}

/* ==========================================
        SCROLLBAR
========================================== */

::-webkit-scrollbar{

    width:8px;

}

::-webkit-scrollbar-thumb{

    background:#d9d9d9;

    border-radius:20px;

}

::-webkit-scrollbar-thumb:hover{

    background:#bfbfbf;

}

</style>

</head>

<body>

<div class="wrapper">

    <!-- =========================
            SIDEBAR
    ========================== -->

    <div class="sidebar">

        <div class="logo">

            <img src="{{ asset('image/Logo-Haoyou.jpeg') }}" alt="Logo">

            <h5>HAOYOU</h5>

        </div>

        <div class="menu">

            <a href="{{ url('/kurikulum/dashboard') }}"
               class="{{ request()->is('kurikulum/dashboard') ? 'active' : '' }}">
                <i class="fas fa-home me-2"></i>
                Dashboard
            </a>

            <a href="{{ url('/kurikulum/sop') }}"
               class="{{ request()->is('kurikulum/sop') ? 'active' : '' }}">
                <i class="fas fa-book me-2"></i>
                SOP
            </a>

            <a href="{{ url('/kurikulum/materi') }}"
               class="{{ request()->is('kurikulum/materi') ? 'active' : '' }}">
                <i class="fas fa-folder-open me-2"></i>
                Materi & Silabus
            </a>

            <a href="{{ url('/kurikulum/jadwal-konsultasi') }}"
               class="{{ request()->is('kurikulum/jadwal-konsultasi') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt me-2"></i>
                Jadwal Konsultasi
            </a>

            <a href="{{ url('/kurikulum/review-pengajuan') }}"
               class="{{ request()->is('kurikulum/review-pengajuan') ? 'active' : '' }}">
                <i class="fas fa-check-circle me-2"></i>
                Review Pengajuan
            </a>

            <a href="{{ url('/kurikulum/monitoring') }}"
               class="{{ request()->is('kurikulum/monitoring') ? 'active' : '' }}">
                <i class="fas fa-chart-line me-2"></i>
                Monitoring Guru & Kelas
            </a>

            <a href="{{ url('/kurikulum/surat') }}"
               class="{{ request()->is('kurikulum/surat') ? 'active' : '' }}">
                <i class="fas fa-envelope me-2"></i>
                Pemberitahuan Surat
            </a>

        </div>

    </div>

    <!-- =========================
            CONTENT
    ========================== -->

    <div class="content">

        @yield('content')

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>