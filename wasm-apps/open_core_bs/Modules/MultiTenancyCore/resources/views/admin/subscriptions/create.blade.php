@extends('layouts.layoutMaster')

@section('title', __('Create Subscription'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
    <x-breadcrumb 
        :title="__('Create Subscription')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Subscriptions'), 'url' => route('multitenancycore.admin.subscriptions.index')],
            ['name' => __('Create'), 'url' => '']
        ]" 
    />

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Create Manual Subscription') }}</h5>
                </div>
                <div class="card-body">
                    <form id="createSubscriptionForm" action="{{ route('multitenancycore.admin.subscriptions.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="tenant_id">{{ __('Tenant') }}</label>
                                <select class="form-select select2" id="tenant_id" name="tenant_id" required>
                                    <option value="">{{ __('Select Tenant') }}</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}">
                                            {{ $tenant->name }} ({{ $tenant->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="plan_id">{{ __('Plan') }}</label>
                                <select class="form-select select2" id="plan_id" name="plan_id" required>
                                    <option value="">{{ __('Select Plan') }}</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" 
                                                data-price="{{ $plan->price }}"
                                                data-period="{{ $plan->billing_period }}">
                                            {{ $plan->name }} - {{ $plan->formatted_price }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="status">{{ __('Status') }}</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="trial">{{ __('Trial') }}</option>
                                    <option value="active" selected>{{ __('Active') }}</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="starts_at">{{ __('Start Date') }}</label>
                                <input type="text" class="form-control flatpickr-date" id="starts_at" name="starts_at" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="ends_at">{{ __('End Date') }}</label>
                                <input type="text" class="form-control flatpickr-date" id="ends_at" name="ends_at">
                                <small class="text-muted">{{ __('Leave empty for lifetime subscription') }}</small>
                            </div>
                        </div>

                        <div class="alert alert-info" id="plan_info" style="display: none;">
                            <h6 class="alert-heading mb-1">{{ __('Plan Information') }}</h6>
                            <div id="plan_details"></div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="btn btn-primary me-2">{{ __('Create Subscription') }}</button>
                            <a href="{{ route('multitenancycore.admin.subscriptions.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
const pageData = {
    labels: {
        success: @json(__('Success!')),
        error: @json(__('Error!')),
        subscriptionCreated: @json(__('Subscription created successfully')),
        price: @json(__('Price:')),
        trialDays: @json(__('Trial Days:')),
        perMonth: @json(__('per month')),
        perYear: @json(__('per year')),
        oneTime: @json(__('one-time'))
    },
    urls: {
        subscriptionsIndex: @json(route('multitenancycore.admin.subscriptions.index'))
    },
    trialDays: {{ $trialDays }}
};
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin-subscription-create.js'])
@endsection