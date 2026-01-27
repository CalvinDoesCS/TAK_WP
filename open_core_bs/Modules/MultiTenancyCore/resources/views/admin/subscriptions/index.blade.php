@extends('layouts.layoutMaster')

@section('title', __('Subscription Management'))

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
        :title="__('Subscription Management')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Subscriptions'), 'url' => '']
        ]" 
    />

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">{{ __('Subscription Management') }}</h5>
            <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
                <div class="col-md-3 subscription_status"></div>
                <div class="col-md-3 subscription_plan"></div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="expiring_soon">
                        <label class="form-check-label" for="expiring_soon">
                            {{ __('Expiring Soon') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-datatable table-responsive">
            <table class="dt-subscriptions table">
                <thead>
                    <tr>
                        <th>{{ __('Tenant') }}</th>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Period') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Change Plan Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="changePlanOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">{{ __('Change Subscription Plan') }}</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form id="changePlanForm">
                <input type="hidden" name="subscription_id" id="change_subscription_id">
                
                <div class="mb-4">
                    <label class="form-label">{{ __('Current Plan') }}</label>
                    <div id="current_plan_info" class="alert alert-info"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="new_plan_id">{{ __('New Plan') }}</label>
                    <select class="form-select" id="new_plan_id" name="plan_id" required>
                        <option value="">{{ __('Select Plan') }}</option>
                        @php
                            $plans = \Modules\MultiTenancyCore\App\Models\Plan::active()->get();
                        @endphp
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" data-price="{{ $plan->formatted_price }}">
                                {{ $plan->name }} - {{ $plan->formatted_price }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="immediate" name="immediate">
                        <label class="form-check-label" for="immediate">
                            {{ __('Change immediately') }}
                            <small class="text-muted d-block">{{ __('If unchecked, plan will change at end of billing period') }}</small>
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">{{ __('Change Plan') }}</button>
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
            datatable: '{{ route('multitenancycore.admin.subscriptions.datatable') }}',
            show: '{{ route('multitenancycore.admin.subscriptions.show', ':id') }}',
            cancel: '{{ route('multitenancycore.admin.subscriptions.cancel', ':id') }}',
            renew: '{{ route('multitenancycore.admin.subscriptions.renew', ':id') }}',
            changePlan: '{{ route('multitenancycore.admin.subscriptions.change-plan', ':id') }}'
        },
        labels: {
            confirmCancel: @json(__('Are you sure you want to cancel this subscription?')),
            confirmRenew: @json(__('Are you sure you want to renew this subscription?')),
            cancelNow: @json(__('Cancel immediately')),
            cancelEnd: @json(__('Cancel at end of period')),
            success: @json(__('Success!')),
            error: @json(__('Error!')),
            renewed: @json(__('Subscription renewed successfully')),
            cancelled: @json(__('Subscription cancelled successfully')),
            planChanged: @json(__('Plan changed successfully')),
            allStatus: @json(__('All Status')),
            trial: @json(__('Trial')),
            active: @json(__('Active')),
            cancelled: @json(__('Cancelled')),
            expired: @json(__('Expired')),
            allPlans: @json(__('All Plans')),
            yesRenew: @json(__('Yes, Renew')),
            cancel: @json(__('Cancel')),
            noKeepActive: @json(__('No, Keep Active'))
        },
        plans: @json(\Modules\MultiTenancyCore\App\Models\Plan::active()->get(['id', 'name']))
    };
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/subscriptions-index.js'])
@endsection