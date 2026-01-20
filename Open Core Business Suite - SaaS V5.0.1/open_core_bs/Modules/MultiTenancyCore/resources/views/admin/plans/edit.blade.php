@extends('layouts.layoutMaster')

@section('title', __('Edit Plan'))

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

@section('content')
    <x-breadcrumb
        :title="__('Edit Plan')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Plans'), 'url' => route('multitenancycore.admin.plans.index')],
            ['name' => __('Edit'), 'url' => '']
        ]"
    />

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('Edit Plan') }}: {{ $plan->name }}</h5>
            @if($plan->subscriptions_count > 0)
                <span class="badge bg-label-warning">
                    <i class="bx bx-info-circle"></i>
                    {{ __(':count active subscriptions', ['count' => $plan->subscriptions_count]) }}
                </span>
            @endif
        </div>
        <div class="card-body">
            @include('multitenancycore::admin.plans._form', [
                'plan' => $plan,
                'coreModules' => $coreModules,
                'addonModules' => $addonModules,
                'formAction' => route('multitenancycore.admin.plans.update', $plan)
            ])
        </div>
    </div>
@endsection

@section('page-script')
<script>
    window.pageData = {
        routes: {
            indexUrl: '{{ route('multitenancycore.admin.plans.index') }}'
        },
        translations: {
            success: @json(__('Success')),
            error: @json(__('Error')),
            errorOccurred: @json(__('An error occurred'))
        }
    };
</script>
@vite(['Modules/MultiTenancyCore/resources/assets/js/admin/plans-form.js'])
@endsection
