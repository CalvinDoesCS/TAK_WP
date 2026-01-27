@extends('layouts.layoutMaster')

@section('title', __('Tenant Management'))

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
        :title="__('Tenant Management')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Tenants'), 'url' => '']
        ]" 
    />

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">{{ __('Tenant Management') }}</h5>
            <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
                <div class="col-md-4 tenant_status"></div>
                <div class="col-md-4 tenant_plan"></div>
                <div class="col-md-4">
                    <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start">
                        <div class="dt-buttons">
                            <a href="{{ route('multitenancycore.admin.tenants.approval-queue') }}" class="dt-button buttons-collection btn btn-label-warning me-2">
                                <span><i class="bx bx-time-five me-1"></i>{{ __('Approval Queue') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-datatable table-responsive">
            <table class="dt-tenants table">
                <thead>
                    <tr>
                        <th>{{ __('Company') }}</th>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Database') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Edit Tenant Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="editTenantOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">{{ __('Edit Tenant') }}</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form id="editTenantForm">
                <input type="hidden" name="tenant_id" id="edit_tenant_id">
                
                <div class="mb-4">
                    <label class="form-label" for="edit_name">{{ __('Company Name') }}</label>
                    <input type="text" class="form-control" id="edit_name" name="name" required>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="edit_email">{{ __('Email') }}</label>
                    <input type="email" class="form-control" id="edit_email" name="email" required>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="edit_phone">{{ __('Phone') }}</label>
                    <input type="text" class="form-control" id="edit_phone" name="phone">
                </div>

                <div class="mb-4">
                    <label class="form-label" for="edit_status">{{ __('Status') }}</label>
                    <select class="form-select" id="edit_status" name="status">
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="suspended">{{ __('Suspended') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="edit_notes">{{ __('Notes') }}</label>
                    <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">{{ __('Save Changes') }}</button>
                    <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-script')
<script>
    // Page data
    window.pageData = {
        urls: {
            datatable: '{{ route('multitenancycore.admin.tenants.datatable') }}',
            show: '{{ route('multitenancycore.admin.tenants.show', ':id') }}',
            edit: '{{ route('multitenancycore.admin.tenants.edit', ':id') }}',
            update: '{{ route('multitenancycore.admin.tenants.update', ':id') }}',
            approve: '{{ route('multitenancycore.admin.tenants.approve', ':id') }}',
            reject: '{{ route('multitenancycore.admin.tenants.reject', ':id') }}',
            suspend: '{{ route('multitenancycore.admin.tenants.suspend', ':id') }}',
            activate: '{{ route('multitenancycore.admin.tenants.activate', ':id') }}'
        },
        labels: {
            confirmApprove: @json(__('Are you sure you want to approve this tenant?')),
            confirmReject: @json(__('Are you sure you want to reject this tenant?')),
            confirmSuspend: @json(__('Are you sure you want to suspend this tenant?')),
            confirmActivate: @json(__('Are you sure you want to activate this tenant?')),
            rejectReason: @json(__('Please provide a reason for rejection:')),
            success: @json(__('Success!')),
            error: @json(__('Error!')),
            approved: @json(__('Tenant approved successfully')),
            rejected: @json(__('Tenant rejected successfully')),
            suspended: @json(__('Tenant suspended successfully')),
            activated: @json(__('Tenant activated successfully')),
            updated: @json(__('Tenant updated successfully')),
            allStatus: @json(__('All Status')),
            pending: @json(__('Pending')),
            approved: @json(__('Approved')),
            active: @json(__('Active')),
            suspended: @json(__('Suspended')),
            cancelled: @json(__('Cancelled')),
            allPlans: @json(__('All Plans')),
            yesApprove: @json(__('Yes, Approve')),
            yesReject: @json(__('Yes, Reject')),
            yesSuspend: @json(__('Yes, Suspend')),
            yesActivate: @json(__('Yes, Activate')),
            cancel: @json(__('Cancel')),
            provideReason: @json(__('Please provide a reason'))
        },
        plans: @json(\Modules\MultiTenancyCore\App\Models\Plan::active()->get(['id', 'name']))
    };
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/tenants-index.js'])
@endsection