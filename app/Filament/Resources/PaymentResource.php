<?php

namespace App\Filament\Resources;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Ledger of every gateway / manual payment. Read-only — payments are recorded from bookings. */
class PaymentResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Reservations';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('booking.guest'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime('j M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('booking.reference')->label(__('Booking'))->fontFamily('mono')->searchable()
                    ->url(fn (Payment $p) => BookingResource::getUrl('view', ['record' => $p->booking_id])),
                Tables\Columns\TextColumn::make('booking.guest.full_name')->label(__('Guest')),
                Tables\Columns\TextColumn::make('provider')->badge(),
                Tables\Columns\TextColumn::make('method')->formatStateUsing(fn ($state) => __(ucfirst(str_replace('_', ' ', (string) $state))))->placeholder(__('—')),
                Tables\Columns\TextColumn::make('amount')->money(config('heavengate.currency'))->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money(config('heavengate.currency'))->label(__('Total'))),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('provider_reference')->label(__('Gateway ref'))->searchable()->copyable()->toggleable(),
                Tables\Columns\TextColumn::make('reference')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(PaymentStatus::class)->default(PaymentStatus::Paid->value),
                Tables\Filters\SelectFilter::make('provider')->options(PaymentProvider::class),
                Tables\Filters\Filter::make('this_month')->query(fn ($query) => $query->where('created_at', '>=', now()->startOfMonth())),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPayments::route('/')];
    }
}
