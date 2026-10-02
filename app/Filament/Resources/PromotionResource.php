<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionResource\Pages;
use App\Models\Accommodation;
use App\Models\Promotion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromotionResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    use Translatable;

    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Pricing';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('code')->unique(ignoreRecord: true)->maxLength(40)
                    ->dehydrateStateUsing(fn ($state) => $state ? strtoupper(trim($state)) : null)
                    ->helperText(__('Leave empty to apply automatically to every eligible booking.')),
                Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                Forms\Components\Select::make('type')->options(['percent' => __('Percentage'), 'fixed' => __('Fixed amount')])->required()->default('percent'),
                Forms\Components\TextInput::make('value')->numeric()->required(),
            ])->columns(2),
            Forms\Components\Section::make(__('Rules'))->schema([
                Forms\Components\DatePicker::make('bookable_from')->native(false)->label(__('Book from')),
                Forms\Components\DatePicker::make('bookable_until')->native(false)->label(__('Book until')),
                Forms\Components\DatePicker::make('stay_from')->native(false)->label(__('Stay from')),
                Forms\Components\DatePicker::make('stay_until')->native(false)->label(__('Stay until')),
                Forms\Components\TextInput::make('min_nights')->numeric(),
                Forms\Components\TextInput::make('max_uses')->numeric()->helperText(__('Empty = unlimited')),
                Forms\Components\Select::make('accommodation_ids')->label(__('Only for these stays'))->multiple()->placeholder(__('All stays'))
                    ->options(fn () => Accommodation::all()->mapWithKeys(fn ($a) => [$a->id => $a->name]))->columnSpanFull(),
                Forms\Components\Toggle::make('show_on_site')->label(__('Show as banner on home page')),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->weight('semibold'),
                Tables\Columns\TextColumn::make('code')->badge()->placeholder(__('Automatic')),
                Tables\Columns\TextColumn::make('value')->formatStateUsing(fn (Promotion $p) => $p->type === 'percent' ? (float) $p->value.'%' : number_format($p->value)),
                Tables\Columns\TextColumn::make('used_count')->label(__('Used'))->formatStateUsing(fn (Promotion $p) => $p->used_count.($p->max_uses ? ' / '.$p->max_uses : '')),
                Tables\Columns\TextColumn::make('bookable_until')->date('j M Y')->placeholder(__('No end')),
                Tables\Columns\ToggleColumn::make('show_on_site')->label(__('Banner')),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}
