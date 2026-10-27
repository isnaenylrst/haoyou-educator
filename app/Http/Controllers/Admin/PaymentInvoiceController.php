<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class PaymentInvoiceController extends Controller
{
    public function show(Payment $payment, InvoiceService $invoices)
    {
        return response($invoices->generate($payment), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $payment->invoice_number . '.pdf"',
        ]);
    }

    public function preview(Request $request, InvoiceService $invoices)
    {
        $data = $request->validate(['content' => ['required', 'string']]);

        return response($invoices->preview($data['content']), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}

/*
 Route (di dalam group auth/admin yang sama dengan cetak formulir):

 Route::get('/payments/{payment}/invoice', [PaymentInvoiceController::class, 'show'])->name('payments.invoice');
 Route::post('/print-templates/invoice/preview', [PaymentInvoiceController::class, 'preview'])->name('print-templates.invoice.preview');
*/