<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Relation manager tab titles and record labels ("New payment") through __(). Set $modelLabel in English. */
trait TranslatesRelationLabels
{
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __label(parent::getTitle($ownerRecord, $pageClass));
    }

    protected static function getModelLabel(): ?string
    {
        return static::$modelLabel ? __label(static::$modelLabel) : null;
    }

    protected static function getPluralModelLabel(): ?string
    {
        return static::$modelLabel ? __label(static::$pluralModelLabel ?? Str::plural(static::$modelLabel)) : null;
    }
}
