@extends('layouts.layoutMaster')

@section('title', __('Payment History'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ])
@endsection

@section('content')
    <x-breadcrumb 
        :title="__('Payment History')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Payments'), 'url' => ''],
            ['name' => __('History'), 'url' => '']
        ]" 
    />

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">{{ __('Payment History') }}</h5>
            <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
                <div class="col-md-2 payment_status"></div>
                <div class="col-md-2 payment_method"></div>
                <div class="col-md-2">
                    <input type="text" class="form-control flatpickr-date" id="date_from" placeholder="{{ __('From Date') }}">
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control flatpickr-date" id="date_to" placeholder="{{ __('To Date') }}">
                </div>
                <div class="col-md-4">
                    <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start">
                        <div class="dt-buttons">
                            <a href="{{ route('multitenancycore.admin.payments.approval-queue') }}" class="dt-button buttons-collection btn btn-label-warning me-2">
                                <span><i class="bx bx-time-five me-1"></i>{{ __('Approval Queue') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-datatable table-responsive">
            <table class="dt-payment-history table">
                <thead>
                    <tr>
                        <th>{{ __('Tenant') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Payment Info') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Approved By') }}</th>
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
            datatable: '{{ route('multitenancycore.admin.payments.history.datatable') }}'
        },
        labels: {
            allStatus: @json(__('All Status')),
            pending: @json(__('Pending')),
            approved: @json(__('Approved')),
            completed: @json(__('Completed')),
            rejected: @json(__('Rejected')),
            failed: @json(__('Failed')),
            cancelled: @json(__('Cancelled')),
            allMethods: @json(__('All Methods')),
            offline: @json(__('Offline')),
            stripe: @json(__('Stripe')),
            paypal: @json(__('PayPal')),
            razorpay: @json(__('Razorpay'))
        }
    };
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/payments-history.js'])
@endsection