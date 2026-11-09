<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Teks berformat (bold, italic, underline, ukuran, warna, rata teks, daftar) dari editor web.
 *
 * - clean()       : bersihkan HTML dari editor (daftar putih tag & style) SEBELUM disimpan.
 * - render()      : HTML aman untuk tampilan/PDF. Teks lama (tanpa tag) tetap tampil rapi.
 * - editorValue() : isi awal editor (teks lama otomatis jadi paragraf).
 * - plain()       : versi teks polos (untuk ringkasan/notifikasi).
 */
class RichText
{
    private const ALLOWED = [
        'p', 'br', 'strong', 'em', 'u', 's', 'span', 'ul', 'ol', 'li', 'sub', 'sup',
    ];

    private const ALIASES = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'div' => 'p', 'h1' => 'p', 'h2' => 'p', 'h3' => 'p', 'h4' => 'p'];

    /** Tag yang dibuang beserta isinya. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript', 'link', 'meta', 'head', 'title', 'form', 'input', 'button', 'textarea', 'select', 'img', 'video', 'audio', 'canvas'];

    public static function looksLikeHtml(?string $v): bool
    {
        return $v !== null && (bool) preg_match('/<\/?[a-z][a-z0-9]*(\s[^>]*)?>/i', $v);
    }

    /** Bersihkan input editor; hasil kosong ('') bila tidak ada teks sama sekali. */
    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        // teks lama tanpa tag: simpan apa adanya (tanpa HTML)
        if (!self::looksLikeHtml($html)) {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= self::walk($child);
        }

        $text = trim(str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags($out), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($text === '') {
            return '';
        }

        return trim($out);
    }

    /** HTML aman untuk ditampilkan. $fontScale untuk PDF (huruf editor 13px, huruf PDF lebih kecil). */
    public static function render(?string $value, float $fontScale = 1.0): string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return '';
        }
        if (!self::looksLikeHtml($value)) {
            return nl2br(e($value));
        }

        $html = self::clean($value);
        if ($fontScale !== 1.0) {
            $html = preg_replace_callback('/font-size:\s*([0-9.]+)px/', fn ($m) => 'font-size: ' . round($m[1] * $fontScale, 1) . 'px', $html);
        }

        return $html;
    }

    /** Isi awal editor (HTML). Teks lama dengan baris baru menjadi paragraf. */
    public static function editorValue(?string $value): string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return '';
        }
        if (self::looksLikeHtml($value)) {
            return self::clean($value);
        }

        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => '<p>' . e($line) . '</p>')->implode('');
    }

    /** Teks polos (tanpa format). */
    public static function plain(?string $value): string
    {
        $value = (string) $value;
        if (!self::looksLikeHtml($value)) {
            return trim($value);
        }
        $html = preg_replace('#</(p|li)>|<br\s*/?>#i', "\n", self::clean($value));

        return trim(preg_replace("/\n{2,}/", "\n", html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    // ------------------------------------------------------------------ internal

    private static function walk(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (!$node instanceof DOMElement) {
            return ''; // komentar, CDATA, instruksi
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, self::DROP, true)) {
            return '';
        }
        $tag = self::ALIASES[$tag] ?? $tag;

        // penanda internal editor dibuang
        if ($tag === 'span' && str_contains((string) $node->getAttribute('class'), 'ql-ui')) {
            return '';
        }

        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= self::walk($child);
        }

        if (!in_array($tag, self::ALLOWED, true)) {
            return $inner; // tag tak dikenal (a, table, font, ...): ambil isinya saja
        }
        if ($tag === 'br') {
            return '<br>';
        }

        $style = self::cleanStyle((string) $node->getAttribute('style'));
        if ($tag === 'span' && $style === '') {
            return $inner;
        }
        $attr = $style !== '' ? ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"' : '';

        return "<{$tag}{$attr}>{$inner}</{$tag}>";
    }

    private static function cleanStyle(string $style): string
    {
        $keep = [];
        foreach (explode(';', $style) as $decl) {
            if (!str_contains($decl, ':')) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $decl, 2));
            $prop = strtolower($prop);
            $val = strtolower($val);

            switch ($prop) {
                case 'font-size':
                    if (preg_match('/^([0-9]{1,2}(?:\.[0-9]+)?)(px|pt)$/', $val, $m)) {
                        $px = $m[2] === 'pt' ? $m[1] * 4 / 3 : (float) $m[1];
                        if ($px >= 8 && $px <= 48) {
                            $keep[$prop] = round($px, 1) . 'px';
                        }
                    }
                    break;
                case 'color':
                case 'background-color':
                    if (preg_match('/^#[0-9a-f]{3,8}$/', $val) || preg_match('/^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$/', $val)) {
                        $keep[$prop] = $val;
                    }
                    break;
                case 'text-align':
                    if (in_array($val, ['left', 'right', 'center', 'justify'], true)) {
                        $keep[$prop] = $val;
                    }
                    break;
                case 'text-decoration':
                case 'text-decoration-line':
                    if (preg_match('/^(underline|line-through)(\s+(underline|line-through))?$/', $val)) {
                        $keep['text-decoration'] = $val;
                    }
                    break;
                case 'font-weight':
                    if (in_array($val, ['bold', '700', '600', '800', '900'], true)) {
                        $keep[$prop] = 'bold';
                    }
                    break;
                case 'font-style':
                    if ($val === 'italic') {
                        $keep[$prop] = 'italic';
                    }
                    break;
            }
        }

        return implode('; ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($keep), $keep));
    }
}