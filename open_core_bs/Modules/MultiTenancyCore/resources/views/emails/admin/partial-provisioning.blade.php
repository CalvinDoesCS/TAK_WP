<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partial Provisioning - Admin Alert</title>
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
            background-color: #ffc107;
            color: #212529;
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
            border-left: 4px solid #ffc107;
        }
        .info-row {
            margin: 8px 0;
        }
        .label {
            font-weight: bold;
            color: #495057;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .module-list {
            background-color: white;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .module-list li {
            padding: 5px 0;
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
        <h2 style="margin: 0;">⚠️ Partial Tenant Provisioning</h2>
    </div>

    <div class="content">
        <p>Dear Administrator,</p>

        <p>A tenant has been provisioned, but some modules failed to migrate or seed properly.</p>

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
        </div>

        <div class="warning-box">
            <h4 style="margin-top: 0; color: #856404;">Failed Modules:</h4>
            <div class="module-list">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($failedModules as $module)
                        <li>{{ $module }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <h3>Impact:</h3>
        <p>The tenant's application is active but may have limited functionality due to missing module data or migrations.</p>

        <h3>Recommended Actions:</h3>
        <ol>
            <li>Review application logs for specific migration/seeder errors</li>
            <li>Check if the failed modules are critical for the tenant</li>
            <li>Manually run migrations for failed modules if needed</li>
            <li>Contact the tenant to explain any missing functionality</li>
        </ol>

        <a href="{{ route('multitenancycore.admin.tenants.show', $tenant->id) }}" class="button">
            View Tenant Details
        </a>

        <div class="footer">
            <p>This is an automated alert from {{ $appName }}.</p>
            <p>Timestamp: {{ now()->format('Y-m-d H:i:s T') }}</p>
        </div>
    </div>
</body>
</html>
