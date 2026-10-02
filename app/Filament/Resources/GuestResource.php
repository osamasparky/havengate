<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestResource\Pages;
use App\Models\Guest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuestResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Guest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Reservations';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('first_name')->required(),
                Forms\Components\TextInput::make('last_name')->required(),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->tel(),
                Forms\Components\TextInput::make('country')->maxLength(2),
                Forms\Components\Select::make('preferred_locale')->options(collect(config('heavengate.locales'))->map(fn ($l) => __($l['name']))),
                Forms\Components\Toggle::make('is_vip')->label(__('VIP / returning guest')),
                Forms\Components\Toggle::make('marketing_opt_in')->label(__('Accepts marketing')),
                Forms\Components\Textarea::make('notes')->label(__('Staff notes'))->rows(3)->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make(__('Bookings'))->schema([
                Forms\Components\Placeholder::make('history')->hiddenLabel()->content(fn (?Guest $record) => $record
                    ? new \Illuminate\Support\HtmlString($record->bookings()->limit(20)->get()->map(fn ($b) => '<a style="text-decoration:underline" href="'.BookingResource::getUrl('view', ['record' => $b]).'">'.$b->reference.'</a> · '.$b->check_in->format('j M Y').' · '.$b->nights.'n · '.e($b->status->getLabel()).' · '.number_format($b->total))->implode('<br>') ?: '—')
                    : '—'),
            ])->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label(__('Name'))->searchable(['first_name', 'last_name'])->weight('semibold')
                    ->icon(fn (Guest $g) => $g->is_vip ? 'heroicon-s-star' : null)->iconColor('warning'),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('country'),
                Tables\Columns\TextColumn::make('bookings_count')->counts('bookings')->label(__('Stays'))->sortable(),
                Tables\Columns\TextColumn::make('created_at')->date('j M Y')->label(__('First seen'))->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_vip'),
                Tables\Filters\TernaryFilter::make('marketing_opt_in'),
                Tables\Filters\SelectFilter::make('country')->options(fn () => Guest::query()->whereNotNull('country')->distinct()->pluck('country', 'country')),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')->label(__('Export CSV'))->icon('heroicon-o-arrow-down-tray')
                    ->action(fn ($records) => response()->streamDownload(function () use ($records) {
                        $out = fopen('php://output', 'w');
                        fputcsv($out, ['First name', 'Last name', 'Email', 'Phone', 'Country', 'Language', 'Marketing']);
                        foreach ($records as $g) {
                            fputcsv($out, [$g->first_name, $g->last_name, $g->email, $g->phone, $g->country, $g->preferred_locale, $g->marketing_opt_in ? 'yes' : 'no']);
                        }
                        fclose($out);
                    }, 'guests.csv')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}
