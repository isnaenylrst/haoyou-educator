<?php

namespace App\Http\Controllers\Admin\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\PrintTemplate;
use App\Services\InvoiceService;
use App\Services\RegistrationFormService;
use Illuminate\Http\Request;

class TemplatePrintController extends Controller
{
    public function index()
    {
        return view('admin.pengaturan.template-dokumen.index', [
            'templates' => PrintTemplate::orderBy('name')->get(),
        ]);
    }

    public function edit(PrintTemplate $template)
    {
        return view('admin.pengaturan.template-dokumen.edit', [
            'template'     => $template,
            'placeholders' => $this->isInvoice($template)
                ? InvoiceService::PLACEHOLDERS
                : RegistrationFormService::PLACEHOLDERS,
        ]);
    }

    public function update(Request $request, PrintTemplate $template)
    {
        $data = $request->validate(['content' => 'required|string']);
        $template->update(['content' => $this->sanitize($data['content'])]);

        return back()->with('success', 'Template berhasil disimpan.');
    }

    public function reset(PrintTemplate $template)
    {
        $template->update(['content' => $template->default_content]);

        return redirect()
            ->route('admin.template-dokumen.edit', $template)
            ->with('success', 'Template dikembalikan ke versi awal.');
    }

    public function preview(
        Request $request,
        PrintTemplate $template,
        RegistrationFormService $formService,
        InvoiceService $invoiceService,
    ) {
        $content = $this->sanitize((string) $request->input('content', $template->content));

        $pdf = $this->isInvoice($template)
            ? $invoiceService->preview($content)
            : $formService->preview($content);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Pratinjau-' . $template->key . '.pdf"',
        ]);
    }

    private function isInvoice(PrintTemplate $template): bool
    {
        return $template->key === InvoiceService::TEMPLATE_KEY;
    }

    /** Buang tag berbahaya; template hanya untuk teks, tabel, dan gambar. */
    private function sanitize(string $html): string
    {
        return preg_replace('#<(script|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html);
    }
}