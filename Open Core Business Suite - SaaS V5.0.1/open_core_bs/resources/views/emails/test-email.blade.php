<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email</title>
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
            background-color: #696cff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border: 1px solid #e9ecef;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .success-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: white;
            padding: 15px;
            border-left: 4px solid #696cff;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Test Email Successful!</h1>
    </div>

    <div class="content">
        <div class="success-icon">✉️</div>

        <p>Congratulations! Your SMTP configuration is working correctly.</p>

        <div class="info-box">
            <strong>Application:</strong> {{ $appName }}<br>
            <strong>Test Time:</strong> {{ $testTime }}<br>
            <strong>Status:</strong> <span style="color: #28a745;">✓ Email Delivered Successfully</span>
        </div>

        <p>This is a test email sent from your application to verify that your email settings are configured properly.</p>

        <p>If you received this email, it means:</p>
        <ul>
            <li>✅ Your SMTP server connection is working</li>
            <li>✅ Authentication credentials are correct</li>
            <li>✅ Email delivery is functional</li>
        </ul>

        <p>You can now use email features in your application with confidence!</p>
    </div>

    <div class="footer">
        <p>This is an automated test email from {{ $appName }}.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>
