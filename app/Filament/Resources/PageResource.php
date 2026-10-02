<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    use Translatable;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Pages & policies';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set, ?Page $record) => $record ? null : $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)
                ->helperText(__('cancellation-policy and house-rules are linked from the booking form.')),
            Forms\Components\RichEditor::make('body')->columnSpanFull()
                ->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo']),
            Forms\Components\TextInput::make('meta_title'),
            Forms\Components\TextInput::make('meta_description'),
            Forms\Components\Toggle::make('show_in_footer')->default(true),
            Forms\Components\Toggle::make('is_published')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->reorderable('sort_order')->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('title')->weight('semibold'),
                Tables\Columns\TextColumn::make('slug')->fontFamily('mono')->color('gray'),
                Tables\Columns\ToggleColumn::make('show_in_footer')->label(__('Footer')),
                Tables\Columns\ToggleColumn::make('is_published')->label(__('Published')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Page $p) => route('page', ['locale' => 'en', 'page' => $p->slug]), true),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
