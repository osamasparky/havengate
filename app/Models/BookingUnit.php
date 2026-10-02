<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingUnit extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'nightly_rates' => 'array',
            'subtotal' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class)->withTrashed();
    }
}
