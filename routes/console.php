<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->daily();
