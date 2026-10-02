<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockedDateResource\Pages;
use App\Models\Accommodation;
use App\Models\BlockedDate;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlockedDateResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = BlockedDate::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationGroup = 'Pricing';

    protected static ?string $navigationLabel = 'Blocked dates';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('accommodation_id')->label(__('Stay'))->placeholder(__('Whole camp'))->live()
                ->options(fn () => Accommodation::all()->mapWithKeys(fn ($a) => [$a->id => $a->name])),
            Forms\Components\Select::make('unit_id')->label(__('Unit'))->placeholder(__('All units of the stay'))
                ->options(fn (Forms\Get $get) => Unit::when($get('accommodation_id'), fn ($q, $id) => $q->where('accommodation_id', $id))->pluck('code', 'id')),
            Forms\Components\DatePicker::make('starts_on')->required()->native(false)->label(__('First blocked night')),
            Forms\Components\DatePicker::make('ends_on')->required()->native(false)->label(__('Last blocked night'))->afterOrEqual('starts_on'),
            Forms\Components\TextInput::make('reason')->placeholder(__('Maintenance, private event…'))->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('starts_on')
            ->columns([
                Tables\Columns\TextColumn::make('target')->label(__('Blocks'))->state(fn (BlockedDate $b) => $b->targetLabel()),
                Tables\Columns\TextColumn::make('starts_on')->date('D j M Y')->sortable(),
                Tables\Columns\TextColumn::make('ends_on')->date('D j M Y'),
                Tables\Columns\TextColumn::make('reason')->placeholder(__('—')),
            ])
            ->filters([Tables\Filters\Filter::make('upcoming')->query(fn ($query) => $query->whereDate('ends_on', '>=', today()))->default()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlockedDates::route('/'),
            'create' => Pages\CreateBlockedDate::route('/create'),
            'edit' => Pages\EditBlockedDate::route('/{record}/edit'),
        ];
    }
}
