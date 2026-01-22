@extends('layouts/layoutMaster')

@section('title', __('Submit Expense Request'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        expensesList: @json(route('hrcore.my.expenses'))
      },
      labels: {
        selectExpenseType: @json(__('Please select an expense type')),
        selectExpenseDate: @json(__('Please select the expense date')),
        amountGreaterThanZero: @json(__('Amount must be greater than zero')),
        fileSizeError: @json(__('File size exceeds 2MB limit')),
        error: @json(__('Error')),
        success: @json(__('Success!')),
        submitting: @json(__('Submitting...')),
        expenseSubmitted: @json(__('Expense request submitted successfully')),
        somethingWentWrong: @json(__('Something went wrong. Please try again.'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/expense-create.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Submit Expense Request')"
      :breadcrumbs="[
        ['name' => __('My Expenses'), 'url' => route('hrcore.my.expenses')],
        ['name' => __('Submit Request'), 'url' => '']
      ]"
      :home-url="url('/')"
    />

    <div class="row">
      {{-- Expense Form --}}
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">{{ __('Expense Request Details') }}</h5>
            <p class="card-subtitle text-muted mb-0">
              {{ __('Submit your expense for reimbursement') }}
            </p>
          </div>
          <div class="card-body">
            <form action="{{ route('hrcore.my.expenses.store') }}" method="POST" id="expenseForm" enctype="multipart/form-data">
              @csrf

              <div class="row">
                {{-- Expense Type --}}
                <div class="col-md-6 mb-3">
                  <label for="expense_type_id" class="form-label">{{ __('Expense Type') }} <span class="text-danger">*</span></label>
                  <select id="expense_type_id" name="expense_type_id" class="form-select select2 @error('expense_type_id') is-invalid @enderror" data-placeholder="{{ __('Select Expense Type') }}" required>
                    <option value="">{{ __('Select Expense Type') }}</option>
                    @foreach($expenseTypes as $type)
                      <option value="{{ $type->id }}" {{ old('expense_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                  </select>
                  @error('expense_type_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                {{-- Expense Date --}}
                <div class="col-md-6 mb-3">
                  <label for="for_date" class="form-label">{{ __('Expense Date') }} <span class="text-danger">*</span></label>
                  <input type="text" id="for_date" name="for_date" class="form-control @error('for_date') is-invalid @enderror" value="{{ old('for_date') }}" required>
                  <small class="text-muted">{{ __('Date when the expense was incurred') }}</small>
                  @error('for_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              {{-- Amount --}}
              <div class="mb-3">
                <label for="amount" class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">{{ $settings->currency_symbol ?? '$' }}</span>
                  <input type="number" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" step="0.01" min="0" required>
                </div>
                <small class="text-muted">{{ __('Enter the expense amount') }}</small>
                @error('amount')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Remarks --}}
              <div class="mb-3">
                <label for="remarks" class="form-label">{{ __('Remarks') }}</label>
                <textarea id="remarks" name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="4" maxlength="500">{{ old('remarks') }}</textarea>
                <small class="text-muted">{{ __('Provide additional details about this expense (optional, max 500 characters)') }}</small>
                @error('remarks')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Document Upload --}}
              <div class="mb-3">
                <label for="document" class="form-label">{{ __('Receipt/Proof') }}</label>
                <input type="file" id="document" name="document" class="form-control @error('document') is-invalid @enderror" accept="image/jpeg,image/jpg,image/png,application/pdf">
                <small class="text-muted">{{ __('Upload receipt or proof (JPG, PNG, PDF - Max 2MB)') }}</small>
                @error('document')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div id="document_preview" class="mt-2" style="display: none;">
                  <img id="preview_image" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                </div>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="bx bx-save me-1"></i>{{ __('Submit Request') }}
                </button>
                <a href="{{ route('hrcore.my.expenses') }}" class="btn btn-label-secondary">
                  <i class="bx bx-arrow-back me-1"></i>{{ __('Cancel') }}
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- Sidebar Information --}}
      <div class="col-md-4">
        {{-- Guidelines --}}
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title">{{ __('Guidelines') }}</h5>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              <li class="d-flex align-items-start mb-3">
                <i class="bx bx-receipt text-primary me-2 mt-1"></i>
                <div>
                  <strong>{{ __('Valid Receipt') }}:</strong><br>
                  <small class="text-muted">{{ __('Always attach a valid receipt or proof of expense') }}</small>
                </div>
              </li>
              <li class="d-flex align-items-start mb-3">
                <i class="bx bx-calendar text-warning me-2 mt-1"></i>
                <div>
                  <strong>{{ __('Timely Submission') }}:</strong><br>
                  <small class="text-muted">{{ __('Submit expenses within 30 days of occurrence') }}</small>
                </div>
              </li>
              <li class="d-flex align-items-start mb-3">
                <i class="bx bx-check-circle text-success me-2 mt-1"></i>
                <div>
                  <strong>{{ __('Accurate Details') }}:</strong><br>
                  <small class="text-muted">{{ __('Ensure all expense details are accurate and complete') }}</small>
                </div>
              </li>
              <li class="d-flex align-items-start mb-0">
                <i class="bx bx-time text-info me-2 mt-1"></i>
                <div>
                  <strong>{{ __('Approval Process') }}:</strong><br>
                  <small class="text-muted">{{ __('Expenses are reviewed and approved by your manager') }}</small>
                </div>
              </li>
            </ul>
          </div>
        </div>

        {{-- Expense Types Info --}}
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">{{ __('Common Expense Types') }}</h5>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              @foreach($expenseTypes->take(5) as $type)
                <div class="list-group-item px-0 py-2">
                  <i class="bx bx-check-circle text-success me-2"></i>
                  <span>{{ $type->name }}</span>
                </div>
              @endforeach
              @if($expenseTypes->count() > 5)
                <div class="list-group-item px-0 py-2 text-muted">
                  <small>{{ __('+ :count more types', ['count' => $expenseTypes->count() - 5]) }}</small>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
