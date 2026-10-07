<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** How the guest chose to pay at checkout. */
enum PaymentMethod: string implements HasColor, HasLabel
{
    case Online = 'online';
    case BankTransfer = 'bank_transfer';
    case AtProperty = 'at_property';

    public function getLabel(): string
    {
        return __('booking.method.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Online => 'success',
            self::BankTransfer => 'info',
            self::AtProperty => 'warning',
        };
    }
}
