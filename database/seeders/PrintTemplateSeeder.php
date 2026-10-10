<?php

namespace Database\Seeders;

use App\Models\PrintTemplate;
use Illuminate\Database\Seeder;

class PrintTemplateSeeder extends Seeder
{
    /* Gaya sel tabel formulir (dipakai di semua tabel halaman data) */
    private const CELL  = 'border:1px solid #000;padding:4px 6px;vertical-align:top;';
    private const LABEL = self::CELL;   // label tanpa background abu-abu
    private const HEAD  = self::CELL . 'background:#e6e6e6;font-weight:bold;';
    private const TABLE = 'width:100%;border-collapse:collapse;margin-bottom:8px;';

    public function run(): void
    {
        $templates = [
            'form_regular' => ['Formulir Pendaftaran Regular', $this->regularForm()],
            'form_private' => ['Formulir Pendaftaran Private', $this->privateForm()],
        ];

        foreach ($templates as $key => [$name, $html]) {
            $template = PrintTemplate::firstOrNew(['key' => $key]);
            $template->name = $name;
            $template->default_content = $html;

            if (! $template->exists) {
                $template->content = $html; // jangan timpa hasil edit admin
            }

            $template->save();
        }
    }

    private function regularForm(): string
    {
        return $this->header('FORMULIR PENDAFTARAN MURID BARU REGULAR CLASS')
            . $this->regularTerms()
            . $this->dataSection(
                $this->table([
                    'Tgl Pendaftaran'   => '{tanggal_pendaftaran}',
                    'Paket'             => '{paket}',
                    'Level'             => '{level}',
                    'Jadwal'            => '{jadwal}',
                    'Pertemuan Pertama' => '',
                    'Biaya Pendaftaran' => '{biaya_pendaftaran}',
                    'Biaya Aktivitas'   => '{biaya_aktivitas}',
                    'Biaya Les'         => '{biaya_les}',
                    'Diskon'            => '{diskon}',
                    'Total Pembayaran'  => '{total_pembayaran}',
                ]),
                $this->paymentTable([
                    'DP / LUNAS' => 'dp_lunas',
                    'Termin 1'   => 'termin1',
                    'Termin 2'   => 'termin2',
                    'Termin 3'   => 'termin3',
                ]),
                '*Khusus Pembayaran Cicilan Termin 1-3 wajib di isi tanggalnya dan di tandatangani.'
            );
    }

    private function privateForm(): string
    {
        return $this->header('FORMULIR PENDAFTARAN MURID BARU PRIVATE CLASS')
            . '<p style="margin:0 0 4px;">Ketentuan tata tertib administrasi murid:</p>'
            . $this->privateTerms()
            . $this->dataSection(
                $this->table([
                    'Tgl Pendaftaran'           => '{tanggal_pendaftaran}',
                    'Private'                   => '{paket}',
                    'Jadwal'                    => '{jadwal}',
                    'Total pertemuan (1 bulan)' => '',
                    'Pertemuan Pertama'         => '',
                    'Expired Date'              => '',
                    'Materi'                    => '',
                    'Biaya Pendaftaran'         => '{biaya_pendaftaran}',
                    'Biaya Aktivitas'           => '{biaya_aktivitas}',
                    'Biaya Les'                 => '{biaya_les}',
                    'Diskon'                    => '{diskon}',
                    'Total Pembayaran'          => '{total_pembayaran}',
                ]),
                $this->paymentTable([
                    'DP'    => 'dp',
                    'LUNAS' => 'lunas',
                ]),
                '*Khusus Pembayaran Cicilan Termin di isi tanggalnya'
            );
    }

    /* ---------------- Potongan umum ---------------- */

    private function header(string $title): string
    {
        return <<<HTML
        <table style="width:100%;border-collapse:collapse;"><tr>
        <td style="width:90px;vertical-align:middle;">{logo}</td>
        <td style="text-align:center;">HAOYOU EDUCATOR<br>Jl. Terusan Dieng No.9E, Pisang Candi, Sukun, Kota Malang,<br>Jawa Timur 65115<br>Email : haoyoueducator@gmail.com &nbsp; HP : 089679209062</td>
        </tr></table>
        <h3 style="text-align:center;margin:12px 0;">{$title}</h3>
        HTML;
    }

    /** Halaman 2: data pendaftaran, data admin, pembayaran, tanda tangan. */
    private function dataSection(string $adminTable, string $paymentTable, string $note): string
    {
        // Kiri: Data Siswa, kanan: Data Wali Murid. Alamat digabung 2 baris (rowspan).
        $identity = '<table style="' . self::TABLE . '">'
            . '<tr>' . $this->th('Data Siswa', 2, 'width:50%;') . $this->th('Data Wali Murid', 2, 'width:50%;') . '</tr>'
            . $this->pair('Nama Siswa', '{nama_siswa}', 'Nama Orang Tua', '{nama_orang_tua}')
            . $this->pair('Tanggal Lahir', '{tanggal_lahir}', 'Nomor Telepon', '{telepon_orang_tua}')
            . $this->pair('Usia', '{usia}', 'Sosial Media', '{sosial_media}')
            . '<tr>'
                . '<td style="' . self::LABEL . 'width:19%;">Alergi</td>'
                . '<td style="' . self::CELL . 'width:31%;">{alergi}</td>'
                . '<td rowspan="2" style="' . self::LABEL . 'width:19%;">Alamat</td>'
                . '<td rowspan="2" style="' . self::CELL . 'width:31%;">{alamat}</td>'
            . '</tr>'
            . '<tr>'
                . '<td style="' . self::LABEL . 'width:19%;">Nama Sekolah</td>'
                . '<td style="' . self::CELL . 'width:31%;">{sekolah}</td>'
            . '</tr>'
            . '</table>';

        $sign = fn (string $role) => '<td style="width:33%;text-align:center;">' . $role
            . '<div style="height:55px;"></div>(........................................)</td>';

        return '<div style="page-break-before:always;font-size:10.5pt;line-height:1.3;">'
            . '<h4 style="margin:0 0 6px;font-size:12pt;">Data Pendaftaran</h4>'
            . $identity
            . '<p style="margin:0 0 4px;"><strong>Data Diisi Oleh Admin</strong></p>'
            . $adminTable
            . '<p style="font-size:12pt;font-style:italic;margin:0 0 2px;">' . $note . '</p>'
            . $paymentTable
            . '<p style="margin:6px 0;">Menyatakan bersedia untuk mengikuti peraturan pembayaran yang tertulis di ketentuan tata tertib administrasi Haoyou Educator</p>'
            . '<table style="width:100%;margin-top:4px;"><tr>'
            . '<td style="width:50%;vertical-align:top;"><strong>Pembayaran :</strong><br>Melalui <strong>BANK CENTRAL ASIA</strong><br>A.N <strong>GABRIELLA AGATA</strong><br>No. Rekening <strong>2589999724</strong></td>'
            . '<td style="width:50%;vertical-align:top;">Cara Pembayaran :<br>Nama murid (titik) Nama kelas<br><strong>Contoh : Rudi.Maochong 1</strong></td>'
            . '</tr></table>'
            . '<table style="width:100%;margin-top:18px;"><tr>'
            . $sign('Klien') . $sign('Admin') . $sign('PIC')
            . '</tr></table>'
            . '</div>';
    }

    /** Satu baris dengan dua pasang label/nilai (4 kolom: 19% / 31% / 19% / 31%). */
    private function pair(string $label1, string $value1, string $label2, string $value2): string
    {
        return '<tr>'
            . '<td style="' . self::LABEL . 'width:19%;">' . $label1 . '</td>'
            . '<td style="' . self::CELL . 'width:31%;">' . $value1 . '</td>'
            . '<td style="' . self::LABEL . 'width:19%;">' . $label2 . '</td>'
            . '<td style="' . self::CELL . 'width:31%;">' . $value2 . '</td>'
            . '</tr>';
    }

    private function th(string $text, int $colspan, string $extra = ''): string
    {
        return '<th colspan="' . $colspan . '" style="' . self::HEAD . 'text-align:left;' . $extra . '">' . $text . '</th>';
    }

    /** Tabel data admin: label di kiri, nilai di kanan. */
    private function table(array $rows): string
    {
        $html = '<table style="' . self::TABLE . '">';

        foreach ($rows as $label => $value) {
            $isTotal = str_starts_with($label, 'Total Pembayaran');
            $bold = $isTotal ? 'font-weight:bold;' : '';

            $html .= '<tr>'
                . '<td style="' . self::LABEL . 'width:36%;' . $bold . '">' . $label . '</td>'
                . '<td style="' . self::CELL . $bold . '">' . ($value ?: '&nbsp;') . '</td>'
                . '</tr>';
        }

        return $html . '</table>';
    }

    /** Tabel pembayaran: kolom pertama 20%, sisanya dibagi rata. */
    private function paymentTable(array $columns): string
    {
        $width = round(80 / max(count($columns), 1), 2);

        $head    = '<tr><th style="' . self::HEAD . 'width:20%;text-align:left;font-size:12pt;">Keterangan</th>';
        $nominal = '<tr><td style="' . self::LABEL . 'font-weight:bold;height:40px;vertical-align:middle;">Nominal :</td>';
        $tanggal = '<tr><td style="' . self::LABEL . 'font-weight:bold;height:40px;vertical-align:middle;">Tanggal :</td>';

        foreach ($columns as $label => $prefix) {
            $head    .= '<th style="' . self::HEAD . 'width:' . $width . '%;text-align:center;font-size:12pt;">' . $label . '</th>';
            $nominal .= '<td style="' . self::CELL . 'height:40px;vertical-align:middle;text-align:center;">{' . $prefix . '_nominal}</td>';
            $tanggal .= '<td style="' . self::CELL . 'height:40px;vertical-align:middle;text-align:center;">{' . $prefix . '_tanggal}</td>';
        }

        return '<table style="' . self::TABLE . '">'
            . $head . '</tr>' . $nominal . '</tr>' . $tanggal . '</tr></table>';
    }

    /* ---------------- Syarat & ketentuan ---------------- */

    private function regularTerms(): string
    {
        return <<<'HTML'
        <ol style="margin:0;padding-left:18px;text-align:justify;">
        <li>Murid baru wajib membayar <strong>biaya pendaftaran {nominal_pendaftaran}</strong> dan <strong>biaya aktivitas {nominal_aktivitas}</strong> paling lambat <strong>H-7 sebelum kelas dimulai</strong>, serta melengkapi seluruh dokumen yang diperlukan.</li>
        <li>Murid Daily Activity wajib mengikuti program per level selama <strong>3 bulan</strong>. Pembayaran bisa dilakukan secara <strong>cicilan (maksimal tanggal 5 setiap bulan)</strong> atau <strong>lunas 3 bulan di depan</strong>.</li>
        <li>Murid Regular HSK wajib mengikuti kelas sesuai paket dan level yang dipilih. Pembayaran dilakukan <strong>sebelum program dimulai.</strong> Untuk level HSK 3 ke atas, pembayaran menggunakan sistem termin setiap <strong>3 bulan</strong>.</li>
        <li>Pembatalan kelas oleh murid setelah program berjalan, dengan alasan apa pun, <strong>tidak menghapus kewajiban pembayaran</strong> sesuai kontrak. Biaya yang telah dibayarkan <strong>tidak dapat dikembalikan</strong> maupun dialihkan ke program lain.</li>
        <li>Kelas berlangsung sesuai jadwal yang telah ditetapkan. <strong>Perubahan jadwal</strong> hanya dapat diajukan setelah paket berakhir. Ketidakhadiran murid dengan alasan apa pun <strong>dianggap hangus dan tidak dapat diganti.</strong></li>
        <li>Murid Regular yang berhalangan hadir dapat mengajukan kelas pengganti private (OPTIONAL) dengan tambahan biaya <strong>Rp175.000 per pertemuan.</strong></li>
        <li>Pada hari libur nasional/tanggal merah, kelas diliburkan dan diganti pada minggu berikutnya di hari yang sama.</li>
        <li>Murid wajib mengonfirmasi kelanjutan kelas dengan melunasi biaya les <strong>paling lambat 1 minggu</strong> sebelum kelas dimulai.</li>
        <li>Apabila <strong>konfirmasi terlambat</strong> dan kuota kelas telah penuh, Haoyou Educator berhak menolak pendaftaran atau menawarkan jadwal alternatif.</li>
        <li>Kehilangan buku paket akan dikenakan biaya penggantian sesuai ketentuan yang berlaku.</li>
        <li>Apabila Haoyou Educator menyelenggarakan kegiatan pendidikan terkait pembelajaran (performance, lomba, dan sejenisnya) pada jam kelas, maka tidak ada kewajiban penggantian kelas.</li>
        <li><strong>Ketentuan Cuti:</strong>
        <ul>
        <li>Cuti hanya dapat diajukan <strong>setelah paket 3 bulan selesai</strong>.</li>
        <li>Cuti <strong>maksimal 1 bulan setelah program berakhir</strong> tidak dikenakan biaya.</li>
        <li>Cuti lebih dari 1 bulan dan kembali aktif di kemudian hari hanya dikenakan <strong>biaya pendaftaran {nominal_pendaftaran} (gratis biaya aktivitas).</strong></li>
        </ul></li>
        <li>Murid yang telah melakukan <strong>pembayaran penuh untuk 1 level (3 bulan)</strong> dan kemudian cuti atau tidak dapat mengikuti kelas dengan alasan apa pun, <strong>tidak ada pengembalian maupun pengalihan biaya ke periode berikutnya.</strong></li>
        <li>Murid yang menggunakan sistem pembayaran termin tetap wajib <strong>melunasi seluruh kewajiban hingga paket 3 bulan selesai.</strong> Apabila tidak menyelesaikan pembayaran, akan dikenakan denda Rp1.000.000 serta wajib membayar kembali biaya pendaftaran dan aktivitas saat mendaftar ulang.</li>
        <li>Uang DP yang telah dibayarkan tidak dapat dikembalikan dan hanya dapat disimpan (keep) maksimal 3 bulan. Setelah melewati batas tersebut, DP dianggap hangus. Apabila murid ingin memulai program kembali, akan dikenakan biaya pendaftaran dan aktivitas sesuai ketentuan yang berlaku.</li>
        </ol>
        HTML;
    }

    private function privateTerms(): string
    {
        return <<<'HTML'
        <ol style="margin:0;padding-left:18px;text-align:justify;">
        <li>Murid baru wajib membayar biaya pendaftaran sebesar {nominal_pendaftaran},- dan biaya aktivitas sebesar {nominal_aktivitas},- serta melengkapi dokumen yang diperlukan;</li>
        <li>Murid wajib mengambil paket 1 bulan; pelunasan maximal H-7 hari, apabila pembatalan dilakukan oleh pihak murid dengan alasan apapun maka biaya les tidak dapat dikembalikan maupun diubah ke kelas lain;</li>
        <li>Tanggal merah kelas diliburkan dan diganti di minggu yang sama sesuai ketentuan Haoyou Educator;</li>
        <li>Murid wajib mengkonfirmasi kelanjutan kelas dengan melunasi biaya les selambat-lambatnya satu minggu sebelum kelas dimulai;</li>
        <li>Bagi murid yang terlambat melakukan konfirmasi dan apabila jam yang dipilih sudah terisi kelas lain (kelas penuh) maka Haoyou Educator berhak menolak murid tersebut ataupun dicarikan jadwal kelas yang lain;</li>
        <li>Apabila buku paket hilang maka akan dikenakan biaya pengganti (konfirmasi ke admin untuk biaya per buku);</li>
        <li>Setelah kelas berjalan, jika ada perubahan jadwal pembelajaran dari murid diperbolehkan izin maximal H-1, bagi murid yang izin di hari H maupun tidak dapat hadir pada jam pelajaran sesuai jadwal yang sudah ditentukan dengan alasan apapun maka kelas akan dihanguskan (tidak di ganti);</li>
        <li>Perihal penggantian hari diberikan expired date maximal 30 hari dari pertemuan pertama, jika penggantian tidak dilakukan maka kelas akan dianggap hangus.</li>
        <li>Ketika Haoyou Educator melakukan aktivitas lain di dalam jam pelajaran (performance, lomba, dll) maka kelas akan digantikan dihari yang lain sesuai expired date;</li>
        <li><strong>Perihal cuti:</strong><br>Cuti hanya dapat dilakukan setelah paket 1 bulan berakhir, cuti dalam satu bulan setelah kelas berakhir tidak dikenakan biaya cuti.<br>Bagi murid yang cuti lebih dari 1 bulan, dan akan melanjutkan les dikemudian hari, hanya akan di kenakan <strong>biaya pendaftaran awal sebesar {nominal_pendaftaran},- (GRATIS BIAYA AKTIVITAS)</strong></li>
        <li>Untuk murid yang sudah melakukan pembayaran uang les 1 bulan dan akan melakukan cuti di bulan tersebut atau berhalangan hadir dengan alasan apapun maka uang les tidak dapat dikembalikan ataupun dialihkan ke bulan berikutnya;</li>
        <li>Untuk murid datang kerumah akan dikenakan biaya tambahan sebesar 50 rb/ pertemuan, pembayaran wajib dilakukan H-1, jika tidak melakukan pembayaran kelas akan tetap berjalan secara offline di tempat haoyou.</li>
        <li>Uang DP yang sudah dibayarkan tidak dapat dikembalikan dan di keep maximal 3 bulan (setelah 3 bulan akan di anggap hangus), jika setelah uang DP hangus, client akan memulai program les maka akan dikenakan biaya pendaftaran dan aktivitas lagi.</li>
        </ol>
        HTML;
    }
}