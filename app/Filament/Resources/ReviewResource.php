<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/** Guest ratings & reviews: approve, feature on the home page, reply publicly. */
class ReviewResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Reservations';

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return ($n = Review::where('is_approved', false)->count()) ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function stars(?int $rating): string
    {
        $rating = max(0, min(5, (int) $rating));

        return str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Review'))->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(120),
                Forms\Components\TextInput::make('email')->email()->required(),
                Forms\Components\TextInput::make('country')->maxLength(80),
                Forms\Components\Select::make('rating')->required()
                    ->options(collect(range(5, 1))->mapWithKeys(fn ($n) => [$n => static::stars($n)." ({$n})"])),
                Forms\Components\TextInput::make('title')->maxLength(160)->columnSpanFull(),
                Forms\Components\Textarea::make('comment')->required()->rows(5)->columnSpanFull(),
                Forms\Components\Placeholder::make('booking_ref')->label(__('Reservation'))
                    ->content(fn (?Review $record) => $record?->booking?->reference ?? __('—')),
                Forms\Components\Placeholder::make('subject')->label(__('Reviewed'))
                    ->content(fn (?Review $record) => $record?->subjectName() ?? __('Camp (general)')),
            ])->columns(2),
            Forms\Components\Section::make(__('Publishing'))->schema([
                Forms\Components\Toggle::make('is_approved')->label(__('Approved (visible on the website)')),
                Forms\Components\Toggle::make('is_featured')->label(__('Feature on the home page')),
                Forms\Components\Toggle::make('is_verified')->label(__('Verified stay'))->disabled()->dehydrated(false),
            ])->columns(3),
            Forms\Components\Section::make(__('Reply from the camp'))->schema([
                Forms\Components\Textarea::make('reply')->label(__('Public reply'))->rows(4)
                    ->helperText(__('Shown under the review on the website.')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('accommodation', 'experience', 'booking'))
            ->columns([
                Tables\Columns\TextColumn::make('rating')->sortable()
                    ->formatStateUsing(fn ($state) => static::stars($state))->color('warning'),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold')
                    ->description(fn (Review $r) => $r->email),
                Tables\Columns\TextColumn::make('comment')->limit(70)->wrap()->color('gray')
                    ->description(fn (Review $r) => $r->title),
                Tables\Columns\TextColumn::make('subject')->label(__('Reviewed'))->badge()
                    ->state(fn (Review $r) => $r->subjectName() ?? __('Camp (general)'))
                    ->color(fn (Review $r) => $r->experience_id ? 'info' : ($r->accommodation_id ? 'warning' : 'gray')),
                Tables\Columns\IconColumn::make('is_verified')->label(__('Verified'))->boolean()
                    ->trueIcon('heroicon-s-check-badge')->falseIcon('heroicon-o-minus')->trueColor('success')
                    ->tooltip(fn (Review $r) => $r->booking?->reference),
                Tables\Columns\ToggleColumn::make('is_approved')->label(__('Approved')),
                Tables\Columns\ToggleColumn::make('is_featured')->label(__('Featured')),
                Tables\Columns\TextColumn::make('locale')->badge()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')->label(__('Approved')),
                Tables\Filters\TernaryFilter::make('is_verified')->label(__('Verified')),
                Tables\Filters\SelectFilter::make('accommodation_id')->label(__('Stay'))
                    ->relationship('accommodation', 'name')->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                Tables\Filters\SelectFilter::make('experience_id')->label(__('Experience'))
                    ->relationship('experience', 'name')->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                Tables\Filters\SelectFilter::make('rating')
                    ->options(collect(range(5, 1))->mapWithKeys(fn ($n) => [$n => static::stars($n)])),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label(__('Approve'))->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Review $r) => ! $r->is_approved)
                    ->action(fn (Review $r) => $r->update(['is_approved' => true])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve')->label(__('Approve'))->icon('heroicon-o-check')
                    ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
