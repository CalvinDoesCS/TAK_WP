@extends('layouts.layoutMaster')

@section('title', __('Tenant Approval Queue'))

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
        :title="__('Tenant Approval Queue')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Tenants'), 'url' => route('multitenancycore.admin.tenants.index')],
            ['name' => __('Approval Queue'), 'url' => '']
        ]" 
    />

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Pending Tenant Approvals') }}</h5>
        </div>
        
        <div class="card-datatable table-responsive">
            <table class="dt-approval-queue table">
                <thead>
                    <tr>
                        <th>{{ __('Company') }}</th>
                        <th>{{ __('Requested Plan') }}</th>
                        <th>{{ __('Submitted At') }}</th>
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
            datatable: '{{ route('multitenancycore.admin.tenants.approval-queue.datatable') }}',
            approve: '{{ route('multitenancycore.admin.tenants.approve', ':id') }}',
            reject: '{{ route('multitenancycore.admin.tenants.reject', ':id') }}'
        },
        labels: {
            confirmApprove: @json(__('Are you sure you want to approve this tenant?')),
            confirmReject: @json(__('Are you sure you want to reject this tenant?')),
            rejectReason: @json(__('Please provide a reason for rejection:')),
            success: @json(__('Success!')),
            error: @json(__('Error!')),
            approved: @json(__('Tenant approved successfully')),
            rejected: @json(__('Tenant rejected successfully')),
            yesApprove: @json(__('Yes, Approve')),
            yesReject: @json(__('Yes, Reject')),
            cancel: @json(__('Cancel')),
            provideReason: @json(__('Please provide a reason'))
        }
    };
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/tenants-approval-queue.js'])
@endsection