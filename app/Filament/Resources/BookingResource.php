<?php

namespace App\Filament\Resources;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentProvider;
use App\Exceptions\BookingException;
use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Reservations';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'guest.first_name', 'guest.last_name', 'guest.email', 'guest.phone'];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Booking::where('status', BookingStatus::Pending)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count();

        return $n ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** Edit form: operational fields only. Dates/unit change via actions. */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Arrival'))->schema([
                Forms\Components\TextInput::make('arrival_time')->maxLength(20),
                Forms\Components\Toggle::make('with_pet'),
                Forms\Components\Textarea::make('special_requests')->rows(3)->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make(__('Internal'))->schema([
                Forms\Components\Textarea::make('internal_notes')->rows(4)->helperText(__('Visible to staff only.')),
            ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()->schema([
                Infolists\Components\TextEntry::make('reference')->weight('bold')->size('lg')->copyable(),
                Infolists\Components\TextEntry::make('status')->badge(),
                Infolists\Components\TextEntry::make('payment_status')->badge(),
                Infolists\Components\TextEntry::make('payment_method')->label(__('Payment method'))->badge()->placeholder(__('—')),
                Infolists\Components\TextEntry::make('source')->badge()->color('gray')->formatStateUsing(fn ($state) => static::sourceLabel($state)),
            ])->columns(4),
            Infolists\Components\Grid::make(3)->schema([
                Infolists\Components\Section::make(__('Stay'))->columnSpan(2)->schema([
                    Infolists\Components\TextEntry::make('units.0.accommodation.name')->label(__('Stay')),
                    Infolists\Components\TextEntry::make('unit_codes')->label(__('Units'))->state(fn (Booking $r) => $r->units->map(fn ($bu) => $bu->unit?->code.' · '.$bu->adults.'+'.$bu->children)->all())->badge(),
                    Infolists\Components\TextEntry::make('check_in')->date('D j M Y'),
                    Infolists\Components\TextEntry::make('check_out')->date('D j M Y'),
                    Infolists\Components\TextEntry::make('nights'),
                    Infolists\Components\TextEntry::make('guests')->state(fn (Booking $r) => party_label($r->adults, $r->children).($r->with_pet ? ' · '.__('With pet') : '')),
                    Infolists\Components\TextEntry::make('arrival_time')->placeholder(__('—')),
                    Infolists\Components\TextEntry::make('locale')->label(__('Language'))->formatStateUsing(fn ($state) => __(config("heavengate.locales.$state.name"))),
                    Infolists\Components\TextEntry::make('special_requests')->columnSpanFull()->placeholder(__('—')),
                    Infolists\Components\TextEntry::make('internal_notes')->columnSpanFull()->placeholder(__('—')),
                ])->columns(2),
                Infolists\Components\Section::make(__('Guest'))->columnSpan(1)->schema([
                    Infolists\Components\TextEntry::make('guest.full_name')->label(__('Name'))
                        ->url(fn (Booking $r) => GuestResource::getUrl('edit', ['record' => $r->guest_id])),
                    Infolists\Components\TextEntry::make('guest.email')->label(__('Email'))->copyable(),
                    Infolists\Components\TextEntry::make('guest.phone')->label(__('Phone'))->copyable()
                        ->url(fn (Booking $r) => $r->guest->phone ? 'https://wa.me/'.preg_replace('/\D/', '', $r->guest->phone) : null, true),
                    Infolists\Components\TextEntry::make('guest.country')->label(__('Country')),
                ]),
            ]),
            Infolists\Components\Section::make(__('Money'))->schema([
                Infolists\Components\TextEntry::make('accommodation_total')->money(config('heavengate.currency')),
                Infolists\Components\TextEntry::make('extras_total')->money(config('heavengate.currency')),
                Infolists\Components\TextEntry::make('discount_total')->money(config('heavengate.currency'))->helperText(fn (Booking $r) => $r->promo_code),
                Infolists\Components\TextEntry::make('tax_total')->money(config('heavengate.currency')),
                Infolists\Components\TextEntry::make('total')->money(config('heavengate.currency'))->weight('bold'),
                Infolists\Components\TextEntry::make('amount_paid')->money(config('heavengate.currency'))->color('success'),
                Infolists\Components\TextEntry::make('balance')->state(fn (Booking $r) => $r->balanceDue())->money(config('heavengate.currency'))->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                Infolists\Components\TextEntry::make('refund_amount')->money(config('heavengate.currency'))->visible(fn (Booking $r) => $r->refund_amount !== null),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('check_in', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['guest', 'units.unit', 'units.accommodation']))
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->weight('bold')->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('guest.full_name')->label(__('Guest'))
                    ->searchable(['first_name', 'last_name', 'email', 'phone'])
                    ->description(fn (Booking $r) => $r->guest?->phone ? "\u{2066}{$r->guest->phone}\u{2069}" : null), // LTR isolate keeps +20… intact in RTL
                Tables\Columns\TextColumn::make('units.0.accommodation.name')->label(__('Stay'))
                    ->description(fn (Booking $r) => ($r->units->count() > 1 ? $r->units->count().' × ' : '').$r->unitCodes()),
                Tables\Columns\TextColumn::make('check_in')->date('D j M')->sortable()
                    ->description(fn (Booking $r) => __('to :date · :n nights', ['date' => $r->check_out->translatedFormat('D j M'), 'n' => $r->nights])),
                Tables\Columns\TextColumn::make('adults')->label(__('Guests'))->formatStateUsing(fn (Booking $r) => $r->adults.($r->children ? '+'.$r->children : '')),
                Tables\Columns\TextColumn::make('total')->money(config('heavengate.currency'))->sortable(),
                Tables\Columns\TextColumn::make('payment_status')->badge(),
                Tables\Columns\TextColumn::make('payment_method')->label(__('Payment method'))->badge()->placeholder(__('—'))->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('source')->badge()->color('gray')->formatStateUsing(fn ($state) => static::sourceLabel($state))->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->since()->label(__('Booked'))->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(BookingStatus::class)->multiple(),
                Tables\Filters\SelectFilter::make('payment_status')->options(BookingPaymentStatus::class),
                Tables\Filters\SelectFilter::make('payment_method')->label(__('Payment method'))->options(\App\Enums\PaymentMethod::class),
                Tables\Filters\SelectFilter::make('accommodation')->label(__('Stay'))
                    ->options(fn () => Accommodation::all()->mapWithKeys(fn ($a) => [$a->id => $a->name]))
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereHas('units', fn ($u) => $u->where('accommodation_id', $data['value'])) : $query),
                Tables\Filters\Filter::make('stay_dates')->form([
                    Forms\Components\DatePicker::make('from')->label(__('Staying from')),
                    Forms\Components\DatePicker::make('until')->label(__('until')),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'], fn ($q, $d) => $q->whereDate('check_out', '>', $d))
                    ->when($data['until'], fn ($q, $d) => $q->whereDate('check_in', '<', $d))),
                Tables\Filters\SelectFilter::make('source')->options(['website' => __('Website'), 'admin' => __('Admin'), 'phone' => __('Phone'), 'walk_in' => __('Walk-in'), 'ota' => __('OTA')]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ActionGroup::make(static::workflowActions(table: true)),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')->label(__('Export CSV'))->icon('heroicon-o-arrow-down-tray')
                    ->action(fn ($records) => response()->streamDownload(function () use ($records) {
                        $out = fopen('php://output', 'w');
                        fputcsv($out, ['Reference', 'Status', 'Guest', 'Email', 'Phone', 'Stay', 'Unit', 'Check-in', 'Check-out', 'Nights', 'Adults', 'Children', 'Total', 'Paid', 'Source']);
                        foreach ($records->load('guest', 'units.unit', 'units.accommodation') as $b) {
                            fputcsv($out, [$b->reference, $b->status->value, $b->guest->full_name, $b->guest->email, $b->guest->phone, $b->units->first()?->accommodation?->getTranslation('name', 'en'), $b->unitCodes(), $b->check_in->toDateString(), $b->check_out->toDateString(), $b->nights, $b->adults, $b->children, $b->total, $b->amount_paid, $b->source]);
                        }
                        fclose($out);
                    }, 'bookings-'.now()->format('Ymd-His').'.csv')),
            ]);
    }

    /**
     * Status workflow shared by the table row menu and the view page header.
     *
     * @return array<int, \Filament\Tables\Actions\Action|\Filament\Actions\Action>
     */
    public static function workflowActions(bool $table = false): array
    {
        $A = $table ? Tables\Actions\Action::class : \Filament\Actions\Action::class;
        $svc = fn () => app(BookingService::class);
        $guard = function (callable $fn) {
            try {
                $fn();
                Notification::make()->title(__('Done'))->success()->send();
            } catch (BookingException $e) {
                Notification::make()->title($e->getMessage())->danger()->send();
            }
        };

        return [
            $A::make('recordPayment')->label(__('Record payment'))->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn (Booking $r) => $r->status->isActive() && $r->balanceDue() > 0)
                ->form(fn (Booking $r) => [
                    Forms\Components\TextInput::make('amount')->numeric()->required()->default($r->balanceDue())->prefix(config('heavengate.currency')),
                    Forms\Components\Select::make('provider')->options([
                        PaymentProvider::Cash->value => __('Cash'), PaymentProvider::InstaPay->value => __('InstaPay'),
                        PaymentProvider::BankTransfer->value => __('Bank transfer'), PaymentProvider::Manual->value => __('Other'),
                    ])->required()->default(PaymentProvider::Cash->value),
                    Forms\Components\TextInput::make('notes')->placeholder(__('Receipt no., sender name…')),
                ])
                ->action(fn (Booking $r, array $data) => $guard(fn () => $svc()->recordManualPayment($r, (float) $data['amount'], PaymentProvider::from($data['provider']), $data['notes'] ?? null))),

            $A::make('confirm')->label(__('Confirm without payment'))->icon('heroicon-o-check-badge')->color('primary')
                ->visible(fn (Booking $r) => $r->status === BookingStatus::Pending)
                ->requiresConfirmation()->modalDescription(__('The guest gets a confirmation email. Use for trusted guests paying on arrival.'))
                ->action(fn (Booking $r) => $guard(fn () => $svc()->confirm($r))),

            $A::make('checkIn')->label(__('Check in'))->icon('heroicon-o-arrow-right-end-on-rectangle')->color('info')
                ->visible(fn (Booking $r) => $r->status === BookingStatus::Confirmed && $r->check_in->lte(today()->addDay()))
                ->requiresConfirmation()->action(fn (Booking $r) => $guard(fn () => $svc()->checkIn($r))),

            $A::make('checkOut')->label(__('Check out'))->icon('heroicon-o-arrow-left-start-on-rectangle')->color('gray')
                ->visible(fn (Booking $r) => $r->status === BookingStatus::CheckedIn)
                ->requiresConfirmation()
                ->modalDescription(fn (Booking $r) => $r->balanceDue() > 0 ? __('Balance outstanding: :amount', ['amount' => number_format($r->balanceDue(), 2)]) : null)
                ->action(fn (Booking $r) => $guard(fn () => $svc()->checkOut($r))),

            $A::make('reassign')->label(__('Move to another unit'))->icon('heroicon-o-arrows-right-left')
                ->visible(fn (Booking $r) => $r->status->isActive())
                ->form(fn (Booking $r) => [
                    Forms\Components\Select::make('booking_unit_id')->label(__('Unit to move'))->required()
                        ->options($r->units->mapWithKeys(fn ($bu) => [$bu->id => $bu->unit?->code]))
                        ->default($r->units->first()?->id)
                        ->visible($r->units->count() > 1),
                    Forms\Components\Select::make('unit_id')->label(__('Free units for these dates'))->required()->options(function () use ($r) {
                        $bu = $r->units->first();
                        $avail = app(AvailabilityService::class);
                        $own = $r->units->pluck('unit_id')->all();

                        return Unit::with('accommodation')->where('is_active', true)->get()
                            ->filter(fn (Unit $u) => ! in_array($u->id, $own, true) && $avail->isUnitAvailable($u, $bu->check_in, $bu->check_out, $r->id))
                            ->mapWithKeys(fn (Unit $u) => [$u->id => $u->code.' — '.$u->accommodation->name]);
                    }),
                ])
                ->action(fn (Booking $r, array $data) => $guard(fn () => $svc()->reassignUnit($r, Unit::findOrFail($data['unit_id']), $data['booking_unit_id'] ?? null))),

            $A::make('noShow')->label(__('Mark no-show'))->icon('heroicon-o-user-minus')->color('danger')
                ->visible(fn (Booking $r) => $r->status === BookingStatus::Confirmed && $r->check_in->lte(today()))
                ->requiresConfirmation()->action(fn (Booking $r) => $guard(fn () => $svc()->markNoShow($r))),

            $A::make('cancel')->label(__('Cancel booking'))->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (Booking $r) => $r->status->isActive())
                ->form(fn (Booking $r) => [
                    Forms\Components\Placeholder::make('policy')->label(__('Policy refund'))
                        ->content(function () use ($r) {
                            $q = app(BookingService::class)->cancellationQuote($r);

                            return __(':percent% of paid (:days days before arrival) = :refund', ['percent' => $q['percent'], 'days' => $q['days_before'], 'refund' => number_format($q['refund'], 2)]);
                        }),
                    Forms\Components\TextInput::make('refund')->numeric()->label(__('Refund amount'))->default(fn () => app(BookingService::class)->cancellationQuote($r)['refund'])
                        ->helperText(__('Process the refund in the EasyKash dashboard / at the desk, then record it on the payment.')),
                    Forms\Components\Textarea::make('reason')->rows(2),
                ])
                ->action(fn (Booking $r, array $data) => $guard(fn () => $svc()->cancel($r, $data['reason'] ?? null, false, (float) ($data['refund'] ?? 0)))),
        ];
    }

    public static function sourceLabel(?string $source): string
    {
        return __(['website' => 'Website', 'admin' => 'Admin', 'phone' => 'Phone', 'walk_in' => 'Walk-in', 'ota' => 'OTA'][$source] ?? (string) $source);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
