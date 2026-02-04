<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\InvoiceService;

class InvoiceController extends Controller
{
    /**
     * Display the invoices list
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }

        // Get all payments that have invoices
        $invoices = Payment::where('tenant_id', $tenant->id)
            ->whereNotNull('invoice_number')
            ->with('subscription.plan')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('multitenancycore::tenant.invoices.index', compact(
            'tenant',
            'invoices'
        ));
    }

    /**
     * Show invoice details
     */
    public function show($id)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }

        $payment = Payment::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->whereNotNull('invoice_number')
            ->with(['subscription.plan', 'newPlan'])
            ->firstOrFail();

        $invoiceService = app(InvoiceService::class);
        $invoiceData = $invoiceService->getInvoiceData($payment);

        return view('multitenancycore::tenant.invoices.show', compact(
            'tenant',
            'payment',
            'invoiceData'
        ));
    }

    /**
     * Download invoice as PDF
     */
    public function download($id)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }

        $payment = Payment::where('tenant_id', $tenant->id)
            ->where('id', $id)
            ->whereNotNull('invoice_number')
            ->with(['subscription.plan', 'newPlan'])
            ->firstOrFail();

        $invoiceService = app(InvoiceService::class);
        $invoiceData = $invoiceService->getInvoiceData($payment);

        $pdf = Pdf::loadView('multitenancycore::tenant.invoices.pdf', compact('invoiceData'));

        $fileName = 'invoice-'.$payment->invoice_number.'.pdf';

        return $pdf->download($fileName);
    }
}
