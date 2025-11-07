<x-filament-panels::page>
    {{-- Key Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- Win Rate --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Win Rate</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        {{ $winRateStats['win_rate'] }}%
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $winRateStats['won'] }}/{{ $winRateStats['total_closed'] }} closed deals
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-trophy class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </div>

        {{-- Avg Cycle Time --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Avg Cycle Time</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        {{ $avgCycleTime ?? 'N/A' }}
                        @if($avgCycleTime)
                            <span class="text-sm">days</span>
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">From lead to close</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-clock class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </div>

        {{-- Total Pipeline --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Pipeline</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        ${{ number_format(array_sum(array_column($stageDistribution, 'value')), 0) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ array_sum(array_column($stageDistribution, 'count')) }} open deals
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-banknotes class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </div>

        {{-- Next Month Forecast --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Next Month</p>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">
                        ${{ number_format($monthlyForecast[0]['weighted'] ?? 0, 0) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Weighted forecast</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-calendar class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Forecast Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">6-Month Revenue Forecast</h3>

        <canvas id="monthlyForecastChart" height="80"></canvas>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Quarterly Forecast --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quarterly Forecast</h3>

            <div class="space-y-4">
                @foreach($quarterlyForecast as $quarter)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $quarter['quarter'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $quarter['start_date'] }} - {{ $quarter['end_date'] }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Weighted</p>
                                <p class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                    ${{ number_format($quarter['weighted'], 0) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Committed (80%+)</p>
                                <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                    ${{ number_format($quarter['committed'], 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pipeline Distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pipeline by Stage</h3>

            <canvas id="stageDistributionChart" height="200"></canvas>

            <div class="mt-4 space-y-2">
                @foreach($stageDistribution as $stage => $data)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ $data['label'] }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ $data['count'] }} deals • ${{ number_format($data['value'], 0) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top Forecast Deals --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Deals in Next 3 Months</h3>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left text-sm font-semibold text-gray-900 dark:text-white py-3">Deal</th>
                        <th class="text-left text-sm font-semibold text-gray-900 dark:text-white py-3">Contact</th>
                        <th class="text-left text-sm font-semibold text-gray-900 dark:text-white py-3">Stage</th>
                        <th class="text-left text-sm font-semibold text-gray-900 dark:text-white py-3">Value</th>
                        <th class="text-left text-sm font-semibold text-gray-900 dark:text-white py-3">Probability</th>
                        <th class="text-left text-sm font-semibold text-gray-900 dark:text-white py-3">Weighted Value</th>
                        <th class="text-left text-sm font-semibold text-gray-900 dark:text-white py-3">Close Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topDeals as $deal)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-3 text-sm text-gray-900 dark:text-white">{{ $deal->title }}</td>
                            <td class="py-3 text-sm text-gray-600 dark:text-gray-400">{{ $deal->contact?->name ?? 'N/A' }}</td>
                            <td class="py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    {{ $deal->stage === 'negotiation' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                                    {{ $deal->stage === 'proposal' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                    {{ $deal->stage === 'qualified' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    {{ $deal->stage === 'lead' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' : '' }}">
                                    {{ $deal->stage_label }}
                                </span>
                            </td>
                            <td class="py-3 text-sm font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($deal->value, 0) }}
                            </td>
                            <td class="py-3 text-sm text-gray-600 dark:text-gray-400">{{ $deal->probability }}%</td>
                            <td class="py-3 text-sm font-semibold text-purple-600 dark:text-purple-400">
                                ${{ number_format($deal->weighted_value, 0) }}
                            </td>
                            <td class="py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ $deal->expected_close_date?->format('M d, Y') ?? 'TBD' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Monthly Forecast Chart
                const monthlyCtx = document.getElementById('monthlyForecastChart').getContext('2d');
                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode(array_column($monthlyForecast, 'month')) !!},
                        datasets: [
                            {
                                label: 'Weighted Forecast',
                                data: {!! json_encode(array_column($monthlyForecast, 'weighted')) !!},
                                borderColor: 'rgb(147, 51, 234)',
                                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Committed (80%+)',
                                data: {!! json_encode(array_column($monthlyForecast, 'committed')) !!},
                                borderColor: 'rgb(34, 197, 94)',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Best Case',
                                data: {!! json_encode(array_column($monthlyForecast, 'best_case')) !!},
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Historical Avg',
                                data: {!! json_encode(array_column($monthlyForecast, 'historical_avg')) !!},
                                borderColor: 'rgb(156, 163, 175)',
                                borderDash: [5, 5],
                                fill: false,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // Stage Distribution Chart
                const stageCtx = document.getElementById('stageDistributionChart').getContext('2d');
                new Chart(stageCtx, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode(array_column($stageDistribution, 'label')) !!},
                        datasets: [{
                            data: {!! json_encode(array_column($stageDistribution, 'value')) !!},
                            backgroundColor: [
                                'rgba(156, 163, 175, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(251, 191, 36, 0.8)',
                                'rgba(249, 115, 22, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': $' + context.parsed.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</x-filament-panels::page>
