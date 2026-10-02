<?php

namespace App\Filament\Resources;

use App\Enums\AdjustmentType;
use App\Filament\Resources\SeasonalRateResource\Pages;
use App\Models\Accommodation;
use App\Models\SeasonalRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SeasonalRateResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    use Translatable;

    protected static ?string $model = SeasonalRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-sun';

    protected static ?string $navigationGroup = 'Pricing';

    protected static ?string $navigationLabel = 'Seasonal rates';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->placeholder(__('Summer peak'))->helperText(__('Shown to guests next to the nightly rate.')),
            Forms\Components\Select::make('accommodation_id')->label(__('Applies to'))->placeholder(__('All stays'))
                ->options(fn () => Accommodation::all()->mapWithKeys(fn ($a) => [$a->id => $a->name])),
            Forms\Components\DatePicker::make('starts_on')->required()->native(false)->label(__('First night')),
            Forms\Components\DatePicker::make('ends_on')->required()->native(false)->label(__('Last night'))->afterOrEqual('starts_on'),
            Forms\Components\Select::make('adjustment_type')->options(AdjustmentType::class)->required()->live()->default(AdjustmentType::Percent),
            Forms\Components\TextInput::make('value')->numeric()->required()
                ->helperText(fn (Forms\Get $get) => match ($get('adjustment_type')) {
                    'percent', AdjustmentType::Percent => __('e.g. 25 for +25%, -15 for a 15% discount.'),
                    'amount', AdjustmentType::Amount => __('e.g. 500 adds 500 per night; -300 removes 300.'),
                    default => __('The nightly price for these dates.'),
                }),
            Forms\Components\CheckboxList::make('weekdays')->label(__('Only these nights (optional)'))->columns(7)->columnSpanFull()
                ->options([0 => __('Sun'), 1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat')]),
            Forms\Components\TextInput::make('min_nights')->numeric()->helperText(__('Rule applies only if the stay is at least this long (e.g. long-stay deals).')),
            Forms\Components\TextInput::make('priority')->numeric()->default(10)->helperText(__('When rules overlap, higher wins.')),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('starts_on')
            ->columns([
                Tables\Columns\TextColumn::make('name')->weight('semibold'),
                Tables\Columns\TextColumn::make('accommodation.name')->label(__('Stay'))->placeholder(__('All stays')),
                Tables\Columns\TextColumn::make('starts_on')->date('j M Y')->sortable(),
                Tables\Columns\TextColumn::make('ends_on')->date('j M Y'),
                Tables\Columns\TextColumn::make('value')->formatStateUsing(fn (SeasonalRate $r) => match ($r->adjustment_type) {
                    AdjustmentType::Percent => ($r->value > 0 ? '+' : '').(float) $r->value.'%',
                    AdjustmentType::Amount => ($r->value > 0 ? '+' : '').number_format($r->value),
                    AdjustmentType::Fixed => number_format($r->value).' fixed',
                }),
                Tables\Columns\TextColumn::make('priority')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->filters([Tables\Filters\Filter::make('current')->label(__('Current & future'))->query(fn ($query) => $query->whereDate('ends_on', '>=', today()))->default()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\ReplicateAction::make()->label(__('Duplicate')), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeasonalRates::route('/'),
            'create' => Pages\CreateSeasonalRate::route('/create'),
            'edit' => Pages\EditSeasonalRate::route('/{record}/edit'),
        ];
    }
}
