<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\VocabSetting;
use Illuminate\Http\Request;

class VocabGuideVideoController extends Controller
{
    public function edit()
    {
        $url = VocabSetting::read('guide_video');

        return view('shared.video-panduan', [
            'url'     => $url,
            'videoId' => VocabSetting::youtubeId($url),
            'action'  => auth()->user()->role === 'Owner'   // sesuaikan cara kamu cek role
                ? route('owner.video-panduan.save')
                : route('kurikulum.video-panduan.save'),
        ]);
    }

    public function save(Request $r)
    {
        $r->validate([
            'video_url' => ['nullable', 'url', function ($attr, $v, $fail) use ($r) {
                if ($v && ! VocabSetting::youtubeId($v)) $fail('Link harus berupa link video YouTube yang valid.');
            }],
        ]);

        VocabSetting::put('guide_video', $r->video_url);

        return back()->with('ok', $r->video_url ? 'Video panduan disimpan' : 'Video panduan dihapus');
    }
}