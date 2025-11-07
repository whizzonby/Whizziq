<?php

namespace App\Console\Commands;

use App\Models\TaxPeriod;
use App\Models\TaxSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateTaxPeriodsCommand extends Command
{
    protected $signature = 'tax:create-periods {--year= : The year to create periods for}';

    protected $description = 'Automatically create tax periods for all users based on their settings';

    public function handle()
    {
        $year = $this->option('year') ?? now()->year;

        $this->info("Creating tax periods for year {$year}...");

        // Get all users with tax settings
        $users = User::whereHas('taxSetting')->with('taxSetting')->get();

        $created = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $taxSetting = $user->taxSetting;

            if (!$taxSetting) {
                continue;
            }

            // Create periods based on filing frequency
            if ($taxSetting->filing_frequency === 'quarterly') {
                $created += $this->createQuarterlyPeriods($user, $taxSetting, $year);
            } else {
                $created += $this->createAnnualPeriod($user, $taxSetting, $year);
            }
        }

        $this->info("✓ Created {$created} tax periods for {$users->count()} users.");

        return Command::SUCCESS;
    }

    protected function createQuarterlyPeriods(User $user, TaxSetting $taxSetting, int $year): int
    {
        $created = 0;

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $startDate = Carbon::create($year, $startMonth, 1);
            $endDate = $startDate->copy()->addMonths(3)->subDay();

            // Check if period already exists
            $exists = TaxPeriod::where('user_id', $user->id)
                ->where('type', 'quarterly')
                ->where('start_date', $startDate)
                ->exists();

            if ($exists) {
                continue;
            }

            // Create the period
            TaxPeriod::create([
                'user_id' => $user->id,
                'name' => "Q{$quarter} {$year}",
                'type' => 'quarterly',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'filing_deadline' => $this->getQuarterlyFilingDeadline($year, $quarter),
                'status' => $endDate->isFuture() ? 'active' : 'closed',
            ]);

            $created++;
        }

        return $created;
    }

    protected function createAnnualPeriod(User $user, TaxSetting $taxSetting, int $year): int
    {
        // Use fiscal year end if set, otherwise calendar year
        if ($taxSetting->fiscal_year_end) {
            $fiscalYearEnd = $taxSetting->fiscal_year_end;
            $endDate = Carbon::create($year, $fiscalYearEnd->month, $fiscalYearEnd->day);
            $startDate = $endDate->copy()->subYear()->addDay();
        } else {
            $startDate = Carbon::create($year, 1, 1);
            $endDate = Carbon::create($year, 12, 31);
        }

        // Check if period already exists
        $exists = TaxPeriod::where('user_id', $user->id)
            ->where('type', 'annual')
            ->where('start_date', $startDate)
            ->exists();

        if ($exists) {
            return 0;
        }

        // Create the period
        TaxPeriod::create([
            'user_id' => $user->id,
            'name' => "FY {$year}",
            'type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'filing_deadline' => $this->getAnnualFilingDeadline($year, $taxSetting),
            'status' => $endDate->isFuture() ? 'active' : 'closed',
        ]);

        return 1;
    }

    protected function getQuarterlyFilingDeadline(int $year, int $quarter): Carbon
    {
        // Standard US quarterly deadlines
        $deadlines = [
            1 => Carbon::create($year, 4, 15), // Q1: April 15
            2 => Carbon::create($year, 6, 15), // Q2: June 15
            3 => Carbon::create($year, 9, 15), // Q3: September 15
            4 => Carbon::create($year + 1, 1, 15), // Q4: January 15 next year
        ];

        return $deadlines[$quarter];
    }

    protected function getAnnualFilingDeadline(int $year, TaxSetting $taxSetting): Carbon
    {
        // Default to April 15 (US tax day)
        // For fiscal year, add 3.5 months after fiscal year end
        if ($taxSetting->fiscal_year_end) {
            return Carbon::create($year, $taxSetting->fiscal_year_end->month, $taxSetting->fiscal_year_end->day)
                ->addMonths(3)
                ->addDays(15);
        }

        return Carbon::create($year + 1, 4, 15);
    }
}
