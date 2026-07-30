<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SopController extends Controller
{
    public function index()
    {
        $sops = Document::where('document_type','SOP')
            ->latest()
            ->get();

        return view('kurikulum.sop', compact('sops'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'=>'required',
            'file'=>'required|mimes:pdf|max:5120'
        ]);

        $path = $request->file('file')
            ->store('documents/sop','public');

        Document::create([

            'title'=>$request->title,

            'document_type'=>'SOP',

            'file_path'=>$path,

            'visibility'=>'Teacher',

            'uploaded_by'=>Auth::id(),

            'user_id'=>Auth::id(),

        ]);

        return back()->with('success','SOP berhasil diupload');
    }

    public function destroy(Document $document)
    {
        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        return back()->with('success','SOP berhasil dihapus');
    }

}