@extends('layouts.layoutMaster')

@section('title', __('Payment Approval Queue'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
    <x-breadcrumb 
        :title="__('Payment Approval Queue')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Payments'), 'url' => route('multitenancycore.admin.payments.history')],
            ['name' => __('Approval Queue'), 'url' => '']
        ]" 
    />

    <div class="row mb-4">
        <!-- Statistics Cards -->
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">{{ __('Pending Payments') }}</h6>
                    <h3 class="mb-0" id="pending_count">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">{{ __('Pending Amount') }}</h6>
                    <h3 class="mb-0" id="pending_amount">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format(0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">{{ __('Approved Today') }}</h6>
                    <h3 class="mb-0" id="approved_today">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">{{ __('This Month') }}</h6>
                    <h3 class="mb-0" id="approved_month">0</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Pending Payment Approvals') }}</h5>
        </div>
        
        <div class="card-datatable table-responsive">
            <table class="dt-payment-approvals table">
                <thead>
                    <tr>
                        <th>{{ __('Tenant') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Payment Info') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Proof') }}</th>
                        <th>{{ __('Submitted') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('page-script')
<script>
    // Page data
    window.pageData = {
        urls: {
            datatable: '{{ route('multitenancycore.admin.payments.approval-queue.datatable') }}',
            approve: '{{ route('multitenancycore.admin.payments.approve', ':id') }}',
            reject: '{{ route('multitenancycore.admin.payments.reject', ':id') }}',
            statistics: '{{ route('multitenancycore.admin.payments.statistics') }}'
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
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/payments-approval-queue.js'])
@endsection