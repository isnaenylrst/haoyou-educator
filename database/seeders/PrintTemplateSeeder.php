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
            'invoice'      => ['Invoice Pembayaran', $this->invoiceForm()],
            'invoice_extra'=> ['Invoice Biaya Tambahan', $this->invoiceExtraForm()],
        ];

        foreach ($templates as $key => [$name, $html]) {
            $template = PrintTemplate::firstOrNew(['key' => $key]);

            // Template yang isinya masih sama dengan default lama berarti belum pernah diedit admin:
            // ikut diperbarui ke default baru. Yang sudah diedit admin tidak ditimpa
            // (kembalikan lewat "reset ke default" di editor kalau mau memakai versi baru).
            $untouched = $template->exists && $template->content === $template->default_content;

            $template->name = $name;
            $template->default_content = $html;

            if (! $template->exists || $untouched) {
                $template->content = $html;
            }

            $template->save();
        }
    }

    private function regularForm(): string
    {
        return $this->header('FORMULIR PENDAFTARAN MURID BARU REGULER CLASS')
            . '<p style="margin:0 0 4px;">Ketentuan tata tertib administrasi murid:</p>'
            . $this->regularTerms()
            . $this->dataSection(
                $this->adminTable([
                    ['Tanggal Pendaftaran', '{tanggal_pendaftaran}', 'Biaya Pendaftaran', '{biaya_pendaftaran}'],
                    ['Level',               '{level}',               'Biaya Aktivitas',   '{biaya_aktivitas}'],
                    ['Total Pertemuan',     '{total_pertemuan}',     'Biaya Les',         '{biaya_les}'],
                    ['Pertemuan Pertama',   '{pertemuan_pertama}',   'Diskon',            '{diskon}'],
                    ['Jadwal',              '{jadwal}',              'Total Biaya',  '{total_pembayaran}'],
                ]),
                // Tabel mengikuti skema yang dipilih siswa: bagian JIKA_TERMIN tampil untuk skema Termin,
                // JIKA_LUNAS untuk skema Lunas (pemilihan dilakukan RegistrationFormService)
                '<!--JIKA_TERMIN-->'
                    . '<p style="font-size:12pt;font-style:italic;margin:0 0 2px;">*Khusus Pembayaran Cicilan Termin 1-3 wajib di isi tanggalnya dan di tandatangani.</p>'
                    . $this->regularPaymentTable()
                . '<!--/JIKA_TERMIN-->'
                . '<!--JIKA_LUNAS-->'
                    . $this->paymentTable([
                        'Pendaftaran' => 'dp_pendaftaran',
                        'Aktivitas'   => 'dp_aktivitas',
                        'Lunas'       => 'termin1_lunas',
                    ])
                . '<!--/JIKA_LUNAS-->',
                ''
            );
    }

    private function privateForm(): string
    {
        return $this->header('FORMULIR PENDAFTARAN MURID BARU PRIVATE CLASS')
            . '<p style="margin:0 0 4px;">Ketentuan tata tertib administrasi murid:</p>'
            . $this->privateTerms()
            . $this->dataSection(
                $this->adminTable([
                    ['Tanggal Pendaftaran', '{tanggal_pendaftaran}', 'Biaya Pendaftaran', '{biaya_pendaftaran}'],
                    ['Paket Private',       '{paket}',               'Biaya Aktivitas',   '{biaya_aktivitas}'],
                    ['Total Pertemuan',     '{total_pertemuan}',     'Biaya Les',         '{biaya_les}'],
                    ['Jadwal',              '{jadwal}',              'Diskon',            '{diskon}'],
                    ['Expired Date',        '{expired_date}',        'Total Pembayaran',  '{total_pembayaran}'],
                ], ['Materi', '{materi}']),
                $this->paymentTable([
                    'DP'    => 'dp',
                    'LUNAS' => 'lunas',
                ]),
                '*Pembayaran Terisi Otomatis sesuai tanggal yang harus dibayar'
            );
    }

    /* ---------------- Invoice pembayaran ---------------- */

    private function invoiceForm(): string
    {
        // Tata letak mengikuti template Word (A4 landscape): kop tengah, judul INVOICE kuning,
        // tabel berbingkai dengan header kuning tebal, watermark logo di latar.
        return <<<'HTML'
        <div style="position:relative;font-family:Arial,Helvetica,sans-serif;font-size:10pt;color:#000;">
        {watermark}

        <div style="text-align:center;border-bottom:1px solid #000;padding-bottom:4px;">
        <div style="font-size:15pt;font-weight:bold;">Haoyou Educator</div>
        <div style="font-size:8pt;">Jl. Terusan Dieng No.9E, Pisang Candi, Kec. Sukun, Kota Malang, Jawa Timur 65115</div>
        <div style="font-size:10pt;">Email : haoyoueducator@gmail.com &nbsp; HP : 089679209062</div>
        </div>

        <div style="font-size:20pt;font-weight:bold;color:#FFC000;margin-top:10px;line-height:1.1;">INVOICE</div>
        <div style="font-size:11pt;font-weight:bold;margin-bottom:8px;">No. {nomor_invoice}</div>

        <table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <tr>
        <td colspan="2" style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;">Data Siswa</td>
        <td colspan="2" style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;">Info Pembayaran</td>
        </tr>
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:13%;">Nama</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:31%;">{nama_siswa}</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:19%;white-space:nowrap;">Paket Program</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:37%;">{paket}</td>
        </tr>
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;">Kelas</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;">{kelas}</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;white-space:nowrap;">Total Pertemuan</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;">{jumlah_pertemuan}</td>
        </tr>
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;">Jadwal</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;">{jadwal}</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;white-space:nowrap;">Tanggal Bayar</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;">{tanggal_bayar}</td>
        </tr>
        <tr>
        <td colspan="2" rowspan="2" style="border:1px solid #000;padding:3px 6px;vertical-align:top;">Pertemuan :<br>{daftar_pertemuan}</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;white-space:nowrap;">Tahap Bayar</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;">{tahap}</td>
        </tr>
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;white-space:nowrap;">Status</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;color:#EE0000;font-weight:bold;">{status}</td>
        </tr>
        </table>

        <table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;width:70%;">Rincian Biaya</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;width:30%;text-align:right;">Jumlah(Rp)</td>
        </tr>
        <!--RINCIAN_AWAL-->
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;">Biaya Les<br>({paket})</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;text-align:right;white-space:nowrap;">{harga_paket}</td>
        </tr>
        <!--RINCIAN_DISKON-->
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFF2CC;font-weight:bold;">Total tagihan</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFF2CC;font-weight:bold;text-align:right;white-space:nowrap;">{total_tagihan}</td>
        </tr>
        </table>

        <table style="width:100%;border-collapse:collapse;">
        <tr>
        <td style="width:46%;vertical-align:top;padding-right:10px;">
        <div style="font-weight:bold;">Catatan:</div>
        <!--JIKA_ADA_CATATAN-->
        <div style="font-size:9.5pt;">{catatan}</div>
        <!--/JIKA_ADA_CATATAN-->
        </td>
        <td style="width:54%;vertical-align:top;">
        <table style="width:100%;border-collapse:collapse;">
        <!--JIKA_ADA_CICILAN-->
        <tr><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:62%;">Sudah Dibayar Sebelumnya</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;text-align:right;white-space:nowrap;">{sudah_dibayar}</td></tr>
        <!--/JIKA_ADA_CICILAN-->
        <tr><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFF2CC;font-weight:bold;">Dibayar {rincian_dibayar} (saat ini)</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFF2CC;font-weight:bold;text-align:right;white-space:nowrap;">{jumlah_dibayar}</td></tr>
        <!--JIKA_ADA_CICILAN-->
        <tr><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;color:#808080;">Sisa Tagihan</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;color:#808080;text-align:right;white-space:nowrap;">{sisa_setelah_ini}</td></tr>
        <!--/JIKA_ADA_CICILAN-->
        </table>
        <!--JIKA_BELUM_LUNAS-->
        <div style="margin-top:4px;">Pembayaran berikutnya <strong style="color:#EE0000;">jatuh tempo pada {jatuh_tempo_berikutnya}</strong></div>
        <!--/JIKA_BELUM_LUNAS-->
        </td>
        </tr>
        </table>

        <table style="width:100%;border-collapse:collapse;margin-top:14px;">
        <tr>
        <td style="width:50%;vertical-align:top;">
        <div style="font-weight:bold;">Pembayaran:</div>
        <div>Melalui BANK CENTRAL ASIA</div>
        <div>A.N GABRIELLA AGATA</div>
        <div>No. Rekening 2589999724</div>
        </td>
        <td style="width:50%;vertical-align:top;">
        <div style="font-weight:bold;">Cara Pembayaran:</div>
        <div>Nama murid (titik) Nama Kelas</div>
        <div>Contoh: {contoh_keterangan}</div>
        </td>
        </tr>
        </table>

        </div>
        HTML;
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
            . ($note !== '' ? '<p style="font-size:12pt;font-style:italic;margin:0 0 2px;">' . $note . '</p>' : '')
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

    /**
     * Tabel "Data Diisi Oleh Admin": Informasi Paket (kiri) dan Informasi Biaya Paket (kanan).
     * $rows = daftar [label kiri, nilai kiri, label kanan, nilai kanan].
     * $fullRow = baris tambahan selebar tabel [label, nilai] (mis. Materi).
     */
    private function adminTable(array $rows, ?array $fullRow = null): string
    {
        $html = '<table style="' . self::TABLE . '"><tr>'
            . $this->th('Informasi Paket', 2, 'width:50%;')
            . $this->th('Informasi Biaya Paket', 2, 'width:50%;')
            . '</tr>';

        foreach ($rows as [$label1, $value1, $label2, $value2]) {
            $bold = str_starts_with($label2, 'Total Pembayaran') ? 'font-weight:bold;' : '';

            // Label dibuat satu baris (nowrap) dan kolomnya dilebarkan supaya "Tanggal Pendaftaran" tidak turun ke bawah
            $html .= '<tr>'
                . '<td style="' . self::LABEL . 'width:23%;white-space:nowrap;">' . $label1 . '</td>'
                . '<td style="' . self::CELL . 'width:27%;">' . ($value1 ?: '&nbsp;') . '</td>'
                . '<td style="' . self::LABEL . 'width:23%;white-space:nowrap;' . $bold . '">' . $label2 . '</td>'
                . '<td style="' . self::CELL . 'width:27%;' . $bold . '">' . ($value2 ?: '&nbsp;') . '</td>'
                . '</tr>';
        }

        if ($fullRow) {
            $html .= '<tr>'
                . '<td style="' . self::LABEL . 'width:23%;white-space:nowrap;">' . $fullRow[0] . '</td>'
                . '<td colspan="3" style="' . self::CELL . '">' . $fullRow[1] . '</td>'
                . '</tr>';
        }

        return $html . '</table>';
    }

    /**
     * Tabel pembayaran formulir reguler: DP (Pendaftaran, Aktivitas), lalu Termin 1 / Lunas,
     * Termin 2, Termin 3. Bagian biaya les sudah terhitung di kolom Termin 1 / Lunas dan seterusnya,
     * jadi tidak ada kolom Biaya Les di bawah DP.
     */
    private function regularPaymentTable(): string
    {
        $head = 'text-align:center;vertical-align:middle;font-size:12pt;';
        $cell = self::CELL . 'height:40px;vertical-align:middle;text-align:center;';

        return '<table style="' . self::TABLE . '">'
            . '<tr>'
                . '<th rowspan="2" style="' . self::HEAD . 'width:15%;text-align:left;vertical-align:middle;font-size:12pt;">Keterangan</th>'
                . '<th colspan="2" style="' . self::HEAD . $head . '">DP</th>'
                . '<th rowspan="2" style="' . self::HEAD . 'width:17%;' . $head . '">Termin 1</th>'
                . '<th rowspan="2" style="' . self::HEAD . 'width:17%;' . $head . '">Termin 2</th>'
                . '<th rowspan="2" style="' . self::HEAD . 'width:17%;' . $head . '">Termin 3</th>'
            . '</tr>'
            . '<tr>'
                . '<th style="' . self::HEAD . 'width:17%;' . $head . '">Pendaftaran</th>'
                . '<th style="' . self::HEAD . 'width:17%;' . $head . '">Aktivitas</th>'
            . '</tr>'
            . '<tr>'
                . '<td style="' . self::LABEL . 'font-weight:bold;height:40px;vertical-align:middle;">Nominal :</td>'
                . '<td style="' . $cell . '">{dp_pendaftaran_nominal}</td>'
                . '<td style="' . $cell . '">{dp_aktivitas_nominal}</td>'
                . '<td style="' . $cell . '">{termin1_lunas_nominal}</td>'
                . '<td style="' . $cell . '">{termin2_nominal}</td>'
                . '<td style="' . $cell . '">{termin3_nominal}</td>'
            . '</tr>'
            . '<tr>'
                . '<td style="' . self::LABEL . 'font-weight:bold;height:40px;vertical-align:middle;">Tanggal :</td>'
                . '<td style="' . $cell . '">{dp_pendaftaran_tanggal}</td>'
                . '<td style="' . $cell . '">{dp_aktivitas_tanggal}</td>'
                . '<td style="' . $cell . '">{termin1_lunas_tanggal}</td>'
                . '<td style="' . $cell . '">{termin2_tanggal}</td>'
                . '<td style="' . $cell . '">{termin3_tanggal}</td>'
            . '</tr>'
            . '</table>';
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

    private function invoiceExtraForm(): string
    {
        return <<<'HTML'
        <div style="position:relative;font-family:Arial,Helvetica,sans-serif;font-size:10pt;color:#000;">

        <div style="text-align:center;border-bottom:1px solid #000;padding-bottom:4px;">
        <div style="font-size:15pt;font-weight:bold;">Haoyou Educator</div>
        <div style="font-size:8pt;">Jl. Terusan Dieng No.9E, Pisang Candi, Kec. Sukun, Kota Malang, Jawa Timur 65115</div>
        <div style="font-size:10pt;">Email : haoyoueducator@gmail.com &nbsp; HP : 089679209062</div>
        </div>

        <div style="font-size:20pt;font-weight:bold;color:#FFC000;margin-top:10px;line-height:1.1;">INVOICE</div>
        <div style="font-size:11pt;font-weight:bold;margin-bottom:8px;">No. {nomor_invoice}</div>

        <table style="width:100%;border-collapse:collapse;">
        <tr>
        <td colspan="2" style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;width:50%;">Data Siswa</td>
        <td colspan="2" style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;width:50%;">Info Pembayaran</td>
        </tr>
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:16%;white-space:nowrap;">Nama</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:34%;">{nama_siswa}</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:16%;white-space:nowrap;">Tanggal Bayar</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:34%;">{tanggal_bayar}</td>
        </tr>
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:16%;white-space:nowrap;">Kelas</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:34%;">{kelas}</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:16%;white-space:nowrap;">Metode</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:34%;">{metode_bayar}</td>
        </tr>
        </table>

        <div style="height:17px;line-height:17px;font-size:1px;">&nbsp;</div>

        <table style="width:100%;border-collapse:collapse;">
        <tr>
        <td colspan="4" style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;">Detail Pertemuan</td>
        </tr>
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:16%;white-space:nowrap;">Tanggal Pertemuan</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:34%;">{tanggal_pertemuan}</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:16%;white-space:nowrap;">Jam Pertemuan</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:34%;">{jam_pertemuan}</td>
        </tr>
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:16%;white-space:nowrap;">Guru</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:34%;">{guru}</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:16%;white-space:nowrap;">Lokasi</td><td style="border:1px solid #000;padding:3px 6px;vertical-align:top;width:34%;">{lokasi}</td>
        </tr>
        </table>

        <div style="height:17px;line-height:17px;font-size:1px;">&nbsp;</div>

        <table style="width:100%;border-collapse:collapse;">
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;width:70%;">Rincian Biaya</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFD966;font-weight:bold;width:30%;text-align:right;">Jumlah(Rp)</td>
        </tr>
        {rincian_biaya}
        <tr>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFF2CC;font-weight:bold;">Total dibayarkan</td>
        <td style="border:1px solid #000;padding:3px 6px;vertical-align:top;background:#FFF2CC;font-weight:bold;text-align:right;white-space:nowrap;">{jumlah_dibayar}</td>
        </tr>
        </table>

        <div style="height:17px;line-height:17px;font-size:1px;">&nbsp;</div>

        <table style="width:100%;border-collapse:collapse;">
        <tr>
        <td style="width:50%;vertical-align:top;">
        <div style="font-weight:bold;">Pembayaran:</div>
        <div>Melalui BANK CENTRAL ASIA</div>
        <div>A.N GABRIELLA AGATA</div>
        <div>No. Rekening 2589999724</div>
        </td>
        <td style="width:50%;vertical-align:top;">
        <div style="font-weight:bold;">Cara Pembayaran:</div>
        <div>Nama murid (titik) Nama Kelas</div>
        <div>Contoh: Rudi.Maochong 1</div>
        </td>
        </tr>
        </table>

        </div>
        HTML;
    }
}