@php
    $invoiceNumber = $data['invoice_number'] ?? 'INV-00001';
    $invoiceDate = isset($data['invoice_date']) ? \Carbon\Carbon::parse($data['invoice_date'])->format('M d, Y') : date('M d, Y');
    $dueDate = isset($data['due_date']) ? \Carbon\Carbon::parse($data['due_date'])->format('M d, Y') : date('M d, Y');
    $currency = $data['currency'] ?? 'USD';
    $taxRate = floatval($data['tax_rate'] ?? 0);
    $discountAmount = floatval($data['discount_amount'] ?? 0);
    $items = $data['items'] ?? [];

    // Load client data if invoice_client_id is provided
    $clientName = 'Client Name';
    $clientEmail = 'client@example.com';
    $clientCompany = 'Client Company';

    if (!empty($data['invoice_client_id'])) {
        $client = \App\Models\InvoiceClient::find($data['invoice_client_id']);
        if ($client) {
            $clientName = $client->name;
            $clientEmail = $client->email ?? 'No email';
            $clientCompany = $client->company ?? '';
        }
    }

    // Ensure items is an array and has proper structure
    if (!is_array($items)) {
        $items = [];
    }

    // Calculate totals - ensure all values are numeric
    $subtotal = collect($items)->sum(function($item) {
        if (!is_array($item)) {
            return 0;
        }
        return floatval($item['amount'] ?? 0);
    });
    $taxAmount = ($subtotal * $taxRate) / 100;
    $total = $subtotal + $taxAmount - $discountAmount;

    // Template colors
    $colors = [
        'modern' => ['primary' => '#3b82f6', 'accent' => '#60a5fa', 'text' => '#1e40af'],
        'elegant' => ['primary' => '#9333ea', 'accent' => '#a855f7', 'text' => '#6b21a8'],
        'minimal' => ['primary' => '#64748b', 'accent' => '#94a3b8', 'text' => '#334155'],
        'vibrant' => ['primary' => '#10b981', 'accent' => '#34d399', 'text' => '#065f46'],
    ];

    $templateColors = $colors[$template] ?? $colors['modern'];
@endphp

<div class="bg-white rounded-lg shadow-xl overflow-hidden transform transition-all duration-300 hover:shadow-2xl"
     style="min-height: 700px; max-width: 800px; margin: 0 auto;">

    <!-- Header Section with Template-specific Design -->
    <div class="relative overflow-hidden"
         style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $accentColor }} 100%);">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)"/>
            </svg>
        </div>

        <div class="relative px-8 py-10">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white mb-1">INVOICE</h1>
                            <p class="text-white/80 text-sm">{{ auth()->user()->name ?? 'Your Company' }}</p>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/30">
                        <p class="text-white/70 text-xs uppercase tracking-wide mb-1">Invoice No.</p>
                        <p class="text-white font-bold text-xl">#{{ $invoiceNumber }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="px-8 py-8">
        <!-- Bill To & Invoice Details -->
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <p class="text-xs uppercase tracking-wider text-gray-500 mb-3 font-semibold">Bill To</p>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="font-semibold text-gray-900 text-lg mb-1">
                        {{ $clientName }}
                    </p>
                    <p class="text-gray-600 text-sm">{{ $clientEmail }}</p>
                    @if($clientCompany)
                        <p class="text-gray-600 text-sm">{{ $clientCompany }}</p>
                    @endif
                </div>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wider text-gray-500 mb-3 font-semibold">Invoice Details</p>
                <div class="space-y-2">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-600 font-medium">Invoice Date:</span>
                        <span class="text-gray-900 font-semibold">{{ $invoiceDate }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-gray-600 font-medium">Due Date:</span>
                        <span class="text-gray-900 font-semibold">{{ $dueDate }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-gray-600 font-medium">Currency:</span>
                        <span class="text-gray-900 font-semibold">{{ $currency }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-8">
            <div class="overflow-hidden rounded-lg border border-gray-200">
                <table class="w-full">
                    <thead>
                        <tr style="background-color: {{ $primaryColor }};">
                            <th class="text-left py-3 px-4 text-white font-semibold text-sm">Description</th>
                            <th class="text-right py-3 px-4 text-white font-semibold text-sm w-24">Qty</th>
                            <th class="text-right py-3 px-4 text-white font-semibold text-sm w-32">Rate</th>
                            <th class="text-right py-3 px-4 text-white font-semibold text-sm w-32">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $index => $item)
                            @php
                                $rowIndex = is_numeric($index) ? intval($index) : $loop->index;
                                $isEven = ($rowIndex % 2 === 0);
                            @endphp
                            <tr class="border-b border-gray-200 {{ $isEven ? 'bg-white' : 'bg-gray-50' }}">
                                <td class="py-4 px-4">
                                    <p class="font-medium text-gray-900">{{ $item['description'] ?? 'Item ' . ($rowIndex + 1) }}</p>
                                    @if(!empty($item['details']))
                                        <p class="text-sm text-gray-500 mt-1">{{ $item['details'] }}</p>
                                    @endif
                                </td>
                                <td class="text-right py-4 px-4 text-gray-700">{{ number_format(floatval($item['quantity'] ?? 1), 2) }}</td>
                                <td class="text-right py-4 px-4 text-gray-700">{{ $currency }} {{ number_format(floatval($item['unit_price'] ?? 0), 2) }}</td>
                                <td class="text-right py-4 px-4 font-semibold text-gray-900">{{ $currency }} {{ number_format(floatval($item['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                        
                        @if(empty($items))
                            <tr>
                                <td colspan="4" class="py-8 px-4 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="font-medium">No items yet</p>
                                    <p class="text-sm mt-1">Add items to see them here</p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totals Section -->
        <div class="flex justify-end mb-8">
            <div class="w-full max-w-sm">
                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-700">
                            <span class="font-medium">Subtotal:</span>
                            <span class="font-semibold">{{ $currency }} {{ number_format(floatval($subtotal), 2) }}</span>
                        </div>

                        @if($discountAmount > 0)
                        <div class="flex justify-between text-green-600">
                            <span class="font-medium">Discount:</span>
                            <span class="font-semibold">-{{ $currency }} {{ number_format(floatval($discountAmount), 2) }}</span>
                        </div>
                        @endif

                        @if($taxRate > 0)
                        <div class="flex justify-between text-gray-700">
                            <span class="font-medium">Tax ({{ number_format(floatval($taxRate), 1) }}%):</span>
                            <span class="font-semibold">{{ $currency }} {{ number_format(floatval($taxAmount), 2) }}</span>
                        </div>
                        @endif

                        <div class="border-t-2 border-gray-300 pt-3 mt-3">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">Total Amount:</span>
                                <div class="text-right">
                                    <div class="text-3xl font-bold" style="color: {{ $primaryColor }};">
                                        {{ $currency }} {{ number_format(floatval($total), 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes & Terms -->
        @if(!empty($data['notes']) || !empty($data['terms']))
        <div class="grid grid-cols-1 gap-6 mb-8">
            @if(!empty($data['notes']))
            <div class="bg-blue-50 border-l-4 rounded-r-lg p-4" style="border-color: {{ $primaryColor }};">
                <p class="text-xs uppercase tracking-wider text-gray-600 mb-2 font-semibold">Notes</p>
                <p class="text-gray-700 text-sm leading-relaxed">{{ $data['notes'] }}</p>
            </div>
            @endif

            @if(!empty($data['terms']))
            <div class="bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-4">
                <p class="text-xs uppercase tracking-wider text-gray-600 mb-2 font-semibold">Payment Terms</p>
                <p class="text-gray-700 text-sm leading-relaxed">{{ $data['terms'] }}</p>
            </div>
            @endif
        </div>
        @endif

        <!-- Footer -->
        <div class="text-center pt-8 border-t-2 border-gray-200">
            @if(!empty($data['footer']))
                <p class="text-gray-600 text-sm">{{ $data['footer'] }}</p>
            @else
                <p class="text-gray-600 text-sm">Thank you for your business!</p>
            @endif
            <p class="text-gray-400 text-xs mt-2">Generated with WhizzIQ Invoice Builder</p>
        </div>
    </div>
</div>
