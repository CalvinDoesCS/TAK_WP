@extends('layouts.layoutMaster')

@section('title', __('Plan Management'))

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
        :title="__('Plan Management')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Plans'), 'url' => '']
        ]" 
    />

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">{{ __('Subscription Plans') }}</h5>
            <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <div class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start">
                        <div class="dt-buttons">
                            <a href="{{ route('multitenancycore.admin.plans.create') }}" class="dt-button buttons-collection btn btn-label-primary me-2">
                                <span><i class="bx bx-plus me-1"></i>{{ __('Create Plan') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-datatable table-responsive">
            <table class="dt-plans table">
                <thead>
                    <tr>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Price') }}</th>
                        <th>{{ __('Restrictions') }}</th>
                        <th>{{ __('Subscribers') }}</th>
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
            datatable: '{{ route('multitenancycore.admin.plans.datatable') }}',
            edit: '{{ route('multitenancycore.admin.plans.edit', ':id') }}',
            destroy: '{{ route('multitenancycore.admin.plans.destroy', ':id') }}'
        },
        labels: {
            confirmDelete: @json(__('Are you sure you want to delete this plan?')),
            success: @json(__('Success!')),
            error: @json(__('Error!')),
            deleted: @json(__('Plan deleted successfully')),
            yesDelete: @json(__('Yes, Delete')),
            cancel: @json(__('Cancel'))
        }
    };
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/plans-index.js'])
@endsection

