<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? ($campaign->subject ?? 'Email from ' . config('app.name')) }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-content {
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .footer a {
            color: #0066cc;
            text-decoration: none;
        }
        a {
            color: #0066cc;
        }
        h1, h2, h3, h4, h5, h6 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-content">
            {!! $body ?? $campaign->body ?? 'Email content' !!}
        </div>

        <div class="footer">
            <p>
                You are receiving this email because you have an account with {{ config('app.name') }}.
            </p>
            <p>
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>

    {{-- Tracking pixel for open tracking --}}
    @if(isset($emailLog))
        <img src="{{ route('admin-email.track.open', ['log' => $emailLog->id]) }}" width="1" height="1" alt="" style="display:none;" />
    @endif
</body>
</html>
