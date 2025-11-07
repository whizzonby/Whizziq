<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.6;
            background-color: #ffffff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0;
            background-color: white;
        }

        /* Header with Gradient Background - Matching Live Preview */
        .header {
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $accentColor }} 100%);
            padding: 40px 40px 40px 40px;
            margin: 0 0 40px 0;
            position: relative;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .company-section {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .company-icon {
            display: inline-block;
            width: 48px;
            height: 48px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            text-align: center;
            vertical-align: middle;
            margin-right: 12px;
            padding-top: 10px;
        }

        .company-icon-svg {
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
        }

        .company-info-inline {
            display: inline-block;
            vertical-align: top;
        }

        .invoice-title-text {
            font-size: 36px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }

        .company-name-sub {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
        }

        .invoice-number-section {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        .invoice-number-box {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 10px 16px;
            display: inline-block;
        }

        .invoice-number-label {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .invoice-number-value {
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
        }

        /* Bill To & Invoice Details Section - Matching Live Preview */
        .info-section {
            display: table;
            width: 100%;
            margin: 0 40px 30px 40px;
            width: calc(100% - 80px);
        }

        .bill-to-section {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 20px;
        }

        .invoice-details-section {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-left: 20px;
        }

        .section-header {
            font-size: 9px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .bill-to-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }

        .client-name {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }

        .client-detail {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.6;
        }

        .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            display: table;
            width: 100%;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            display: table-cell;
            font-weight: 500;
            color: #6b7280;
            width: 50%;
        }

        .detail-value {
            display: table-cell;
            font-weight: 600;
            color: #111827;
            text-align: right;
            width: 50%;
        }

        /* Items Table - Matching Live Preview */
        .items-section {
            margin: 0 40px 30px 40px;
        }

        .items-table {
            width: calc(100% - 80px);
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table thead tr {
            background-color: {{ $primaryColor }};
        }

        .items-table th {
            padding: 12px 16px;
            text-align: left;
            color: #ffffff;
            font-weight: 600;
            font-size: 11px;
        }

        .items-table th.text-right {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .items-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .items-table td {
            padding: 16px;
            vertical-align: top;
        }

        .item-description {
            font-weight: 500;
            color: #111827;
            margin-bottom: 2px;
        }

        .item-details {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }

        .text-right {
            text-align: right;
        }

        .item-value {
            color: #4b5563;
        }

        .item-amount {
            font-weight: 600;
            color: #111827;
        }

        /* Totals Section - Matching Live Preview */
        .totals-section {
            margin: 0 40px 30px 40px;
            text-align: right;
        }

        .totals-box {
            display: inline-block;
            min-width: 350px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
        }

        .totals-row {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .totals-label {
            display: table-cell;
            font-weight: 500;
            color: #4b5563;
            text-align: left;
        }

        .totals-value {
            display: table-cell;
            font-weight: 600;
            color: #111827;
            text-align: right;
        }

        .discount-row .totals-label,
        .discount-row .totals-value {
            color: #10b981;
        }

        .totals-divider {
            border-top: 2px solid #d1d5db;
            margin: 16px 0;
        }

        .total-row {
            margin-top: 16px;
        }

        .total-row .totals-label {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }

        .total-row .totals-value {
            font-size: 24px;
            font-weight: bold;
            color: {{ $primaryColor }};
        }

        /* Notes & Terms - Matching Live Preview */
        .notes-section {
            margin: 0 40px 30px 40px;
        }

        .note-box {
            background-color: #eff6ff;
            border-left: 4px solid {{ $primaryColor }};
            border-radius: 0 8px 8px 0;
            padding: 16px;
            margin-bottom: 16px;
        }

        .note-header {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .note-content {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.7;
        }

        .terms-box {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            border-radius: 0 8px 8px 0;
            padding: 16px;
        }

        /* Footer - Matching Live Preview */
        .footer {
            text-align: center;
            padding: 30px 40px 40px 40px;
            border-top: 2px solid #e5e7eb;
            margin-top: 20px;
        }

        .footer-text {
            color: #6b7280;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .footer-sub {
            color: #9ca3af;
            font-size: 9px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
            color: #d1d5db;
        }

        .empty-text {
            font-weight: 500;
            margin-bottom: 4px;
        }

        .empty-subtext {
            font-size: 10px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-draft {
            background-color: #e5e7eb;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section with Gradient -->
        <div class="header">
            <div class="header-content">
                <div class="company-section">
                    <div class="company-icon">
                        <span class="company-icon-svg">📄</span>
                    </div>
                    <div class="company-info-inline">
                        <div class="invoice-title-text">INVOICE</div>
                        <div class="company-name-sub">{{ $user->name ?? 'Your Company' }}</div>
                    </div>
                </div>
                <div class="invoice-number-section">
                    <div class="invoice-number-box">
                        <div class="invoice-number-label">Invoice No.</div>
                        <div class="invoice-number-value">#{{ $invoice->invoice_number }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bill To & Invoice Details -->
        <div class="info-section">
            <div class="bill-to-section">
                <div class="section-header">Bill To</div>
                <div class="bill-to-box">
                    <div class="client-name">{{ $client->name }}</div>
                    @if($client->email)
                        <div class="client-detail">{{ $client->email }}</div>
                    @endif
                    @if($client->company)
                        <div class="client-detail">{{ $client->company }}</div>
                    @endif
                    @if($client->address)
                        <div class="client-detail">{{ $client->address }}</div>
                    @endif
                    @if($client->city || $client->state || $client->zip)
                        <div class="client-detail">
                            {{ $client->city }}{{ $client->state ? ', ' . $client->state : '' }}{{ $client->zip ? ' ' . $client->zip : '' }}
                        </div>
                    @endif
                    @if($client->country)
                        <div class="client-detail">{{ $client->country }}</div>
                    @endif
                </div>
            </div>

            <div class="invoice-details-section">
                <div class="section-header">Invoice Details</div>
                <div>
                    <div class="detail-row">
                        <span class="detail-label">Invoice Date:</span>
                        <span class="detail-value">{{ $invoice->invoice_date->format('M d, Y') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Due Date:</span>
                        <span class="detail-value">{{ $invoice->due_date->format('M d, Y') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Currency:</span>
                        <span class="detail-value">{{ $invoice->currency }}</span>
                    </div>
                    @if($invoice->status)
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th class="text-right" style="width: 15%;">Qty</th>
                        <th class="text-right" style="width: 17%;">Rate</th>
                        <th class="text-right" style="width: 18%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if($items && count($items) > 0)
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <div class="item-description">{{ $item->description }}</div>
                                    @if($item->details)
                                        <div class="item-details">{{ $item->details }}</div>
                                    @endif
                                </td>
                                <td class="text-right item-value">{{ number_format($item->quantity, 2) }}</td>
                                <td class="text-right item-value">{{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right item-amount">{{ $invoice->currency }} {{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="empty-state">
                                <div class="empty-icon">📄</div>
                                <div class="empty-text">No items yet</div>
                                <div class="empty-subtext">Add items to see them here</div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="totals-box">
                <div class="totals-row">
                    <span class="totals-label">Subtotal:</span>
                    <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
                </div>

                @if($invoice->discount_amount > 0)
                    <div class="totals-row discount-row">
                        <span class="totals-label">Discount:</span>
                        <span class="totals-value">-{{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</span>
                    </div>
                @endif

                @if($invoice->tax_rate > 0)
                    <div class="totals-row">
                        <span class="totals-label">Tax ({{ number_format($invoice->tax_rate, 1) }}%):</span>
                        <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</span>
                    </div>
                @endif

                <div class="totals-divider"></div>

                <div class="totals-row total-row">
                    <span class="totals-label">Total Amount:</span>
                    <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Notes & Terms -->
        @if($invoice->notes || $invoice->terms)
            <div class="notes-section">
                @if($invoice->notes)
                    <div class="note-box">
                        <div class="note-header">Notes</div>
                        <div class="note-content">{{ $invoice->notes }}</div>
                    </div>
                @endif

                @if($invoice->terms)
                    <div class="terms-box">
                        <div class="note-header">Payment Terms</div>
                        <div class="note-content">{{ $invoice->terms }}</div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            @if($invoice->footer)
                <div class="footer-text">{{ $invoice->footer }}</div>
            @else
                <div class="footer-text">Thank you for your business!</div>
            @endif
            <div class="footer-sub">Generated with WhizzIQ Invoice Builder</div>
        </div>
    </div>
</body>
</html>
