@extends('layouts.layoutMaster')

@section('title', __('Payment Details'))

@section('content')
    <x-breadcrumb 
        :title="__('Payment Details')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Payments'), 'url' => route('multitenancycore.admin.payments.history')],
            ['name' => __('Details'), 'url' => '']
        ]" 
    />

    <div class="row">
        <!-- Payment Information -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Payment Information') }}</h5>
                    
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Reference:') }}</span>
                            <code>{{ $payment->reference_number ?? __('N/A') }}</code>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Amount:') }}</span>
                            <span class="h5 text-primary">{{ $payment->formatted_amount }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Method:') }}</span>
                            <span class="badge bg-label-primary">{{ ucfirst($payment->payment_method) }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Status:') }}</span>
                            @include('multitenancycore::admin.payments._status', ['payment' => $payment])
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Created At:') }}</span>
                            <span>{{ $payment->created_at->format('Y-m-d H:i:s') }}</span>
                        </li>
                        @if($payment->approved_at)
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Approved At:') }}</span>
                                <span>{{ $payment->approved_at->format('Y-m-d H:i:s') }}</span>
                            </li>
                            @if($payment->approvedBy)
                                <li class="mb-3">
                                    <span class="h6 me-1">{{ __('Approved By:') }}</span>
                                    <span>{{ $payment->approvedBy->first_name }} {{ $payment->approvedBy->last_name }}</span>
                                </li>
                            @endif
                        @endif
                        @if($payment->rejected_at)
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Rejected At:') }}</span>
                                <span class="text-danger">{{ $payment->rejected_at->format('Y-m-d H:i:s') }}</span>
                            </li>
                            @if($payment->rejection_reason)
                                <li class="mb-3">
                                    <span class="h6 me-1">{{ __('Rejection Reason:') }}</span>
                                    <span class="text-danger">{{ $payment->rejection_reason }}</span>
                                </li>
                            @endif
                        @endif
                    </ul>

                    @if($payment->metadata)
                        <hr>
                        <h6>{{ __('Additional Information') }}</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($payment->metadata as $key => $value)
                                @if(!in_array($key, ['approval_notes']))
                                    <li class="mb-2">
                                        <span class="text-muted">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                        <span>{{ $value }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tenant & Subscription Information -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Tenant Information') }}</h5>
                    
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Company:') }}</span>
                            <a href="{{ route('multitenancycore.admin.tenants.show', $payment->tenant_id) }}">
                                {{ $payment->tenant->name }}
                            </a>
                        </li>
                        <li class="mb-3">
                            <span class="h6 me-1">{{ __('Email:') }}</span>
                            <span>{{ $payment->tenant->email }}</span>
                        </li>
                        @if($payment->tenant->phone)
                            <li class="mb-3">
                                <span class="h6 me-1">{{ __('Phone:') }}</span>
                                <span>{{ $payment->tenant->phone }}</span>
                            </li>
                        @endif
                    </ul>

                    @if($payment->subscription)
                        <hr>
                        <h6>{{ __('Subscription Information') }}</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="text-muted">{{ __('Plan:') }}</span>
                                <span class="badge bg-label-primary">{{ $payment->subscription->plan->name }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="text-muted">{{ __('Period:') }}</span>
                                <span>{{ $payment->subscription->starts_at->format('Y-m-d') }} - {{ $payment->subscription->ends_at ? $payment->subscription->ends_at->format('Y-m-d') : __('Lifetime') }}</span>
                            </li>
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Proof Document -->
            @if($payment->proof_document_path)
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Payment Proof') }}</h5>

                        <div class="d-grid">
                            <a href="{{ route('multitenancycore.admin.payments.proof', $payment->id) }}" target="_blank" class="btn btn-primary">
                                <i class="bx bx-file me-1"></i>{{ __('View Proof Document') }}
                            </a>
                        </div>

                        @if($payment->proof_document_path && strpos($payment->proof_document_path, '.pdf') === false)
                            <div class="mt-3">
                                <img src="{{ route('multitenancycore.admin.payments.proof', $payment->id) }}" class="img-fluid rounded" alt="{{ __('Payment Proof') }}">
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    @if($payment->isPending())
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Actions') }}</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" onclick="approvePayment({{ $payment->id }})">
                                <i class="bx bx-check me-1"></i>{{ __('Approve Payment') }}
                            </button>
                            <button class="btn btn-danger" onclick="rejectPayment({{ $payment->id }})">
                                <i class="bx bx-x me-1"></i>{{ __('Reject Payment') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
<script>
    // Page data
    window.pageData = {
        urls: {
            approve: '{{ route('multitenancycore.admin.payments.approve', $payment->id) }}',
            reject: '{{ route('multitenancycore.admin.payments.reject', $payment->id) }}'
        },
        labels: {
            confirmApprove: @json(__('Are you sure you want to approve this payment?')),
            confirmReject: @json(__('Are you sure you want to reject this payment?')),
            rejectReason: @json(__('Please provide a reason for rejection:')),
            activateSubscription: @json(__('Activate subscription?')),
            approveNotes: @json(__('Approval notes (optional):')),
            success: @json(__('Success!')),
            error: @json(__('Error!')),
            approved: @json(__('Payment approved successfully')),
            rejected: @json(__('Payment rejected successfully')),
            yesApprove: @json(__('Yes, Approve')),
            yesReject: @json(__('Yes, Reject')),
            cancel: @json(__('Cancel')),
            provideReason: @json(__('Please provide a reason'))
        }
    };
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/payments-show.js'])
@endsection