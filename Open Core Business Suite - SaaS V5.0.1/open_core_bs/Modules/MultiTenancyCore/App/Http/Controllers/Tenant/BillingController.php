<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Tenant;

class BillingController extends Controller
{
    /**
     * Display billing overview and payment history
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }

        // Get payment history
        $payments = Payment::where('tenant_id', $tenant->id)
            ->with('subscription.plan')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate totals
        $totalPaid = Payment::where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->sum('amount');

        $pendingAmount = Payment::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->sum('amount');

        return view('multitenancycore::tenant.billing.index', compact(
            'tenant',
            'payments',
            'totalPaid',
            'pendingAmount'
        ));
    }

    /**
     * Show payment instructions
     */
    public function paymentInstructions($paymentId)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        $payment = Payment::where('tenant_id', $tenant->id)
            ->where('id', $paymentId)
            ->where('status', 'pending')
            ->firstOrFail();

        return view('multitenancycore::tenant.billing.payment-instructions', compact(
            'payment'
        ));
    }

    /**
     * Upload payment proof
     */
    public function uploadProof(Request $request, $paymentId)
    {
        $request->validate([
            'proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        $payment = Payment::where('tenant_id', $tenant->id)
            ->where('id', $paymentId)
            ->where('status', 'pending')
            ->firstOrFail();

        // Store the proof
        $path = $request->file('proof')->store('payment-proofs/'.$tenant->id, 'public');

        $payment->proof_document_path = $path;
        $payment->save();

        return redirect()->route('multitenancycore.tenant.billing')
            ->with('success', 'Payment proof uploaded successfully. We will verify your payment within 24 hours.');
    }

    /**
     * Get payment details for AJAX request
     */
    public function getPaymentDetails($paymentId)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return response()->json([
                'status' => 'error',
                'message' => __('Tenant not found'),
            ], 404);
        }

        $payment = Payment::where('tenant_id', $tenant->id)
            ->where('id', $paymentId)
            ->with(['subscription.plan'])
            ->first();

        if (! $payment) {
            return response()->json([
                'status' => 'error',
                'message' => __('Payment not found'),
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $payment,
        ]);
    }

    /**
     * View payment proof document
     */
    public function viewProof(Payment $payment)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->firstOrFail();

        // Verify tenant owns this payment
        if ($payment->tenant_id !== $tenant->id) {
            abort(403);
        }

        if (! $payment->proof_document_path || ! Storage::disk('public')->exists($payment->proof_document_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($payment->proof_document_path));
    }
}
