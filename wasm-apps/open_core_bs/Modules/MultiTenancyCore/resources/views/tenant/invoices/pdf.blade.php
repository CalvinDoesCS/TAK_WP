<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Invoice') }} #{{ $invoiceData['invoice']['number'] }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .company-info {
            flex: 1;
        }
        .invoice-info {
            text-align: right;
            flex: 1;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 30px;
            text-align: right;
        }
        .totals-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5px;
        }
        .totals-label {
            margin-right: 20px;
            min-width: 100px;
        }
        .totals-value {
            min-width: 100px;
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .note {
            margin-top: 40px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .status-paid {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="company-info">
            <div class="invoice-title">{{ $invoiceData['company']['name'] }}</div>
            <div>{{ $invoiceData['company']['address'] }}</div>
            <div>{{ $invoiceData['company']['phone'] }}</div>
            <div>{{ $invoiceData['company']['email'] }}</div>
            @if($invoiceData['company']['tax_id'])
                <div>{{ __('Tax ID') }}: {{ $invoiceData['company']['tax_id'] }}</div>
            @endif
        </div>
        <div class="invoice-info">
            <div class="invoice-title">{{ __('INVOICE') }}</div>
            <div><strong>{{ __('Invoice #') }}:</strong> {{ $invoiceData['invoice']['number'] }}</div>
            <div><strong>{{ __('Date') }}:</strong> {{ $invoiceData['invoice']['date']->format('M d, Y') }}</div>
            <div><strong>{{ __('Due Date') }}:</strong> {{ $invoiceData['invoice']['due_date']->format('M d, Y') }}</div>
            <div><strong>{{ __('Status') }}:</strong> <span class="status-paid">{{ __('PAID') }}</span></div>
        </div>
    </div>

    <div class="section-title">{{ __('Bill To') }}:</div>
    <div>
        <strong>{{ $invoiceData['customer']['name'] }}</strong><br>
        @if($invoiceData['customer']['address'])
            {{ $invoiceData['customer']['address'] }}<br>
        @endif
        {{ $invoiceData['customer']['phone'] }}<br>
        {{ $invoiceData['customer']['email'] }}
    </div>

    <div class="section-title">{{ __('Payment Details') }}:</div>
    <div>
        <strong>{{ __('Payment Method') }}:</strong> {{ $invoiceData['payment']['method'] }}<br>
        <strong>{{ __('Reference') }}:</strong> {{ $invoiceData['payment']['reference'] }}<br>
        @if($invoiceData['payment']['transaction_id'])
            <strong>{{ __('Transaction ID') }}:</strong> {{ $invoiceData['payment']['transaction_id'] }}<br>
        @endif
        @if($invoiceData['payment']['paid_at'])
            <strong>{{ __('Paid On') }}:</strong> {{ $invoiceData['payment']['paid_at']->format('M d, Y') }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th class="text-right">{{ __('Qty') }}</th>
                <th class="text-right">{{ __('Unit Price') }}</th>
                <th class="text-right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoiceData['items'] as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($item['price']) }}</td>
                    <td class="text-right">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($item['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">{{ __('Subtotal') }}:</span>
            <span class="totals-value">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($invoiceData['totals']['subtotal']) }}</span>
        </div>
        @if($invoiceData['totals']['tax'] > 0)
            <div class="totals-row">
                <span class="totals-label">{{ __('Tax') }}:</span>
                <span class="totals-value">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($invoiceData['totals']['tax']) }}</span>
            </div>
        @endif
        <div class="totals-row total-row">
            <span class="totals-label">{{ __('Total') }}:</span>
            <span class="totals-value">{{ \Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper::format($invoiceData['totals']['total']) }}</span>
        </div>
    </div>

    <div class="note">
        <strong>{{ __('Note') }}:</strong> {{ __('This is a computer-generated invoice and does not require a signature.') }}
    </div>
</body>
</html>