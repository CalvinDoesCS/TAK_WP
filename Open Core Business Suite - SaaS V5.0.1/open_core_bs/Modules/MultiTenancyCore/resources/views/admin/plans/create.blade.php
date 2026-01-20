@extends('layouts.layoutMaster')

@section('title', __('Create Plan'))

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
        :title="__('Create Plan')"
        :breadcrumbs="[
            ['name' => __('Multitenancy'), 'url' => route('multitenancycore.admin.dashboard')],
            ['name' => __('Plans'), 'url' => route('multitenancycore.admin.plans.index')],
            ['name' => __('Create'), 'url' => '']
        ]"
    />

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Create New Plan') }}</h5>
        </div>
        <div class="card-body">
            @include('multitenancycore::admin.plans._form', [
                'plan' => new \Modules\MultiTenancyCore\App\Models\Plan(),
                'coreModules' => $coreModules,
                'addonModules' => $addonModules,
                'formAction' => route('multitenancycore.admin.plans.store')
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
