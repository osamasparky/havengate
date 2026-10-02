<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentBlockResource\Pages;
use App\Models\ContentBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Editable copy for home / camp / location sections. Keys are fixed by the templates. */
class ContentBlockResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    use Translatable;

    protected static ?string $model = ContentBlock::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Page sections';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')->disabled(),
            Forms\Components\TextInput::make('eyebrow'),
            Forms\Components\TextInput::make('title')->columnSpanFull()->helperText(__('Wrap a word in *asterisks* to set it in copper italic.')),
            Forms\Components\Textarea::make('body')->rows(5)->columnSpanFull(),
            Forms\Components\TextInput::make('cta_label')->label(__('Button label')),
            Forms\Components\TextInput::make('cta_url')->label(__('Button link'))->helperText(__('Site path like /contact (language is added automatically) or a full URL.')),
            Forms\Components\FileUpload::make('image')->image()->imageEditor()->directory('content')->columnSpanFull(),
            Forms\Components\FileUpload::make('video')->label(__('Background video'))->directory('content/video')
                ->acceptedFileTypes(['video/mp4', 'video/webm'])->maxSize(40 * 1024)->columnSpanFull()
                ->helperText(__('Home hero only. MP4/WebM, muted, 10–30 s loop, under 15 MB works best. The image is used as the poster and on slow connections.')),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('key')
            ->columns([
                Tables\Columns\TextColumn::make('key')->fontFamily('mono')->weight('semibold'),
                Tables\Columns\TextColumn::make('title')->limit(60),
                Tables\Columns\TextColumn::make('updated_at')->since(),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentBlocks::route('/'),
            'edit' => Pages\EditContentBlock::route('/{record}/edit'),
        ];
    }
}
