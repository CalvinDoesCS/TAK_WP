@php
    $title = __('Addon Management');
@endphp

@section('title', $title)

{{-- Vendor Styles --}}
@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss'
    ])
@endsection

{{-- Vendor Scripts --}}
@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/addons.js'])
@endsection

@extends('layouts/layoutMaster')

@section('content')
    {{-- Breadcrumbs --}}
    <x-breadcrumb
        :title="$title"
        :breadcrumbs="[
            ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Settings'), 'url' => '#'],
            ['name' => __('Addons')]
        ]"
        :homeUrl="route('dashboard')"
    >
    </x-breadcrumb>

    {{-- Statistics Cards Row --}}
    <div class="row g-4 mb-4">
        {{-- Total Modules Card --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">{{ __('Total Modules') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h3 class="mb-0 me-2" id="total-modules">0</h3>
                            </div>
                            <small class="text-muted">{{ __('Installed modules') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class='bx bx-package bx-md'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Modules Card --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">{{ __('Active Modules') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h3 class="mb-0 me-2" id="active-modules">0</h3>
                            </div>
                            <small class="text-muted">{{ __('Currently enabled') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class='bx bx-check-circle bx-md'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Categories Card --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">{{ __('Categories') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h3 class="mb-0 me-2" id="total-categories">0</h3>
                            </div>
                            <small class="text-muted">{{ __('Module categories') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class='bx bx-category bx-md'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Disabled Modules Card --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted">{{ __('Disabled Modules') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h3 class="mb-0 me-2" id="disabled-modules">0</h3>
                            </div>
                            <small class="text-muted">{{ __('Currently disabled') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-secondary">
                                <i class='bx bx-x-circle bx-md'></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Upload Section (Collapsible) --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Upload New Addon') }}</h5>
            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#uploadSection">
                <i class='bx bx-plus me-1'></i>{{ __('Add New Addon') }}
            </button>
        </div>
        <div class="collapse" id="uploadSection">
            <div class="card-body">
                <form action="{{ route('module.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="moduleFile" class="form-label">{{ __('Select addon zip file') }}</label>
                        <input type="file" name="module" id="moduleFile" class="form-control" accept=".zip" required>
                        <div class="form-text">{{ __('Upload a zip file containing the module files') }}</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-upload me-1'></i>{{ __('Upload') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Demo Mode Alert --}}
    @if(env('APP_DEMO'))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <strong>{{ __('Demo Mode:') }}</strong> {{ __('Some features are disabled in demo mode. Purchase addons are available at') }} <a href="https://czappstudio.com" target="_blank" class="alert-link">czappstudio.com</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- DataTable Section --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Installed Modules') }}</h5>
            <div class="d-flex gap-2">
                {{-- Category Filter --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="categoryFilterDropdown" data-bs-toggle="dropdown">
                        <i class='bx bx-filter me-1'></i><span id="categoryFilterLabel">{{ __('All Categories') }}</span>
                    </button>
                    <ul class="dropdown-menu" id="categoryFilterMenu">
                        <li><a class="dropdown-item category-filter-item active" href="javascript:void(0);" data-category="">{{ __('All Categories') }}</a></li>
                    </ul>
                </div>

                {{-- Status Filter --}}
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown">
                        <i class='bx bx-stats me-1'></i><span id="statusFilterLabel">{{ __('All Status') }}</span>
                    </button>
                    <ul class="dropdown-menu" id="statusFilterMenu">
                        <li><a class="dropdown-item status-filter-item active" href="javascript:void(0);" data-status="">{{ __('All Status') }}</a></li>
                        <li><a class="dropdown-item status-filter-item" href="javascript:void(0);" data-status="enabled">{{ __('Enabled') }}</a></li>
                        <li><a class="dropdown-item status-filter-item" href="javascript:void(0);" data-status="disabled">{{ __('Disabled') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="addons-table" class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Module') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Version') }}</th>
                        <th>{{ __('Dependencies') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Marketplace Section --}}
    <div class="text-center mt-4">
        <a href="https://czappstudio.com/open-core-bs-addons/" target="_blank" class="btn btn-primary">
            <i class='bx bx-store me-1'></i>{{ __('Browse More Addons') }}
        </a>
    </div>

    {{-- Include Module Details Offcanvas --}}
    @include('addons._module-details-offcanvas')

    {{-- Page Data for JavaScript --}}
    <script>
        const pageData = {
            urls: {
                datatable: @json(route('addons.ajax')),
                statistics: @json(route('addons.statistics')),
                enable: @json(route('module.activate')),
                disable: @json(route('module.deactivate')),
                show: @json(url('/addons/:module')),
                checkDependencies: @json(url('/addons/:module/check')),
                uninstall: @json(route('module.uninstall')),
            },
            labels: {
                // General
                confirmTitle: @json(__('Are you sure?')),
                confirmButtonText: @json(__('Yes, proceed!')),
                cancelButtonText: @json(__('Cancel')),
                success: @json(__('Success!')),
                error: @json(__('Error!')),

                // Enable/Disable
                enableConfirm: @json(__('Do you want to enable this module?')),
                disableConfirm: @json(__('Do you want to disable this module?')),
                enableSuccess: @json(__('Module enabled successfully')),
                disableSuccess: @json(__('Module disabled successfully')),

                // Uninstall
                uninstallConfirm: @json(__('You are about to uninstall this module. This action cannot be undone!')),
                uninstallSuccess: @json(__('Module uninstalled successfully')),
                uninstallWarning: @json(__('Warning: This will permanently remove the module')),

                // Dependencies
                dependenciesRequired: @json(__('Dependencies Required')),
                dependenciesMissing: @json(__('This module requires the following modules to be enabled:')),
                hasDependents: @json(__('Cannot Disable')),
                dependentsActive: @json(__('The following modules depend on this module and must be disabled first:')),

                // Status
                enabled: @json(__('Enabled')),
                disabled: @json(__('Disabled')),

                // Categories
                allCategories: @json(__('All Categories')),

                // Demo mode
                demoModeRestriction: @json(__('This feature is disabled in demo mode'))
            },
            isDemoMode: {{ env('APP_DEMO') ? 'true' : 'false' }}
        };
    </script>
@endsection
