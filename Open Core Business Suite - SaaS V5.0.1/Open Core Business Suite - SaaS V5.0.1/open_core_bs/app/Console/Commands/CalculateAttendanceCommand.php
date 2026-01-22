<?php

namespace App\Console\Commands;

use App\Services\AttendanceCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateAttendanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:calculate
                            {--date= : Specific date to calculate (Y-m-d)}
                            {--start-date= : Start date for range calculation (Y-m-d)}
                            {--end-date= : End date for range calculation (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate attendance metrics (working hours, late hours, overtime, etc.)';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected AttendanceCalculationService $attendanceService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting attendance calculation...');

        // Determine date range
        if ($this->option('start-date') && $this->option('end-date')) {
            $startDate = Carbon::parse($this->option('start-date'));
            $endDate = Carbon::parse($this->option('end-date'));
            $this->info("Processing date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        } elseif ($this->option('date')) {
            $startDate = Carbon::parse($this->option('date'));
            $endDate = $startDate->copy();
            $this->info("Processing date: {$startDate->format('Y-m-d')}");
        } else {
            // Default: yesterday
            $startDate = Carbon::yesterday();
            $endDate = $startDate->copy();
            $this->info("Processing yesterday: {$startDate->format('Y-m-d')}");
        }

        // Use service to calculate
        $stats = $this->attendanceService->calculateForDateRange($startDate, $endDate);

        // Display results
        $this->info("\n✓ Calculation completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Dates Processed', count($stats['dates'])],
                ['Attendance Records Calculated', $stats['processed']],
                ['Absence Records Created', $stats['absents_created']],
                ['Errors', $stats['errors']],
            ]
        );

        if (! empty($stats['dates'])) {
            $this->info("\nProcessed dates: ".implode(', ', $stats['dates']));
        }

        return $stats['errors'] === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
