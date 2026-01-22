<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provisioning Failed - Admin Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
        }
        .info-box {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .info-row {
            margin: 8px 0;
        }
        .label {
            font-weight: bold;
            color: #495057;
        }
        .error-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin: 0;">⚠️ Tenant Provisioning Failed</h2>
    </div>

    <div class="content">
        <p>Dear Administrator,</p>

        <p>A tenant provisioning attempt has failed and requires your immediate attention.</p>

        <div class="info-box">
            <h3 style="margin-top: 0;">Tenant Information</h3>
            <div class="info-row">
                <span class="label">Company Name:</span> {{ $tenant->name }}
            </div>
            <div class="info-row">
                <span class="label">Email:</span> {{ $tenant->email }}
            </div>
            <div class="info-row">
                <span class="label">Subdomain:</span> {{ $tenant->subdomain }}
            </div>
            <div class="info-row">
                <span class="label">Tenant ID:</span> #{{ $tenant->id }}
            </div>
            <div class="info-row">
                <span class="label">Status:</span> {{ ucfirst($tenant->status) }}
            </div>
            @if($attempts > 1)
            <div class="info-row">
                <span class="label">Attempts:</span> {{ $attempts }}
            </div>
            @endif
        </div>

        <div class="error-box">
            <h4 style="margin-top: 0; color: #856404;">Error Details:</h4>
            <p style="margin: 0; font-family: monospace; word-wrap: break-word;">{{ $error }}</p>
        </div>

        <h3>Recommended Actions:</h3>
        <ol>
            <li>Review the error message above</li>
            <li>Check database server availability and permissions</li>
            <li>Verify sufficient disk space on database server</li>
            <li>Check application logs for additional details</li>
            <li>Attempt manual provisioning if necessary</li>
        </ol>

        <a href="{{ route('multitenancycore.admin.provisioning.show', $tenant->id) }}" class="button">
            View Tenant Provisioning Details
        </a>

        <div class="footer">
            <p>This is an automated alert from {{ $appName }}.</p>
            <p>Timestamp: {{ now()->format('Y-m-d H:i:s T') }}</p>
        </div>
    </div>
</body>
</html>
