<?php

namespace Modules\MultiTenancyCore\App\Console;

use Illuminate\Console\Command;
use Modules\MultiTenancyCore\App\Services\InvoiceService;

class GenerateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'saas:generate-invoices 
                           {--all : Generate invoices for all approved payments without invoice numbers}
                           {--payment= : Generate invoice for a specific payment ID}';

    /**
     * The console command description.
     */
    protected $description = 'Generate invoices for approved payments';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoiceService = app(InvoiceService::class);
        
        if ($paymentId = $this->option('payment')) {
            // Generate invoice for specific payment
            try {
                $payment = \Modules\MultiTenancyCore\App\Models\Payment::findOrFail($paymentId);
                
                if ($payment->invoice_number) {
                    $this->warn("Payment #{$paymentId} already has invoice number: {$payment->invoice_number}");
                    return 0;
                }
                
                if (!$payment->isApproved()) {
                    $this->error("Payment #{$paymentId} is not approved. Status: {$payment->status}");
                    return 1;
                }
                
                $invoiceService->generateInvoice($payment);
                $this->info("Invoice generated successfully: {$payment->invoice_number}");
                
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice: " . $e->getMessage());
                return 1;
            }
            
        } else {
            // Generate invoices for all pending
            $this->info('Generating invoices for all approved payments without invoice numbers...');
            
            try {
                $count = $invoiceService->generatePendingInvoices();
                $this->info("Successfully generated {$count} invoices.");
                
            } catch (\Exception $e) {
                $this->error("Error during batch invoice generation: " . $e->getMessage());
                return 1;
            }
        }
        
        return 0;
    }
}