<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/** Custom page titles, navigation labels and groups through __(). */
trait TranslatesPageLabels
{
    public static function getNavigationLabel(): string
    {
        return __label(parent::getNavigationLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ? __label(static::$navigationGroup) : null;
    }

    public function getTitle(): string | Htmlable
    {
        $title = parent::getTitle();

        return is_string($title) ? __label($title) : $title;
    }
}
