<?php

namespace App\Support;

/**
 * Daftar SEMUA fitur untuk Owner, dikelompokkan per role.
 * Dipakai oleh: sidebar Owner, sidebar Guru/Kurikulum/Admin saat Owner login,
 * dan kartu "Semua Fitur per Role" di Dashboard Owner.
 *
 * Tambah fitur baru cukup di sini; semua menu ikut berubah.
 * Format item: [label, ikon FontAwesome, url, deskripsi singkat]
 */
class OwnerMenu
{
    public static function groups(): array
    {
        $groups = [
            [
                'key'   => 'owner',
                'label' => 'OWNER',
                'color' => '#b8860b',
                'desc'  => 'Ringkasan bisnis',
                'items' => [
                    ['Dashboard Owner', 'fa-house', '/owner/dashboard', 'Keuangan, siswa, kelas, kinerja guru'],
                ],
            ],
            [
                'key'   => 'admin',
                'label' => 'ADMIN',
                'color' => '#2563eb',
                'desc'  => 'Operasional harian: calon siswa, siswa, kelas, jadwal',
                'items' => [
                    ['Dashboard Admin',   'fa-house',         '/admin/dashboard',     'Ringkasan operasional harian'],
                    ['Calon Siswa (CRM)', 'fa-user-plus',     '/admin/calon-siswa',   'Lead, jadwal trial, follow-up, konversi'],
                    ['Siswa',             'fa-graduation-cap','/admin/siswa',         'Data siswa dan pendaftaran program'],
                    ['Kelas',             'fa-users',         '/admin/kelas',         'Kelola kelas, guru, dan kapasitas'],
                    ['Jadwal',            'fa-calendar-days', '/admin/jadwal',        'Jadwal kelas mingguan'],
                    ['Program & Level',   'fa-layer-group',   '/admin/program-level', 'Struktur program, level, paket, harga'],
                ],
            ],
            [
                'key'   => 'kurikulum',
                'label' => 'KURIKULUM',
                'color' => '#7c3aed',
                'desc'  => 'Materi, review pengajuan guru, kompetensi, monitoring',
                'items' => [
                    ['Dashboard Kurikulum',     'fa-house',          '/kurikulum/dashboard',         'Pengajuan yang perlu ditindaklanjuti'],
                    ['Materi & Silabus',        'fa-folder-open',    '/kurikulum/materi',            'CCO, lesson plan, aktivitas, kode box'],
                    ['SOP',                     'fa-file-lines',     '/kurikulum/sop',               'SOP dan template progress report'],
                    ['Jadwal Konsultasi',       'fa-calendar-days',  '/kurikulum/jadwal-konsultasi', 'Konsultasi guru dengan kurikulum'],
                    ['Tugas Kelas',             'fa-clipboard-list', '/kurikulum/tugas-kelas',       'Tugas perbaikan kelas untuk guru'],
                    ['Video Pembelajaran',      'fa-circle-play',    '/kurikulum/video',             'Upload video untuk siswa'],
                    ['Review Pengajuan',        'fa-circle-check',   '/kurikulum/review-pengajuan',  'ACC materi, jurnal, report, cuti, sertifikat'],
                    ['Kompetensi Report',       'fa-list-check',     '/kurikulum/kompetensi',        'Kompetensi dan level progress report'],
                    ['Ketersediaan Guru',       'fa-user-clock',     '/kurikulum/ketersediaan-guru', 'Hari dan jam guru bisa mengajar'],
                    ['Monitoring Guru & Kelas', 'fa-chart-line',     '/kurikulum/monitoring',        'Jadwal mingguan, kehadiran, jurnal'],
                    ['Pemberitahuan Surat',     'fa-envelope',       '/kurikulum/surat',             'Surat dan dokumen untuk guru'],
                ],
            ],
            [
                'key'   => 'guru',
                'label' => 'GURU',
                'color' => '#16a34a',
                'desc'  => 'Menu pengajar (dibuka sebagai guru pilihan)',
                'items' => [
                    ['Pilih / Ganti Guru',   'fa-users',           '/owner/guru',           'Pilih guru yang ingin dilihat'],
                    ['Dashboard Guru',       'fa-house',           '/guru/dashboard',       'Ringkasan mengajar guru'],
                    ['Notifikasi',           'fa-bell',            '/guru/notifikasi',      'Tugas, surat, permintaan kelas'],
                    ['SOP',                  'fa-book',            '/guru/sop',             'SOP untuk guru'],
                    ['Materi & Silabus',     'fa-folder-open',     '/guru/materi',          'Materi dari kurikulum'],
                    ['Kelas',                'fa-chalkboard',      '/guru/kelas',           'Upload LP dan PPT, konsultasi'],
                    ['Attendance & Journal', 'fa-user-check',      '/guru/attendance',      'Absensi siswa dan jurnal'],
                    ['Progress Report',      'fa-chart-column',    '/guru/progress-report', 'Nilai kompetensi per siswa'],
                    ['Sertifikat',           'fa-certificate',     '/guru/sertifikat',      'Buat dan ajukan sertifikat'],
                    ['Schedule',             'fa-calendar-days',   '/guru/schedule',        'Jadwal dan ketersediaan guru'],
                    ['Teaching Log',         'fa-clipboard-list',  '/guru/teaching-log',    'Riwayat mengajar'],
                    ['Cuti / Ganti Kelas',   'fa-plane-departure', '/guru/cuti',            'Pengajuan cuti dan pengganti'],
                ],
            ],
        ];

        // ubah tiap item menjadi array bernama + pola "aktif"
        foreach ($groups as &$g) {
            $g['items'] = array_map(fn ($i) => [
                'label'   => $i[0],
                'icon'    => $i[1],
                'url'     => $i[2],
                'desc'    => $i[3],
                'pattern' => ltrim($i[2], '/') . '*',
            ], $g['items']);
        }
        unset($g);

        return $groups;
    }

    /**
     * Format yang dipakai langsung oleh loop menu di layout Guru, Kurikulum, dan Owner:
     * [['label' => ..., 'color' => ..., 'items' => [[teks, ikon, url, pola], ...]], ...]
     */
    public static function asLayoutMenu(): array
    {
        return array_map(fn ($g) => [
            'label' => $g['label'],
            'color' => $g['color'],
            'items' => array_map(fn ($i) => [$i['label'], $i['icon'], $i['url'], $i['pattern']], $g['items']),
        ], self::groups());
    }
}