<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        {{-- Revenue --}}
        <div class="bg-success-50 dark:bg-success-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-success-700 dark:text-success-400">Revenue</div>
            <div class="text-2xl font-bold text-success-900 dark:text-success-100 mt-1">
                ${{ number_format($record->revenue, 2) }}
            </div>
            @if($record->revenue_change_percentage != 0)
                <div class="text-xs mt-2 {{ $record->revenue_change_percentage > 0 ? 'text-success-600' : 'text-danger-600' }}">
                    {{ $record->revenue_change_percentage > 0 ? '↑' : '↓' }}
                    {{ abs($record->revenue_change_percentage) }}% from previous day
                </div>
            @endif
        </div>

        {{-- Expenses --}}
        <div class="bg-danger-50 dark:bg-danger-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-danger-700 dark:text-danger-400">Expenses</div>
            <div class="text-2xl font-bold text-danger-900 dark:text-danger-100 mt-1">
                ${{ number_format($record->expenses, 2) }}
            </div>
            @if($record->expenses_change_percentage != 0)
                <div class="text-xs mt-2 {{ $record->expenses_change_percentage > 0 ? 'text-danger-600' : 'text-success-600' }}">
                    {{ $record->expenses_change_percentage > 0 ? '↑' : '↓' }}
                    {{ abs($record->expenses_change_percentage) }}% from previous day
                </div>
            @endif
        </div>

        {{-- Profit --}}
        <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-primary-700 dark:text-primary-400">Profit</div>
            <div class="text-2xl font-bold text-primary-900 dark:text-primary-100 mt-1">
                ${{ number_format($record->profit, 2) }}
            </div>
            @if($record->profit_change_percentage != 0)
                <div class="text-xs mt-2 {{ $record->profit_change_percentage > 0 ? 'text-success-600' : 'text-danger-600' }}">
                    {{ $record->profit_change_percentage > 0 ? '↑' : '↓' }}
                    {{ abs($record->profit_change_percentage) }}% from previous day
                </div>
            @endif
        </div>

        {{-- Cash Flow --}}
        <div class="bg-info-50 dark:bg-info-900/20 p-4 rounded-lg">
            <div class="text-sm font-medium text-info-700 dark:text-info-400">Cash Flow</div>
            <div class="text-2xl font-bold text-info-900 dark:text-info-100 mt-1">
                ${{ number_format($record->cash_flow, 2) }}
            </div>
            @if($record->cash_flow_change_percentage != 0)
                <div class="text-xs mt-2 {{ $record->cash_flow_change_percentage > 0 ? 'text-success-600' : 'text-danger-600' }}">
                    {{ $record->cash_flow_change_percentage > 0 ? '↑' : '↓' }}
                    {{ abs($record->cash_flow_change_percentage) }}% from previous day
                </div>
            @endif
        </div>
    </div>

    {{-- Calculation Info --}}
    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg text-sm space-y-2">
        <div class="font-semibold text-gray-700 dark:text-gray-300">How these metrics are calculated:</div>
        <ul class="space-y-1 text-gray-600 dark:text-gray-400">
            <li>• <strong>Revenue:</strong> Sum of all revenue sources + invoice payments for this date</li>
            <li>• <strong>Expenses:</strong> Sum of all expenses for this date</li>
            <li>• <strong>Profit:</strong> Revenue - Expenses</li>
            <li>• <strong>Cash Flow:</strong> Cumulative (Revenue - Expenses) up to this date</li>
        </ul>
        <div class="pt-2 text-xs text-gray-500 dark:text-gray-500">
            Data auto-calculated from: Expenses, Revenue Sources, and Client Invoice Payments
        </div>
    </div>
</div>
