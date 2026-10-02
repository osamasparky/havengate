<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExperienceResource\Pages;
use App\Models\Experience;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ExperienceResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    use Translatable;

    protected static ?string $model = Experience::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')->required()->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set, ?Experience $record) => $record ? null : $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('summary')->rows(2)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->rows(6)->columnSpanFull(),
                Forms\Components\TextInput::make('schedule')->placeholder(__('Daily at sunrise')),
                Forms\Components\TextInput::make('duration_minutes')->numeric()->suffix(__('min')),
            ])->columns(2),
            Forms\Components\Section::make(__('Price & booking'))->schema([
                Forms\Components\TextInput::make('price')->numeric()->default(0)->prefix(config('heavengate.currency')),
                Forms\Components\Select::make('pricing_unit')->options(['per_person' => __('Per person'), 'per_group' => __('Per group')])->default('per_person'),
                Forms\Components\TextInput::make('max_people')->numeric(),
                Forms\Components\Toggle::make('is_addon')->label(__('Can be added during booking')),
                Forms\Components\Toggle::make('is_featured')->label(__('Show on home page')),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(3),
            Forms\Components\FileUpload::make('image')->image()->imageEditor()->directory('experiences')->maxSize(6144)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->state(fn ($record) => $record->imageUrl())->height(48),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('price')->money(config('heavengate.currency'))->description(fn ($record) => __(ucfirst(str_replace('_', ' ', $record->pricing_unit)))),
                Tables\Columns\IconColumn::make('is_addon')->boolean()->label(__('Add-on')),
                Tables\Columns\ToggleColumn::make('is_featured')->label(__('Home')),
                Tables\Columns\ToggleColumn::make('is_active')->label(__('Active')),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExperiences::route('/'),
            'create' => Pages\CreateExperience::route('/create'),
            'edit' => Pages\EditExperience::route('/{record}/edit'),
        ];
    }
}
