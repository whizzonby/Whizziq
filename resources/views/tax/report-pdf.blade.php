<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->report_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #4F46E5;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .business-info {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 5px;
        }
        .business-info h3 {
            margin-top: 0;
            color: #4F46E5;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .summary-table th,
        .summary-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .summary-table th {
            background-color: #4F46E5;
            color: white;
            font-weight: bold;
        }
        .summary-table tr:hover {
            background-color: #f9fafb;
        }
        .total-row {
            font-weight: bold;
            background-color: #f3f4f6;
            font-size: 14px;
        }
        .deductions-section {
            margin-top: 30px;
        }
        .deductions-section h3 {
            color: #4F46E5;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 10px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        .positive {
            color: #059669;
        }
        .negative {
            color: #DC2626;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report->report_name }}</h1>
        <p>Generated on {{ $report->generated_at->format('F d, Y') }}</p>
        <p>Period: {{ Carbon\Carbon::parse($report->period_start)->format('M d, Y') }} - {{ Carbon\Carbon::parse($report->period_end)->format('M d, Y') }}</p>
    </div>

    @if($taxSetting)
    <div class="business-info">
        <h3>Business Information</h3>
        <p><strong>Business Name:</strong> {{ $taxSetting->business_name ?? 'N/A' }}</p>
        <p><strong>Tax ID:</strong> {{ $taxSetting->tax_id ?? 'N/A' }}</p>
        <p><strong>Business Type:</strong> {{ ucfirst(str_replace('_', ' ', $taxSetting->business_type ?? 'N/A')) }}</p>
        <p><strong>Location:</strong> {{ $taxSetting->state ?? '' }}{{ $taxSetting->state && $taxSetting->country ? ', ' : '' }}{{ $taxSetting->country ?? '' }}</p>
    </div>
    @endif

    <h3 style="color: #4F46E5; border-bottom: 2px solid #4F46E5; padding-bottom: 10px;">Tax Summary</h3>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Revenue</td>
                <td class="amount positive">${{ number_format($report->total_revenue, 2) }}</td>
            </tr>
            <tr>
                <td>Total Expenses</td>
                <td class="amount">${{ number_format($report->total_expenses, 2) }}</td>
            </tr>
            <tr>
                <td>Total Tax Deductions</td>
                <td class="amount">${{ number_format($report->total_deductions, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Taxable Income</td>
                <td class="amount">${{ number_format($report->taxable_income, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Estimated Tax Owed</td>
                <td class="amount negative">${{ number_format($report->estimated_tax, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if(!empty($deductions))
    <div class="deductions-section">
        <h3>Tax Deductions by Category</h3>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="amount">Total Amount</th>
                    <th class="amount">Deductible Amount</th>
                    <th style="text-align: center;">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deductions as $deduction)
                <tr>
                    <td>{{ $deduction['category_name'] }}</td>
                    <td class="amount">${{ number_format($deduction['total_amount'], 2) }}</td>
                    <td class="amount positive">${{ number_format($deduction['deductible_amount'], 2) }}</td>
                    <td style="text-align: center;">{{ $deduction['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This report is generated for informational purposes only and should not be considered as professional tax advice.</p>
        <p>Please consult with a qualified tax professional for accurate tax filing and advice.</p>
        <p>Generated by {{ config('app.name', 'WhizIQ') }} on {{ now()->format('F d, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
