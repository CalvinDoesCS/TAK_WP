<?php

namespace Modules\MultiTenancyCore\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Payment;

class InvoiceService
{
    /**
     * Generate invoice number for a payment
     */
    public function generateInvoiceNumber(Payment $payment): string
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');

        // Get the last invoice number for this year-month
        $lastInvoice = Payment::whereNotNull('invoice_number')
            ->where('invoice_number', 'like', "INV-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the sequence number from the last invoice
            $lastNumber = (int) substr($lastInvoice->invoice_number, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format: INV-YYYYMM-00001
        return sprintf('INV-%s%s-%05d', $year, $month, $nextNumber);
    }

    /**
     * Generate invoice for an approved payment
     */
    public function generateInvoice(Payment $payment): bool
    {
        if (! $payment->isApproved()) {
            throw new \Exception('Invoice can only be generated for approved payments');
        }

        if ($payment->invoice_number) {
            return true; // Invoice already exists
        }

        try {
            DB::beginTransaction();

            // Generate invoice number
            $payment->invoice_number = $this->generateInvoiceNumber($payment);
            $payment->save();

            // You can add more invoice-related logic here
            // For example: generate PDF, send email notification, etc.

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate invoices for all approved payments without invoice numbers
     */
    public function generatePendingInvoices(): int
    {
        $payments = Payment::where('status', 'approved')
            ->whereNull('invoice_number')
            ->get();

        $count = 0;
        foreach ($payments as $payment) {
            try {
                $this->generateInvoice($payment);
                $count++;
            } catch (\Exception $e) {
                // Log error and continue with next payment
                Log::error('Failed to generate invoice for payment ID '.$payment->id.': '.$e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Get invoice data for rendering
     */
    public function getInvoiceData(Payment $payment): array
    {
        if (! $payment->invoice_number) {
            throw new \Exception('Payment does not have an invoice number');
        }

        $tenant = $payment->tenant;
        $subscription = $payment->subscription;
        $plan = $subscription ? $subscription->plan : $payment->newPlan;

        // Get SaaS settings for company info
        $saasSettings = DB::table('saas_settings')->pluck('value', 'key')->toArray();

        return [
            'invoice' => [
                'number' => $payment->invoice_number,
                'date' => $payment->approved_at ?? $payment->created_at,
                'due_date' => $payment->approved_at ?? $payment->created_at,
                'status' => $payment->status,
            ],
            'company' => [
                'name' => $saasSettings['company_name'] ?? config('app.name'),
                'address' => $saasSettings['company_address'] ?? '',
                'phone' => $saasSettings['company_phone'] ?? '',
                'email' => $saasSettings['company_email'] ?? '',
                'tax_id' => $saasSettings['company_tax_id'] ?? '',
            ],
            'customer' => [
                'name' => $tenant->company_name,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
            ],
            'items' => [
                [
                    'description' => $plan ? $plan->name.' - '.ucfirst($plan->billing_period).' Subscription' : 'Subscription Payment',
                    'quantity' => 1,
                    'price' => $payment->amount,
                    'total' => $payment->amount,
                ],
            ],
            'payment' => [
                'method' => ucfirst($payment->payment_method),
                'reference' => $payment->reference_number,
                'transaction_id' => $payment->gateway_transaction_id,
                'paid_at' => $payment->paid_at,
            ],
            'totals' => [
                'subtotal' => $payment->amount,
                'tax' => 0, // You can add tax calculation here
                'total' => $payment->amount,
                'currency' => $payment->currency,
            ],
        ];
    }
}
