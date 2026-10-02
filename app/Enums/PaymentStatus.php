<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status of a single payment transaction. */
enum PaymentStatus: string implements HasColor, HasLabel
{
    case Initiated = 'initiated';
    case Pending = 'pending';   // e.g. Fawry voucher issued, awaiting cash payment
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return __('booking.payment.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Pending, self::Initiated => 'warning',
            self::Failed, self::Expired => 'danger',
            self::Refunded => 'gray',
        };
    }
}
