<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingExtra extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['service_date' => 'date', 'unit_price' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }
}
