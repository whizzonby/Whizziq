<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-container {
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-body {
            margin: 20px 0;
        }
        .email-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-greeting">
            <p>Hi {{ $contactName }},</p>
        </div>

        <div class="email-body">
            {!! $body !!}
        </div>

        <div class="email-signature">
            <p>Best regards,<br>
            {{ auth()->user()->name ?? 'Your Company' }}</p>
        </div>

        <div class="email-footer">
            <p>Sent via WhizIQ CRM</p>
        </div>
    </div>
</body>
</html>
