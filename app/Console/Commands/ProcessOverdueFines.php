<?php

namespace App\Console\Commands;

use App\Services\FineService;
use Illuminate\Console\Command;

class ProcessOverdueFines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fines:process-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all overdue borrowings and create/update fines';

    protected FineService $fineService;

    public function __construct(FineService $fineService)
    {
        parent::__construct();
        $this->fineService = $fineService;
    }

    /**
     * Execute the console command.
     * 
     * This command runs automatically via scheduler (recommended: daily)
     * Finds all overdue borrowings and:
     * - Creates new fine records if none exist
     * - Updates existing fine amounts if days overdue increased
     * 
     * Fine calculation: $0.50 per day overdue, max $50.00 per book
     */
    public function handle()
    {
        $this->info('Processing overdue fines...');

        try {
            $results = $this->fineService->processOverdueFines();

            $createdCount = collect($results)->where('status', 'created_or_updated')->count();
            $errorCount = collect($results)->where('status', 'error')->count();

            if ($createdCount > 0) {
                $this->info("✓ Processed {$createdCount} overdue borrowing(s)");
                
                // Show details
                foreach ($results as $result) {
                    if ($result['status'] === 'created_or_updated') {
                        $this->line("  - Borrowing #{$result['borrowing_id']}: Fine #{$result['fine_id']} (RM " . number_format($result['amount'], 2) . ")");
                    }
                }
            }

            if ($errorCount > 0) {
                $this->warn("⚠ {$errorCount} borrowing(s) had errors");
                foreach ($results as $result) {
                    if ($result['status'] === 'error') {
                        $this->error("  - Borrowing #{$result['borrowing_id']}: {$result['error']}");
                    }
                }
            }

            if ($createdCount === 0 && $errorCount === 0) {
                $this->info('No overdue borrowings found.');
            }

            $this->info('Done!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error processing overdue fines: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
