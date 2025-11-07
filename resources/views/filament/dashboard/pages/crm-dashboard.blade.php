<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $dealsByStage = $this->getDealsByStage();
        $revenueByMonth = $this->getRevenueByMonth();
        $topContacts = $this->getTopContacts();
        $upcomingDeals = $this->getUpcomingDeals();
        $overdueFollowUps = $this->getOverdueFollowUps();
        $recentInteractions = $this->getRecentInteractions();
        $contactsByType = $this->getContactsByType();
        $relationshipHealth = $this->getRelationshipHealth();
    @endphp

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Contacts --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Contacts</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['contacts']['total'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $stats['contacts']['active'] }} active
                    </p>
                </div>
                <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-user-group class="w-7 h-7 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </div>

        {{-- Open Deals --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Open Deals</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['deals']['open'] }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                        {{ $stats['deals']['win_rate'] }}% win rate
                    </p>
                </div>
                <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-briefcase class="w-7 h-7 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </div>

        {{-- Pipeline Value --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pipeline Value</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['revenue']['pipeline_value'], 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ~${{ number_format($stats['revenue']['weighted_value'], 0) }} weighted
                    </p>
                </div>
                <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-currency-dollar class="w-7 h-7 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </div>

        {{-- Total Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['revenue']['total_won'], 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $stats['deals']['won'] }} deals won
                    </p>
                </div>
                <div class="w-14 h-14 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-trophy class="w-7 h-7 text-yellow-600 dark:text-yellow-400" />
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Deals by Stage Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700 lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Deals by Stage</h3>
            <canvas id="dealsByStageChart" height="250"></canvas>
        </div>

        {{-- Contacts by Type Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contacts by Type</h3>
            <canvas id="contactsByTypeChart" height="200"></canvas>
        </div>
    </div>

    {{-- Revenue Trend Chart --}}
    @if(count($revenueByMonth['months']) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Revenue Trend (Last 6 Months)</h3>
        <canvas id="revenueTrendChart" height="100"></canvas>
    </div>
    @endif

    {{-- Data Tables Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Top Contacts --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Contacts by Value</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($topContacts as $contact)
                <a href="{{ route('filament.dashboard.resources.contacts.edit', $contact['id']) }}"
                   class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $contact['name'] }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $contact['company'] ?? 'No company' }}</p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="font-semibold text-gray-900 dark:text-white">${{ number_format($contact['lifetime_value'], 0) }}</p>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $contact['relationship_strength'] === 'hot' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $contact['relationship_strength'] === 'warm' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $contact['relationship_strength'] === 'cold' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' : '' }}">
                                {{ ucfirst($contact['relationship_strength']) }}
                            </span>
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-8 text-center text-gray-400 dark:text-gray-500">
                    No contacts yet
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upcoming Deals --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Deals Closing Soon (30 Days)</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($upcomingDeals as $deal)
                <a href="{{ route('filament.dashboard.resources.deals.edit', $deal['id']) }}"
                   class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $deal['title'] }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $deal['contact_name'] ?? 'No contact' }}</p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="font-semibold text-gray-900 dark:text-white">${{ number_format($deal['value'], 0) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $deal['expected_close_date'] }}</p>
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-8 text-center text-gray-400 dark:text-gray-500">
                    No upcoming deals
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Alerts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Overdue Follow-ups --}}
        @if(count($overdueFollowUps) > 0)
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow border border-red-200 dark:border-red-800">
            <div class="p-6 border-b border-red-200 dark:border-red-800">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                    <h3 class="text-lg font-semibold text-red-900 dark:text-red-100">Overdue Follow-ups</h3>
                </div>
            </div>
            <div class="divide-y divide-red-200 dark:divide-red-800">
                @foreach($overdueFollowUps as $contact)
                <a href="{{ route('filament.dashboard.resources.contacts.edit', $contact['id']) }}"
                   class="block p-4 hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-red-900 dark:text-red-100">{{ $contact['name'] }}</p>
                            <p class="text-sm text-red-700 dark:text-red-300">{{ $contact['company'] ?? 'No company' }}</p>
                        </div>
                        <div class="text-right text-red-800 dark:text-red-200">
                            <p class="text-sm font-semibold">{{ $contact['days_overdue'] }} days overdue</p>
                            <p class="text-xs">Due: {{ $contact['next_follow_up_date'] }}</p>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Recent Interactions --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentInteractions as $interaction)
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-1">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30">
                                <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                            </span>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $interaction['contact_name'] ?? 'Unknown' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $interaction['subject'] ?? ucfirst($interaction['type']) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $interaction['interaction_date'] }}</p>
                        </div>
                        @if($interaction['outcome'])
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $interaction['outcome'] === 'positive' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                            {{ $interaction['outcome'] === 'neutral' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' : '' }}
                            {{ $interaction['outcome'] === 'negative' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                            {{ ucfirst($interaction['outcome']) }}
                        </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-400 dark:text-gray-500">
                    No recent interactions
                </div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Deals by Stage Chart
            const dealsByStageCtx = document.getElementById('dealsByStageChart');
            if (dealsByStageCtx) {
                new Chart(dealsByStageCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(array_keys($dealsByStage)) !!},
                        datasets: [{
                            label: 'Number of Deals',
                            data: {!! json_encode(array_values($dealsByStage)) !!},
                            backgroundColor: [
                                'rgba(156, 163, 175, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(251, 191, 36, 0.8)',
                                'rgba(249, 115, 22, 0.8)',
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(239, 68, 68, 0.8)',
                            ],
                            borderColor: [
                                'rgb(156, 163, 175)',
                                'rgb(59, 130, 246)',
                                'rgb(251, 191, 36)',
                                'rgb(249, 115, 22)',
                                'rgb(34, 197, 94)',
                                'rgb(239, 68, 68)',
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Contacts by Type Chart
            const contactsByTypeCtx = document.getElementById('contactsByTypeChart');
            if (contactsByTypeCtx) {
                new Chart(contactsByTypeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode(array_keys($contactsByType)) !!},
                        datasets: [{
                            data: {!! json_encode(array_values($contactsByType)) !!},
                            backgroundColor: [
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(251, 191, 36, 0.8)',
                                'rgba(147, 51, 234, 0.8)',
                                'rgba(156, 163, 175, 0.8)',
                            ],
                            borderColor: [
                                'rgb(34, 197, 94)',
                                'rgb(59, 130, 246)',
                                'rgb(251, 191, 36)',
                                'rgb(147, 51, 234)',
                                'rgb(156, 163, 175)',
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }

            // Revenue Trend Chart
            const revenueTrendCtx = document.getElementById('revenueTrendChart');
            if (revenueTrendCtx) {
                new Chart(revenueTrendCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($revenueByMonth['months']) !!},
                        datasets: [{
                            label: 'Revenue ($)',
                            data: {!! json_encode($revenueByMonth['revenue']) !!},
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
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
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
