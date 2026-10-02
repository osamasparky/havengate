<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';         // everything incl. users, settings, pricing
    case Manager = 'manager';     // operations, pricing, content
    case Reception = 'reception'; // bookings, guests, payments, calendar

    public function getLabel(): string
    {
        return __(ucfirst($this->value));
    }
}
