<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\ClientInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OutstandingInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    /**
     * Hide on main dashboard - only show on CRM Dashboard
     */
    public static function canView(): bool
    {
        $livewire = \Livewire\Livewire::current();
        if ($livewire instanceof \Filament\Pages\Dashboard) {
            return false;
        }
        return true;
    }

    public function getHeading(): string
    {
        return '📄 Invoice Summary';
    }

    protected function getStats(): array
    {
        $userId = Auth::id();

        // Total outstanding (sent + partial + overdue)
        $totalOwed = ClientInvoice::where('user_id', $userId)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->sum('balance_due');

        $outstandingCount = ClientInvoice::where('user_id', $userId)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->count();

        // Overdue invoices
        $overdueAmount = ClientInvoice::where('user_id', $userId)
            ->where('status', 'overdue')
            ->sum('balance_due');

        $overdueCount = ClientInvoice::where('user_id', $userId)
            ->where('status', 'overdue')
            ->count();

        // Paid this month
        $paidThisMonth = ClientInvoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total_amount');

        // Due this month
        $dueThisMonth = ClientInvoice::where('user_id', $userId)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('balance_due');

        return [
            Stat::make('You\'re Owed', '$' . number_format($totalOwed, 2))
                ->description("from {$outstandingCount} " . str('invoice')->plural($outstandingCount))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->chart($this->getRevenueChart()),

            Stat::make('Overdue Invoices', '$' . number_format($overdueAmount, 2))
                ->description("{$overdueCount} invoice" . ($overdueCount !== 1 ? 's' : '') . " need attention")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueCount > 0 ? 'danger' : 'success')
                ->url(route('filament.dashboard.resources.client-invoices.index', ['tableFilters' => ['status' => ['overdue']]])),

            Stat::make('Paid This Month', '$' . number_format($paidThisMonth, 2))
                ->description("Due this month: $" . number_format($dueThisMonth, 2))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }

    protected function getRevenueChart(): array
    {
        // Get last 7 days of payments
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $amount = ClientInvoice::where('user_id', Auth::id())
                ->where('status', 'paid')
                ->whereDate('paid_date', $date)
                ->sum('total_amount');
            $data[] = (float) $amount;
        }

        return $data;
    }
}
