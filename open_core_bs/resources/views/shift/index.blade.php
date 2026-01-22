@extends('layouts/layoutMaster')

@section('title', __('Shifts Management'))

<!-- Vendor Styles -->
@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/app/shifts.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb --}}
    <x-breadcrumb
        :title="__('Shifts Management')"
        :breadcrumbs="[
            ['name' => __('Attendance'), 'url' => ''],
            ['name' => __('Shifts'), 'url' => '']
        ]"
        :home-url="url('/')"
    />

    {{-- Shifts Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">{{ __('Shift List') }}</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddOrUpdateShift">
                <i class="bx bx-plus me-1"></i>{{ __('Add New Shift') }}
            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table id="shiftsTable" class="table">
                <thead>
                    <tr>
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Shift Name') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Timing') }}</th>
                        <th>{{ __('Working Days') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Include the Offcanvas partial --}}
@include('shift._form')

{{-- Page Data for JavaScript --}}
<script>
    const pageData = {
        urls: {
            datatable: @json(route('shifts.indexAjax')),
            store: @json(route('shifts.store')),
            edit: @json(url('shifts')),
            destroy: @json(url('shifts')),
            toggleStatus: @json(url('shifts'))
        },
        labels: {
            search: @json(__('Search')),
            processing: @json(__('Processing...')),
            lengthMenu: @json(__('Show _MENU_ entries')),
            info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
            infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
            emptyTable: @json(__('No data available')),
            paginate: {
                first: @json(__('First')),
                last: @json(__('Last')),
                next: @json(__('Next')),
                previous: @json(__('Previous'))
            },
            confirmDelete: @json(__('Are you sure?')),
            confirmDeleteText: @json(__('You will not be able to recover this shift!')),
            confirmDeleteButton: @json(__('Yes, delete it!')),
            cancelButton: @json(__('Cancel')),
            deleted: @json(__('Deleted!')),
            deletedText: @json(__('The shift has been deleted.')),
            success: @json(__('Success!')),
            error: @json(__('Error!')),
            validationError: @json(__('Validation failed. Please check the form.'))
        }
    };
</script>
@endsection
