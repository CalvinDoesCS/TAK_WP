@extends('layouts.layoutMaster')

@section('title', __('Billing & Payments'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/jquery/jquery.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Billing & Payments') }}</h2>
                    <p class="text-muted">{{ __('View your payment history and manage pending transactions') }}</p>
                    <a href="{{ route('multitenancycore.tenant.invoices') }}" class="btn btn-sm btn-label-primary">
                        <i class="bx bx-receipt me-1"></i>{{ __('View Invoices') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ __('Total Paid') }}</h6>
                                <h3 class="mb-0">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($totalPaid) }}</h3>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="bx bx-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ __('Pending Payments') }}</h6>
                                <h3 class="mb-0">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($pendingAmount) }}</h3>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded">
                                    <i class="bx bx-time"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ __('Total Payments') }}</h6>
                                <h3 class="mb-0">{{ $payments->total() }}</h3>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-info rounded">
                                    <i class="bx bx-receipt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment History --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Payment History') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($payments->where('status', 'pending')->count() > 0)
                            <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                                <i class="bx bx-info-circle me-2"></i>
                                <div>
                                    {{ __('Pending payments will generate invoices once approved by the administrator. You can upload payment proof to expedite approval.') }}
                                </div>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table" id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Payment ID') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $payment->created_at->format('M d, Y') }}</td>
                                            <td>#{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
                                            <td>{{ $payment->description ?? __('Subscription payment') }}</td>
                                            <td>{{ $payment->formatted_amount }}</td>
                                            <td>
                                                @if($payment->status === 'approved' || $payment->status === 'completed')
                                                    <span class="badge bg-label-success">{{ __('Paid') }}</span>
                                                @elseif($payment->status === 'pending')
                                                    <span class="badge bg-label-warning">{{ __('Pending') }}</span>
                                                @elseif($payment->status === 'failed' || $payment->status === 'rejected')
                                                    <span class="badge bg-label-danger">{{ __('Failed') }}</span>
                                                @elseif($payment->status === 'cancelled')
                                                    <span class="badge bg-label-secondary">{{ __('Cancelled') }}</span>
                                                @else
                                                    <span class="badge bg-label-secondary">{{ ucfirst($payment->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn btn-sm btn-icon btn-label-secondary rounded-pill" data-bs-toggle="dropdown">
                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:void(0);" onclick="viewPaymentDetails({{ $payment->id }})">
                                                                <i class="bx bx-show me-2"></i>{{ __('View Details') }}
                                                            </a>
                                                        </li>
                                                        @if($payment->status === 'pending' && !$payment->proof_document_path)
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('multitenancycore.tenant.payment.instructions', $payment->id) }}">
                                                                    <i class="bx bx-info-circle me-2"></i>{{ __('View Instructions') }}
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if($payment->proof_document_path)
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('multitenancycore.tenant.payment.proof', $payment->id) }}" target="_blank" rel="noopener noreferrer">
                                                                    <i class="bx bx-file me-2"></i>{{ __('View Proof') }}
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if($payment->invoice_number)
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('multitenancycore.tenant.invoices.show', $payment->id) }}">
                                                                    <i class="bx bx-show me-2"></i>{{ __('View Invoice') }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('multitenancycore.tenant.invoices.download', $payment->id) }}">
                                                                    <i class="bx bx-download me-2"></i>{{ __('Download Invoice') }}
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- Pagination --}}
                        <div class="mt-4">
                            {{ $payments->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{-- Payment Details Modal --}}
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Payment Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="paymentDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
    <script>
        const pageData = {
            paymentDetailsUrl: "{{ route('multitenancycore.tenant.payment.details', ':id') }}",
            paymentProofUrl: "{{ route('multitenancycore.tenant.payment.proof', ':id') }}",
            translations: {
                paymentInformation: "{{ __('Payment Information') }}",
                paymentId: "{{ __('Payment ID') }}",
                referenceNumber: "{{ __('Reference') }}",
                amount: "{{ __('Amount') }}",
                paymentMethod: "{{ __('Method') }}",
                status: "{{ __('Status') }}",
                createdAt: "{{ __('Created') }}",
                subscriptionDetails: "{{ __('Subscription Details') }}",
                description: "{{ __('Description') }}",
                plan: "{{ __('Plan') }}",
                billingPeriod: "{{ __('Billing Period') }}",
                approvedAt: "{{ __('Approved') }}",
                rejectionReason: "{{ __('Rejection Reason') }}",
                viewProof: "{{ __('View Proof Document') }}",
                errorLoading: "{{ __('Error loading payment details') }}",
                loading: "{{ __('Loading...') }}",
                paid: "{{ __('Paid') }}",
                approved: "{{ __('Paid') }}",
                completed: "{{ __('Completed') }}",
                pending: "{{ __('Pending') }}",
                failed: "{{ __('Failed') }}",
                rejected: "{{ __('Rejected') }}",
                cancelled: "{{ __('Cancelled') }}"
            }
        };
    </script>
    @vite(['Modules/MultiTenancyCore/resources/assets/js/tenant/billing.js'])
@endsection