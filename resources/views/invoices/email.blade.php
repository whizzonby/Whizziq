<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #0066cc; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Invoice #{{ $invoice->invoice_number }}</h1>
    </div>

    <div style="background-color: #f9f9f9; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="font-size: 16px; margin-top: 0;">Dear {{ $client->name }},</p>

        <p style="font-size: 14px; line-height: 1.8;">
            {{ $emailMessage }}
        </p>

        <div style="background-color: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0066cc;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #666;">Invoice Number:</td>
                    <td style="padding: 8px 0; text-align: right;">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #666;">Invoice Date:</td>
                    <td style="padding: 8px 0; text-align: right;">{{ $invoice->invoice_date->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #666;">Due Date:</td>
                    <td style="padding: 8px 0; text-align: right;">{{ $invoice->due_date->format('M d, Y') }}</td>
                </tr>
                <tr style="border-top: 2px solid #0066cc;">
                    <td style="padding: 12px 0; font-weight: bold; color: #0066cc; font-size: 16px;">Total Amount:</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: bold; color: #0066cc; font-size: 16px;">
                        {{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}
                    </td>
                </tr>
                @if($invoice->balance_due < $invoice->total_amount)
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; color: #666;">Amount Due:</td>
                    <td style="padding: 8px 0; text-align: right; font-weight: bold;">
                        {{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <p style="font-size: 14px;">
            The complete invoice is attached as a PDF document. If you have any questions regarding this invoice, please don't hesitate to contact us.
        </p>

        @if($invoice->terms)
        <div style="background-color: #fff9e6; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
            <p style="margin: 0; font-size: 13px; color: #666;">
                <strong>Payment Terms:</strong><br>
                {{ $invoice->terms }}
            </p>
        </div>
        @endif

        <p style="font-size: 14px; margin-bottom: 0;">
            Best regards,<br>
            <strong>{{ $invoice->user->name }}</strong>
        </p>
    </div>

    <div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">
        <p style="margin: 0;">
            This is an automated email. Please do not reply to this message.
        </p>
    </div>
</body>
</html>
