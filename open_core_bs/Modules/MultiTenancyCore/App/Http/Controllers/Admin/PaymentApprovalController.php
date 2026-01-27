<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Admin;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Services\PaymentProcessingService;
use Yajra\DataTables\Facades\DataTables;

class PaymentApprovalController extends Controller
{
    public function __construct(
        protected PaymentProcessingService $paymentProcessingService
    ) {}

    /**
     * Display payment approval queue
     */
    public function index()
    {
        return view('multitenancycore::admin.payments.approval-queue');
    }

    /**
     * Get pending payments for DataTables
     */
    public function indexAjax(Request $request)
    {
        $query = Payment::with(['tenant', 'subscription.plan'])
            ->pending()
            ->offline()
            ->latest();

        return DataTables::of($query)
            ->addColumn('tenant_info', function ($payment) {
                return view('multitenancycore::admin.payments._tenant-info', compact('payment'))->render();
            })
            ->addColumn('amount_display', function ($payment) {
                return $payment->formatted_amount;
            })
            ->addColumn('payment_info', function ($payment) {
                return view('multitenancycore::admin.payments._payment-info', compact('payment'))->render();
            })
            ->addColumn('status_display', function ($payment) {
                return view('multitenancycore::admin.payments._status', compact('payment'))->render();
            })
            ->addColumn('proof_document', function ($payment) {
                if ($payment->proof_document_path) {
                    return '<a href="'.$payment->proof_document_url.'" target="_blank" class="btn btn-sm btn-label-primary">
                        <i class="bx bx-file me-1"></i>'.__('View Proof').'
                    </a>';
                }

                return '<span class="text-muted">'.__('No document').'</span>';
            })
            ->addColumn('submitted_at', function ($payment) {
                return $payment->created_at->format('Y-m-d H:i');
            })
            ->addColumn('actions', function ($payment) {
                return view('multitenancycore::admin.payments._approval-actions', compact('payment'))->render();
            })
            ->rawColumns(['tenant_info', 'payment_info', 'status_display', 'proof_document', 'actions'])
            ->make(true);
    }

    /**
     * Show payment details
     */
    public function show(Payment $payment)
    {
        $payment->load(['tenant', 'subscription.plan', 'approvedBy']);

        return view('multitenancycore::admin.payments.show', compact('payment'));
    }

    /**
     * Approve payment
     */
    public function approve(Request $request, Payment $payment)
    {
        if (! $payment->isPending()) {
            return Error::response(__('Only pending payments can be approved'));
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ]);
        }

        // Add notes to metadata if provided
        if ($request->filled('notes')) {
            $metadata = $payment->metadata ?? [];
            $metadata['approval_notes'] = $request->notes;
            $payment->update(['metadata' => $metadata]);
        }

        // Use PaymentProcessingService to handle approval
        // This handles: payment status, invoice generation, subscription activation, notifications
        $result = $this->paymentProcessingService->processPaymentSuccess(
            $payment,
            'offline_manual_'.time(),
            null,
            auth()->id()
        );

        if (! $result['success']) {
            return Error::response(__('Failed to approve payment'));
        }

        // Activate tenant if needed
        if ($payment->subscription) {
            $tenant = $payment->tenant;
            if ($tenant && $tenant->status === 'approved' && $tenant->database && $tenant->database->isProvisioned()) {
                $tenant->update(['status' => 'active']);
            }
        }

        return Success::response([
            'message' => __('Payment approved successfully'),
        ]);
    }

    /**
     * Reject payment
     */
    public function reject(Request $request, Payment $payment)
    {
        if (! $payment->isPending()) {
            return Error::response(__('Only pending payments can be rejected'));
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ]);
        }

        // Use PaymentProcessingService to handle rejection
        $result = $this->paymentProcessingService->rejectPayment(
            $payment,
            auth()->id(),
            $request->reason
        );

        if (! $result['success']) {
            return Error::response(__('Failed to reject payment'));
        }

        return Success::response([
            'message' => __('Payment rejected successfully'),
        ]);
    }

    /**
     * Get payment statistics
     */
    public function statistics()
    {
        $stats = [
            'pending_count' => Payment::pending()->count(),
            'pending_amount' => Payment::pending()->sum('amount'),
            'approved_today' => Payment::whereDate('approved_at', today())->count(),
            'approved_this_month' => Payment::whereMonth('approved_at', now()->month)
                ->whereYear('approved_at', now()->year)
                ->count(),
            'total_approved_amount' => Payment::where('status', 'approved')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Upload payment proof
     */
    public function uploadProof(Request $request, Payment $payment)
    {
        $validator = Validator::make($request->all(), [
            'proof_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ]);
        }

        try {
            // Delete old document if exists
            if ($payment->proof_document_path && Storage::exists($payment->proof_document_path)) {
                Storage::delete($payment->proof_document_path);
            }

            // Store new document
            $path = $request->file('proof_document')->store('payment-proofs', 'public');

            $payment->update(['proof_document_path' => $path]);

            return Success::response([
                'message' => __('Proof document uploaded successfully'),
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to upload proof document'));
        }
    }

    /**
     * Get all payments history
     */
    public function history()
    {
        return view('multitenancycore::admin.payments.history');
    }

    /**
     * Get payments history for DataTables
     */
    public function historyAjax(Request $request)
    {
        $query = Payment::with(['tenant', 'subscription.plan', 'approvedBy']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('tenant_info', function ($payment) {
                return view('multitenancycore::admin.payments._tenant-info', compact('payment'))->render();
            })
            ->addColumn('amount_display', function ($payment) {
                return $payment->formatted_amount;
            })
            ->addColumn('payment_info', function ($payment) {
                return view('multitenancycore::admin.payments._payment-info', compact('payment'))->render();
            })
            ->addColumn('status_display', function ($payment) {
                return view('multitenancycore::admin.payments._status', compact('payment'))->render();
            })
            ->addColumn('approved_by_display', function ($payment) {
                if ($payment->approvedBy) {
                    return view('components.datatable-user', ['user' => $payment->approvedBy])->render();
                }

                return '-';
            })
            ->addColumn('actions', function ($payment) {
                return view('components.datatable-actions', [
                    'id' => $payment->id,
                    'actions' => [
                        [
                            'label' => __('View'),
                            'icon' => 'bx bx-show',
                            'url' => route('multitenancycore.admin.payments.show', $payment->id),
                        ],
                    ],
                ])->render();
            })
            ->rawColumns(['tenant_info', 'payment_info', 'status_display', 'approved_by_display', 'actions'])
            ->make(true);
    }

    /**
     * View payment proof document
     */
    public function viewProof(Payment $payment)
    {
        if (! $payment->proof_document_path || ! Storage::disk('public')->exists($payment->proof_document_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($payment->proof_document_path));
    }
}
