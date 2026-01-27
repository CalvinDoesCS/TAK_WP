@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Holiday Management'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/app/holidays.js'])
@endsection

@section('content')
    <x-breadcrumb
        :title="__('Holiday Management')"
        :breadcrumbs="[
            ['name' => __('Holidays'), 'url' => '']
        ]"
        :home-url="route('dashboard')"
    />

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Holiday Management') }}</h5>
            {{-- @can('hrcore.create-holidays') --}} {{-- PERMISSION TEMPORARILY DISABLED --}}
                <button type="button" class="btn btn-primary" id="btnAddHoliday">
                    <i class="bx bx-plus me-1"></i>{{ __('Add Holiday') }}
                </button>
            {{-- @endcan --}}
        </div>

        <div class="card-datatable table-responsive">
            <table class="datatables-holidays table border-top">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Applicable To') }}</th>
                        <th>{{ __('Properties') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Holiday Form Offcanvas --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="holidayFormOffcanvas" aria-labelledby="holidayFormOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="holidayFormOffcanvasLabel">{{ __('Add Holiday') }}</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body">
            <form id="holidayForm" class="needs-validation" novalidate>
                <input type="hidden" id="holiday_id" name="id">

                {{-- Basic Information --}}
                <div class="mb-4">
                    <h6 class="mb-3">{{ __('Basic Information') }}</h6>

                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Holiday Name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label">{{ __('Holiday Code') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required placeholder="e.g., NEW_YEAR">
                        <small class="text-muted">{{ __('Unique identifier for this holiday') }}</small>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="date" name="date" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">{{ __('Select Type') }}</option>
                                @foreach ($holidayTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">{{ __('Category') }}</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">{{ __('Select Category') }}</option>
                                @foreach ($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label">{{ __('Color') }}</label>
                        <input type="color" class="form-control form-control-color" id="color" name="color" value="#4CAF50">
                    </div>
                </div>

                {{-- Applicability --}}
                <div class="mb-4">
                    <h6 class="mb-3">{{ __('Applicability') }}</h6>

                    <div class="mb-3">
                        <label for="applicable_for" class="form-label">{{ __('Applies To') }} <span class="text-danger">*</span></label>
                        <select class="form-select" id="applicable_for" name="applicable_for" required>
                            @foreach ($applicableOptions as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="departments_container">
                        <label for="departments" class="form-label">{{ __('Select Departments') }}</label>
                        <select class="form-select select2" id="departments" name="departments[]" multiple>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="employees_container">
                        <label for="specific_employees" class="form-label">{{ __('Select Employees') }}</label>
                        <select class="form-select select2" id="specific_employees" name="specific_employees[]" multiple>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Options --}}
                <div class="mb-4">
                    <h6 class="mb-3">{{ __('Options') }}</h6>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring">
                        <label class="form-check-label" for="is_recurring">{{ __('Recurring (Annual)') }}</label>
                        <small class="d-block text-muted">{{ __('This holiday repeats every year') }}</small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_optional" name="is_optional">
                        <label class="form-check-label" for="is_optional">{{ __('Optional Holiday') }}</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_restricted" name="is_restricted">
                        <label class="form-check-label" for="is_restricted">{{ __('Restricted Holiday') }}</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_half_day" name="is_half_day">
                        <label class="form-check-label" for="is_half_day">{{ __('Half Day Holiday') }}</label>
                    </div>

                    <div class="mb-3 d-none" id="half_day_container">
                        <label for="half_day_type" class="form-label">{{ __('Half Day Type') }}</label>
                        <select class="form-select" id="half_day_type" name="half_day_type">
                            <option value="morning">{{ __('Morning') }}</option>
                            <option value="afternoon">{{ __('Afternoon') }}</option>
                        </select>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_visible_to_employees" name="is_visible_to_employees" checked>
                        <label class="form-check-label" for="is_visible_to_employees">{{ __('Visible to Employees') }}</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="send_notification" name="send_notification">
                        <label class="form-check-label" for="send_notification">{{ __('Send Notification') }}</label>
                    </div>

                    <div class="mb-3 d-none" id="notification_container">
                        <label for="notification_days_before" class="form-label">{{ __('Notify Days Before') }}</label>
                        <input type="number" class="form-control" id="notification_days_before" name="notification_days_before" min="0" max="30" value="7">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bx bx-save me-1"></i>{{ __('Save Holiday') }}
                    </button>
                    <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Pass data to JavaScript
        const pageData = {
            urls: {
                datatable: @json(route('hrcore.holidays.datatable')),
                store: @json(route('hrcore.holidays.store')),
                show: @json(url('/holidays')),
                update: @json(url('/holidays')),
                destroy: @json(url('/holidays')),
                toggleStatus: @json(url('/holidays')),
            },
            labels: {
                confirmDelete: @json(__('Are you sure you want to delete this holiday?')),
                confirmDeleteText: @json(__('This action cannot be undone')),
                yes: @json(__('Yes, delete it')),
                no: @json(__('Cancel')),
                success: @json(__('Success!')),
                error: @json(__('Error!')),
                deleted: @json(__('Holiday deleted successfully')),
                statusUpdated: @json(__('Holiday status updated successfully')),
                addHoliday: @json(__('Add Holiday')),
                editHoliday: @json(__('Edit Holiday')),
            },
            // PERMISSIONS TEMPORARILY DISABLED - ALL SET TO TRUE
            permissions: {
                canEdit: true, // @json(auth()->user()->can('hrcore.edit-holidays')),
                canDelete: true, // @json(auth()->user()->can('hrcore.delete-holidays')),
            },
        };
    </script>
@endsection
