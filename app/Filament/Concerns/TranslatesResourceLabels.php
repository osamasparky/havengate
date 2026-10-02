<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Str;

use function Filament\Support\get_model_label;

/**
 * Resource names, navigation labels and groups through __() so the admin
 * follows the staff member's language (keys are the English labels, see lang/ar.json).
 */
trait TranslatesResourceLabels
{
    protected static function englishModelLabel(): string
    {
        return static::$modelLabel ?? get_model_label(static::getModel());
    }

    public static function getModelLabel(): string
    {
        return __label(static::englishModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        return __label(static::$pluralModelLabel ?? Str::plural(static::englishModelLabel()));
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ? __label(static::$navigationLabel) : static::getTitleCasePluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ? __label(static::$navigationGroup) : null;
    }
}
