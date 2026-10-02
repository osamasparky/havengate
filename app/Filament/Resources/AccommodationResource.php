<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccommodationResource\Pages;
use App\Filament\Resources\AccommodationResource\RelationManagers;
use App\Models\Accommodation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AccommodationResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    use Translatable;

    protected static ?string $model = Accommodation::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stays';

    protected static ?string $modelLabel = 'stay';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->columnSpanFull()->tabs([
                Forms\Components\Tabs\Tab::make(__('Content'))->icon('heroicon-o-pencil-square')->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set, ?Accommodation $record) => $record ? null : $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->helperText(__('Used in the URL. Keep it in English.')),
                    Forms\Components\TextInput::make('tagline')->maxLength(160)->columnSpanFull(),
                    Forms\Components\Textarea::make('description')->rows(8)->columnSpanFull()->helperText(__('Separate paragraphs with an empty line.')),
                    Forms\Components\Textarea::make('highlights')->rows(5)->helperText(__('One highlight per line.')),
                    Forms\Components\TextInput::make('bed_configuration')->maxLength(120),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make(__('Capacity & features'))->icon('heroicon-o-users')->schema([
                    Forms\Components\Select::make('category')->options(['chalet' => __('Chalet'), 'family' => __('Family chalet'), 'hut' => __('Beach hut'), 'suite' => __('Suite')])->required(),
                    Forms\Components\TextInput::make('size_sqm')->numeric()->suffix('m²'),
                    Forms\Components\TextInput::make('base_occupancy')->numeric()->required()->minValue(1)->helperText(__('Guests included in the nightly price.')),
                    Forms\Components\TextInput::make('max_guests')->numeric()->required()->minValue(1),
                    Forms\Components\TextInput::make('max_adults')->numeric()->required()->minValue(1),
                    Forms\Components\TextInput::make('max_children')->numeric()->required()->minValue(0),
                    Forms\Components\CheckboxList::make('features')->columnSpanFull()->columns(3)->options([
                        'sea_view' => __('Sea view'), 'ac' => __('Air conditioning'), 'fan' => __('Fan'), 'private_bathroom' => __('Private bathroom'),
                        'shared_bathroom' => __('Shared bathroom'), 'terrace' => __('Terrace'), 'wifi' => __('Wi-Fi'), 'towels' => __('Towels & linen'),
                        'family' => __('Family friendly'), 'beachfront' => __('Beachfront'),
                    ]),
                    Forms\Components\Select::make('facilities')->relationship('facilities', 'name')->multiple()->preload()->columnSpanFull()
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                ])->columns(3),

                Forms\Components\Tabs\Tab::make(__('Pricing'))->icon('heroicon-o-banknotes')->schema([
                    Forms\Components\TextInput::make('base_price')->numeric()->required()->prefix(config('heavengate.currency'))->helperText(__('Per night, weekdays.')),
                    Forms\Components\TextInput::make('weekend_price')->numeric()->prefix(config('heavengate.currency'))->helperText(__('Thu & Fri nights (Settings → weekend nights). Empty = base price.')),
                    Forms\Components\TextInput::make('extra_adult_fee')->numeric()->default(0)->prefix(config('heavengate.currency'))->helperText(__('Per night, per adult above base occupancy.')),
                    Forms\Components\TextInput::make('extra_child_fee')->numeric()->default(0)->prefix(config('heavengate.currency')),
                    Forms\Components\TextInput::make('min_nights')->numeric()->default(1)->minValue(1),
                    Forms\Components\Placeholder::make('seasons_hint')->label(__('Seasonal pricing'))
                        ->content(__('Manage holidays and high seasons under Pricing → Seasonal rates.')),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make(__('Media & SEO'))->icon('heroicon-o-photo')->schema([
                    Forms\Components\FileUpload::make('cover_image')->image()->imageEditor()->directory('stays')->maxSize(6144)
                        ->helperText(__('Portrait 4:5, at least 1600px tall. Shown inside the arch frame.'))->columnSpanFull(),
                    Forms\Components\TextInput::make('meta_title')->maxLength(70),
                    Forms\Components\Textarea::make('meta_description')->rows(2)->maxLength(160),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make(__('Visibility'))->icon('heroicon-o-eye')->schema([
                    Forms\Components\Toggle::make('is_active')->label(__('Bookable & visible'))->default(true),
                    Forms\Components\Toggle::make('is_featured')->label(__('Feature on home page')),
                    Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                ])->columns(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')->label('')->state(fn ($record) => media_url($record->cover_image))->height(56)->extraImgAttributes(['style' => 'border-radius:999px 999px 4px 4px']),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold')->description(fn ($record) => $record->tagline),
                Tables\Columns\TextColumn::make('units_count')->counts('units')->label(__('Units'))->badge(),
                Tables\Columns\TextColumn::make('max_guests')->label(__('Max guests')),
                Tables\Columns\TextColumn::make('base_price')->money(config('heavengate.currency'))->label(__('Base / night')),
                Tables\Columns\TextColumn::make('weekend_price')->money(config('heavengate.currency'))->label(__('Weekend')),
                Tables\Columns\ToggleColumn::make('is_active')->label(__('Active')),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UnitsRelationManager::class,
            RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccommodations::route('/'),
            'create' => Pages\CreateAccommodation::route('/create'),
            'edit' => Pages\EditAccommodation::route('/{record}/edit'),
        ];
    }
}
