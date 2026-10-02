<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ExpireHolds extends Command
{
    protected $signature = 'bookings:expire-holds';

    protected $description = 'Release units held by unpaid bookings whose hold time has passed';

    public function handle(BookingService $bookings): int
    {
        $n = $bookings->expireHolds();
        $this->info("Released {$n} expired hold(s).");

        return self::SUCCESS;
    }
}
