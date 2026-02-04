@extends('layouts.layoutMaster')

@section('title', __('Reports'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('content')
  <x-breadcrumb :title="__('Reports')" :breadcrumbs="$breadcrumbs" />

  {{-- Quick Report Links --}}
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">{{ __('Income & Expense Summary') }}</h5>
          <p class="card-text text-muted">{{ __('View detailed income and expense breakdown for any period') }}</p>
          <a href="{{ route('accountingcore.reports.summary') }}" class="btn btn-primary">
            <i class="bx bx-line-chart me-1"></i> {{ __('View Report') }}
          </a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">{{ __('Cash Flow') }}</h5>
          <p class="card-text text-muted">{{ __('Track money flow and running balance over time') }}</p>
          <a href="{{ route('accountingcore.reports.cashflow') }}" class="btn btn-primary">
            <i class="bx bx-transfer me-1"></i> {{ __('View Report') }}
          </a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">{{ __('Category Performance') }}</h5>
          <p class="card-text text-muted">{{ __('Analyze income and expenses by category') }}</p>
          <a href="{{ route('accountingcore.reports.category-performance') }}" class="btn btn-primary">
            <i class="bx bx-pie-chart-alt-2 me-1"></i> {{ __('View Report') }}
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Custom Report Generator --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">{{ __('Custom Report Generator') }}</h5>
    </div>
    <div class="card-body">
      <form id="reportFilters" class="row g-3">
        <div class="col-md-3">
          <label class="form-label" for="dateRange">{{ __('Date Range') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="dateRange" name="dateRange" placeholder="{{ __('Select date range') }}" required>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="reportType">{{ __('Report Type') }}</label>
          <select class="form-select" id="reportType" name="reportType">
            <option value="summary">{{ __('Income & Expense Summary') }}</option>
            <option value="category">{{ __('Category Breakdown') }}</option>
            <option value="monthly">{{ __('Monthly Comparison') }}</option>
          </select>
        </div>
        <div class="col-md-3" style="display: none;">
          <label class="form-label" for="categoryFilter">{{ __('Category') }}</label>
          <select class="form-select" id="categoryFilter" name="categoryFilter">
            <option value="">{{ __('All Categories') }}</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}">{{ $category->name }} ({{ ucfirst($category->type) }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label d-block">&nbsp;</label>
          <button type="submit" class="btn btn-primary w-100">
            <i class="bx bx-search me-1"></i> {{ __('Generate Report') }}
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Summary Cards --}}
  <div class="row mb-4" id="summaryCards" style="display: none;">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title text-muted">{{ __('Total Income') }}</h6>
          <h3 class="mb-0 text-success" id="totalIncome">-</h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title text-muted">{{ __('Total Expenses') }}</h6>
          <h3 class="mb-0 text-danger" id="totalExpenses">-</h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title text-muted">{{ __('Net Balance') }}</h6>
          <h3 class="mb-0" id="netBalance">-</h3>
        </div>
      </div>
    </div>
  </div>

  {{-- Report Table --}}
  <div class="card" id="reportCard" style="display: none;">
    <div class="card-header">
      <h5 class="card-title mb-0" id="reportTitle">{{ __('Report Results') }}</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover" id="reportTable">
          <thead id="reportTableHead" class="table-light"></thead>
          <tbody id="reportTableBody"></tbody>
          <tfoot id="reportTableFoot"></tfoot>
        </table>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  @vite(['Modules/AccountingCore/resources/assets/js/reports.js'])
  <script>
    // Pass data from PHP to JavaScript
    window.pageData = {
      urls: {
        generate: "{{ route('accountingcore.reports.generate') }}"
      },
      labels: {
        selectCategory: @json(__('Select Category')),
        selectDateRange: @json(__('Please select a date range')),
        generatingReport: @json(__('Generating Report...')),
        pleaseWait: @json(__('Please wait while we generate your report')),
        errorOccurred: @json(__('Something went wrong')),
        success: @json(__('Success')),
        error: @json(__('Error')),
        noData: @json(__('No data found for the selected criteria'))
      }
    };
  </script>
@endsection

@section('page-style')
<style>
  #reportTable {
    table-layout: auto;
    width: 100%;
  }

  #reportTable th,
  #reportTable td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    white-space: nowrap;
  }

  #reportTable th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
  }

  #reportTable tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
  }

  #reportTable .text-end {
    text-align: right !important;
    padding-right: 1.5rem;
  }

  #reportTable tfoot tr {
    border-top: 2px solid #dee2e6;
  }

  /* Ensure proper width distribution */
  #reportTable th:first-child,
  #reportTable td:first-child {
    min-width: 120px;
    text-align: left;
  }

  #reportTable th:not(:first-child),
  #reportTable td:not(:first-child) {
    min-width: 100px;
  }

  /* Better number formatting */
  #reportTable .text-success {
    font-weight: 500;
  }

  #reportTable .text-danger {
    font-weight: 500;
  }

  /* Report card styling */
  #reportCard .table-responsive {
    max-height: 600px;
    overflow-y: auto;
  }
</style>
@endsection

