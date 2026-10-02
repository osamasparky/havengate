<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case NoShow = 'no_show';

    /** Statuses that occupy inventory. Pending only while its hold is valid. */
    public static function occupying(): array
    {
        return [self::Pending, self::Confirmed, self::CheckedIn];
    }

    public function isActive(): bool
    {
        return in_array($this, self::occupying(), true);
    }

    public function getLabel(): string
    {
        return __('booking.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::CheckedIn => 'info',
            self::CheckedOut => 'gray',
            self::Cancelled, self::NoShow => 'danger',
            self::Expired => 'gray',
        };
    }
}
