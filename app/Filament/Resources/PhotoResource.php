<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhotoResource\Pages;
use App\Models\Photo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** General gallery (photos not attached to a specific stay). */
class PhotoResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    use Translatable;

    protected static ?string $model = Photo::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Gallery';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('photoable_id');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')->label(__('Photo'))->image()->imageEditor()->directory('gallery')->required()->maxSize(8192)->columnSpanFull(),
            Forms\Components\TextInput::make('caption'),
            Forms\Components\TextInput::make('alt')->label(__('Alt text')),
            Forms\Components\Select::make('category')->options(['camp' => __('Camp'), 'stay' => __('Stays'), 'beach' => __('Beach'), 'night' => __('Night'), 'food' => __('Food'), 'experience' => __('Experiences')])->required()->default('camp'),
            Forms\Components\Toggle::make('is_featured')->label(__('Show on home page')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort_order')->defaultSort('sort_order')
            ->contentGrid(['md' => 3, 'xl' => 4])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('path')->state(fn (Photo $p) => $p->url())->height(180)->width('100%')->extraImgAttributes(['style' => 'object-fit:cover;border-radius:12px;width:100%']),
                    Tables\Columns\TextColumn::make('caption')->weight('semibold'),
                    Tables\Columns\TextColumn::make('category')->badge()->formatStateUsing(fn ($state) => __(ucfirst(str_replace('_', ' ', (string) $state)))),
                ]),
            ])
            ->filters([Tables\Filters\SelectFilter::make('category')->options(['camp' => __('Camp'), 'stay' => __('Stays'), 'beach' => __('Beach'), 'night' => __('Night'), 'food' => __('Food'), 'experience' => __('Experiences')])])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhotos::route('/'),
            'create' => Pages\CreatePhoto::route('/create'),
            'edit' => Pages\EditPhoto::route('/{record}/edit'),
        ];
    }
}
