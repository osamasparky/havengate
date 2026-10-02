<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentProvider: string implements HasLabel
{
    case EasyKash = 'easykash';
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case InstaPay = 'instapay';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::EasyKash => 'EasyKash',
            self::Cash => __('booking.provider.cash'),
            self::BankTransfer => __('booking.provider.bank_transfer'),
            self::InstaPay => 'InstaPay',
            self::Manual => __('booking.provider.manual'),
        };
    }
}
