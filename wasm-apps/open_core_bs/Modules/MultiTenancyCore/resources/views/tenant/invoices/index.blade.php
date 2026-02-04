@extends('layouts.layoutMaster')

@section('title', __('Invoices'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/jquery/jquery.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
    ])
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-center mb-5">
                    <h2 class="mb-2">{{ __('Invoices') }}</h2>
                    <p class="text-muted">{{ __('Download invoices for completed payments') }}</p>
                    <a href="{{ route('multitenancycore.tenant.billing') }}" class="btn btn-sm btn-label-primary">
                        <i class="bx bx-wallet me-1"></i>{{ __('View All Payments') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @if($invoices->count() > 0)
                            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                                <i class="bx bx-check-circle me-2"></i>
                                <div>
                                    {{ __('Invoices are automatically generated for approved payments. You can view and download them anytime.') }}
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="invoicesTable">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Invoice Date') }}</th>
                                            <th>{{ __('Invoice Number') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th>{{ __('Amount') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->description ?? __('Subscription payment') }}</td>
                                                <td>{{ $invoice->formatted_amount }}</td>
                                                <td>
                                                    @if($invoice->status === 'approved')
                                                        <span class="badge bg-label-success">{{ __('Paid') }}</span>
                                                    @else
                                                        <span class="badge bg-label-secondary">{{ ucfirst($invoice->status) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('multitenancycore.tenant.invoices.show', $invoice->id) }}" 
                                                       class="btn btn-sm btn-label-primary me-1">
                                                        <i class="bx bx-show me-1"></i>{{ __('View') }}
                                                    </a>
                                                    <a href="{{ route('multitenancycore.tenant.invoices.download', $invoice->id) }}" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bx bx-download me-1"></i>{{ __('Download') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            {{-- Pagination --}}
                            <div class="mt-4">
                                {{ $invoices->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bx bx-file-blank" style="font-size: 4rem; color: #e0e0e0;"></i>
                                </div>
                                <h5 class="text-muted">{{ __('No invoices found') }}</h5>
                                <p class="text-muted">{{ __('Invoices are generated automatically when your payments are approved.') }}</p>
                                <a href="{{ route('multitenancycore.tenant.billing') }}" class="btn btn-sm btn-primary mt-2">
                                    <i class="bx bx-wallet me-1"></i>{{ __('View Payment History') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['Modules/MultiTenancyCore/resources/assets/js/tenant/invoices.js'])
@endsection