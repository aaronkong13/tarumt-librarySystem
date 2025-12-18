<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

class CheckExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and expire old reservation notifications, notify next person in queue';

    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        parent::__construct();
        $this->reservationService = $reservationService;
    }

    /**
     * Execute the console command.
     * 
     * This command runs automatically every hour via scheduler
     * Implements the 3-day expiry rule:
     * - Finds reservations with expired notifications
     * - Marks them as expired
     * - Notifies next person in queue
     */
    public function handle()
    {
        $this->info('Checking for expired reservations...');

        try {
            $result = $this->reservationService->checkAndExpireReservations();

            $expiredCount = $result['expired_count'];
            $notifiedCount = $result['notified_count'];

            if ($expiredCount > 0) {
                $this->info("✓ Expired {$expiredCount} reservation(s)");
            }

            if ($notifiedCount > 0) {
                $this->info("✓ Notified {$notifiedCount} next user(s) in queue");
            }

            if ($expiredCount === 0 && $notifiedCount === 0) {
                $this->info('No expired reservations found.');
            }

            $this->info('✓ Reservation check completed successfully.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error checking expired reservations: ' . $e->getMessage());
            return 1;
        }
    }
}
