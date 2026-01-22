@extends('layouts.layoutMaster')

@section('title', __('Approval Pipeline Report'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
      'resources/assets/vendor/libs/select2/select2.scss',
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
      'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/moment/moment.js',
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    ])
@endsection

@section('page-script')
    @vite([
      'resources/assets/js/app/reports-approval-pipeline.js',
    ])
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- Breadcrumb --}}
        <x-breadcrumb
            :title="__('Approval Pipeline Report')"
            :breadcrumbs="[
                ['name' => __('Expenses'), 'url' => route('expenses.index')],
                ['name' => __('Expense Management'), 'url' => ''],
                ['name' => __('Approval Pipeline'), 'url' => '']
            ]"
            :home-url="url('/')"
        />

        {{-- Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded">
                                    <i class="bx bx-time-five bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-0" id="totalPending">0</h3>
                                <small class="text-muted">{{ __('Total Pending') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-info rounded">
                                    <i class="bx bx-calendar bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-0" id="avgDaysPending">0</h3>
                                <small class="text-muted">{{ __('Average Days Pending') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-danger rounded">
                                    <i class="bx bx-error-circle bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-0" id="over7Days">0</h3>
                                <small class="text-muted">{{ __('Requests > 7 Days Old') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="bx bx-trending-up bx-sm"></i>
                                </div>
                            </div>
                            <div class="text-end">
                                <h3 class="mb-0" id="approvalRate">0%</h3>
                                <small class="text-muted">{{ __('Current Approval Rate') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Status Distribution') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="statusDistributionChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Pending Requests by Approver') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="pendingByApproverChart"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-filter-alt"></i> {{ __('Filters') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="dateFrom" class="form-label">{{ __('Date From') }}</label>
                        <input type="text" id="dateFrom" name="date_from" class="form-control" placeholder="{{ __('Select date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="dateTo" class="form-label">{{ __('Date To') }}</label>
                        <input type="text" id="dateTo" name="date_to" class="form-control" placeholder="{{ __('Select date') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="statusFilter" class="form-label">{{ __('Status') }}</label>
                        <select id="statusFilter" name="status" class="form-select">
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="agingFilter" class="form-label">{{ __('Aging') }}</label>
                        <select id="agingFilter" name="aging" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="less_7">{{ __('< 7 days') }}</option>
                            <option value="7_14">{{ __('7-14 days') }}</option>
                            <option value="14_30">{{ __('14-30 days') }}</option>
                            <option value="over_30">{{ __('> 30 days') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="approverFilter" class="form-label">{{ __('Approver') }}</label>
                        <select id="approverFilter" name="approver_id" class="form-select select2">
                            <option value="">{{ __('All Approvers') }}</option>
                            @foreach($approvers as $approver)
                                <option value="{{ $approver->id }}">{{ $approver->getFullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="filterBtn">
                            <i class="bx bx-filter-alt me-1"></i> {{ __('Apply Filters') }}
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" id="resetBtn">
                            <i class="bx bx-refresh me-1"></i> {{ __('Clear Filters') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Approval Pipeline Details') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="approvalPipelineTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Request ID') }}</th>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Expense Type') }}</th>
                                <th>{{ __('Submitted Date') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Days Pending') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Assigned Approver') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Data for JavaScript --}}
    <script>
        const pageData = {
            urls: {
                datatable: @json(route('expenses.reports.approval-pipeline.ajax')),
                statistics: @json(route('expenses.reports.approval-pipeline.statistics')),
                view: @json(route('expenses.index'))
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
                pending: @json(__('Pending')),
                approved: @json(__('Approved')),
                rejected: @json(__('Rejected')),
                cancelled: @json(__('Cancelled')),
                notAssigned: @json(__('Not Assigned')),
                count: @json(__('Count')),
                noData: @json(__('No data available'))
            },
            // Default date range (last 30 days)
            defaultDateFrom: @json(now()->subMonth()->format('Y-m-d')),
            defaultDateTo: @json(now()->format('Y-m-d'))
        };
    </script>
@endsection
