<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdjustmentType: string implements HasLabel
{
    case Fixed = 'fixed';     // replaces nightly price
    case Percent = 'percent'; // ± % of base (e.g. 25 or -15)
    case Amount = 'amount';   // ± amount on base

    public function apply(float $base, float $value): float
    {
        return max(0, round(match ($this) {
            self::Fixed => $value,
            self::Percent => $base * (1 + $value / 100),
            self::Amount => $base + $value,
        }, 2));
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => __('Fixed nightly price'),
            self::Percent => __('Percentage of base (±%)'),
            self::Amount => __('Amount on base (±)'),
        };
    }
}
