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
        if ($this->isExtraInvoice($template)) {
            $placeholders = InvoiceService::PLACEHOLDERS_EXTRA;
        } elseif ($this->isInvoice($template)) {
            $placeholders = InvoiceService::PLACEHOLDERS;
        } else {
            $placeholders = RegistrationFormService::PLACEHOLDERS;
        }

        return view('admin.pengaturan.template-dokumen.edit', [
            'template' => $template,
            'placeholders' => $placeholders,
        ]);
    }

    public function update(Request $request, PrintTemplate $template)
    {
        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $template->update([
            'content' => $this->sanitize($data['content']),
        ]);

        return back()->with(
            'success',
            'Template berhasil disimpan.'
        );
    }

    public function reset(PrintTemplate $template)
    {
        $template->update([
            'content' => $template->default_content,
        ]);

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
        $content = $this->sanitize(
            (string) $request->input('content', $template->content)
        );

        if ($this->isInvoice($template) || $this->isExtraInvoice($template)) {
            $pdf = $invoiceService->preview($content);
        } else {
            $pdf = $formService->preview($content);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' =>
                'inline; filename="Pratinjau-' .
                $template->key .
                '.pdf"',
        ]);
    }

    /**
     * Invoice pembayaran paket reguler/private.
     */
    private function isInvoice(PrintTemplate $template): bool
    {
        return $template->key === InvoiceService::TEMPLATE_KEY;
    }

    /**
     * Invoice biaya tambahan:
     * - Home Visit
     * - Pertemuan Pengganti
     */
    private function isExtraInvoice(PrintTemplate $template): bool
    {
        return $template->key === InvoiceService::TEMPLATE_KEY_EXTRA;
    }

    /**
     * Membuang tag berbahaya dari HTML template.
     */
    private function sanitize(string $html): string
    {
        return preg_replace(
            '#<(script|iframe|object|embed)\b[^>]*>.*?</\1>#is',
            '',
            $html
        ) ?? '';
    }
}
