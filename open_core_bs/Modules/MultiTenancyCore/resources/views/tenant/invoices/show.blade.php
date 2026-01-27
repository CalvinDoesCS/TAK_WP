@extends('layouts/layoutMaster')

@section('title', __('Invoice') . ' #' . $invoiceData['invoice']['number'])

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

@section('page-style')
<style>
    .invoice-preview {
        max-width: 1000px;
        margin: 0 auto;
    }
    .invoice-actions {
        position: sticky;
        top: 80px;
        z-index: 10;
    }
    @media print {
        .invoice-actions,
        .navbar,
        .menu,
        .footer {
            display: none !important;
        }
        .invoice-preview {
            max-width: 100%;
        }
    }
</style>
@endsection

@section('page-script')
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <x-breadcrumb 
                    :title="__('Invoice') . ' #' . $invoiceData['invoice']['number']"
                    :homeUrl="route('multitenancycore.tenant.dashboard')"
                    :breadcrumbs="[
                        ['name' => __('Billing'), 'url' => route('multitenancycore.tenant.billing')],
                        ['name' => __('Invoices'), 'url' => route('multitenancycore.tenant.invoices')],
                        ['name' => __('Invoice') . ' #' . $invoiceData['invoice']['number']]
                    ]"
                />
            </div>
        </div>

        <div class="row">
        <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-4">
            <div class="card invoice-preview-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column p-sm-3 p-0">
                        <div class="mb-xl-0 mb-4">
                            <div class="d-flex svg-illustration mb-3 gap-2">
                                <span class="app-brand-text h3 mb-0 fw-bold">{{ $invoiceData['company']['name'] }}</span>
                            </div>
                            <p class="mb-1">{{ $invoiceData['company']['address'] }}</p>
                            <p class="mb-1">{{ $invoiceData['company']['phone'] }}</p>
                            <p class="mb-0">{{ $invoiceData['company']['email'] }}</p>
                            @if($invoiceData['company']['tax_id'])
                                <p class="mb-0">{{ __('Tax ID') }}: {{ $invoiceData['company']['tax_id'] }}</p>
                            @endif
                        </div>
                        <div>
                            <h4>{{ __('Invoice') }} #{{ $invoiceData['invoice']['number'] }}</h4>
                            <div class="mb-2">
                                <span class="me-1">{{ __('Date Issued') }}:</span>
                                <span class="fw-semibold">{{ $invoiceData['invoice']['date']->format('M d, Y') }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="me-1">{{ __('Due Date') }}:</span>
                                <span class="fw-semibold">{{ $invoiceData['invoice']['due_date']->format('M d, Y') }}</span>
                            </div>
                            <div>
                                <span class="me-1">{{ __('Status') }}:</span>
                                <span class="badge bg-label-success">{{ __('Paid') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-0" />
                <div class="card-body">
                    <div class="row p-sm-3 p-0">
                        <div class="col-xl-6 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-4 mb-sm-0 mb-4">
                            <h6 class="pb-2">{{ __('Invoice To') }}:</h6>
                            <p class="mb-1 fw-semibold">{{ $invoiceData['customer']['name'] }}</p>
                            @if($invoiceData['customer']['address'])
                                <p class="mb-1">{{ $invoiceData['customer']['address'] }}</p>
                            @endif
                            <p class="mb-1">{{ $invoiceData['customer']['phone'] }}</p>
                            <p class="mb-0">{{ $invoiceData['customer']['email'] }}</p>
                        </div>
                        <div class="col-xl-6 col-md-12 col-sm-7 col-12">
                            <h6 class="pb-2">{{ __('Payment Details') }}:</h6>
                            <table>
                                <tbody>
                                    <tr>
                                        <td class="pe-3">{{ __('Payment Method') }}:</td>
                                        <td>{{ $invoiceData['payment']['method'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="pe-3">{{ __('Reference') }}:</td>
                                        <td>{{ $invoiceData['payment']['reference'] }}</td>
                                    </tr>
                                    @if($invoiceData['payment']['transaction_id'])
                                        <tr>
                                            <td class="pe-3">{{ __('Transaction ID') }}:</td>
                                            <td>{{ $invoiceData['payment']['transaction_id'] }}</td>
                                        </tr>
                                    @endif
                                    @if($invoiceData['payment']['paid_at'])
                                        <tr>
                                            <td class="pe-3">{{ __('Paid On') }}:</td>
                                            <td>{{ $invoiceData['payment']['paid_at']->format('M d, Y') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table border-top m-0">
                        <thead>
                            <tr>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Qty') }}</th>
                                <th>{{ __('Unit Price') }}</th>
                                <th>{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoiceData['items'] as $item)
                                <tr>
                                    <td class="text-nowrap">{{ $item['description'] }}</td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td>{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($item['price']) }}</td>
                                    <td>{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($item['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-md-0 mb-3"></div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="w-px-100">{{ __('Subtotal') }}:</span>
                                <span class="fw-semibold">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($invoiceData['totals']['subtotal']) }}</span>
                            </div>
                            @if($invoiceData['totals']['tax'] > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-100">{{ __('Tax') }}:</span>
                                    <span class="fw-semibold">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($invoiceData['totals']['tax']) }}</span>
                                </div>
                            @endif
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="w-px-100">{{ __('Total') }}:</span>
                                <span class="fw-semibold">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($invoiceData['totals']['total']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-0" />
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <span class="fw-semibold">{{ __('Note') }}:</span>
                            <span>{{ __('This is a computer-generated invoice and does not require a signature.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-4 col-12 invoice-actions">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('multitenancycore.tenant.invoices.download', $payment->id) }}" class="btn btn-primary d-grid w-100 mb-3">
                        <span class="d-flex align-items-center justify-content-center text-nowrap">
                            <i class="bx bx-download bx-xs me-1"></i>{{ __('Download') }}
                        </span>
                    </a>
                    <a href="{{ route('multitenancycore.tenant.invoices') }}" class="btn btn-label-secondary d-grid w-100">
                        <span class="d-flex align-items-center justify-content-center text-nowrap">
                            <i class="bx bx-left-arrow-alt bx-xs me-1"></i>{{ __('Back to Invoices') }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection