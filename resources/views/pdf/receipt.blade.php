<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}">
<head><meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; color: #1D1A16; font-size: 12px; }
    .head { background: #0B1424; color: #E2C29C; padding: 24px; }
    .brand { font-family: 'DejaVu Serif', serif; font-size: 24px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 8px 0; border-bottom: 1px solid #E8D9C2; text-align: {{ locale_dir() === 'rtl' ? 'right' : 'left' }}; }
    .num { text-align: {{ locale_dir() === 'rtl' ? 'left' : 'right' }}; }
    .muted { color: #8F877C; }
    .total td { font-size: 16px; font-weight: bold; border-bottom: 0; }
</style></head>
<body>
@php $bu = $booking->units->first(); @endphp
<div class="head"><div class="brand">Heaven Gate Camp</div><div style="font-size:10px;letter-spacing:3px;">NUWEIBA · SOUTH SINAI</div></div>
<table style="margin-top:20px;">
    <tr><td class="muted">{{ __('booking.reference') }}</td><td class="num"><strong>{{ $booking->reference }}</strong></td></tr>
    <tr><td class="muted">{{ __('booking.first_name') }}</td><td class="num">{{ $booking->guest->full_name }}</td></tr>
    <tr><td class="muted">{{ __('mail.stay') }}</td><td class="num">{{ $bu?->accommodation?->name }}{{ $booking->roomCount() > 1 ? ' × '.trans_choice('booking.rooms_count', $booking->roomCount(), ['count' => $booking->roomCount()]) : '' }}</td></tr>
    <tr><td class="muted">{{ __('mail.dates') }}</td><td class="num">{{ $booking->check_in->format('Y-m-d') }} → {{ $booking->check_out->format('Y-m-d') }} ({{ $booking->nights }})</td></tr>
    <tr><td class="muted">{{ __('mail.guests') }}</td><td class="num">{{ party_label($booking->adults, $booking->children) }}</td></tr>
</table>
<table style="margin-top:20px;">
    @php $rooms = $booking->roomCount(); $nightsTotal = collect($bu?->nightly_rates ?? [])->sum('rate') * $rooms; @endphp
    @foreach ($bu?->nightly_rates ?? [] as $n)
        <tr><td>{{ $n['date'] }} {{ $n['label'] ? '· '.$n['label'] : '' }}{{ $rooms > 1 ? ' × '.$rooms : '' }}</td><td class="num">{{ number_format($n['rate'] * $rooms, 2) }}</td></tr>
    @endforeach
    @if ((float) $booking->accommodation_total - $nightsTotal > 0.009)
        <tr><td>{{ __('booking.extra_guests') }} / {{ __('booking.pet_fee') }}</td><td class="num">{{ number_format((float) $booking->accommodation_total - $nightsTotal, 2) }}</td></tr>
    @endif
    @foreach ($booking->extras as $x)
        <tr><td>{{ $x->name }} × {{ $x->quantity }}</td><td class="num">{{ number_format($x->total, 2) }}</td></tr>
    @endforeach
    @if ($booking->discount_total > 0)<tr><td>{{ __('booking.discount') }}</td><td class="num">−{{ number_format($booking->discount_total, 2) }}</td></tr>@endif
    @if ($booking->tax_total > 0)<tr><td>{{ __('booking.service_charge') }} / {{ __('booking.vat') }}</td><td class="num">{{ number_format($booking->tax_total, 2) }}</td></tr>@endif
    <tr class="total"><td>{{ __('booking.total') }}</td><td class="num">{{ $booking->currency }} {{ number_format($booking->total, 2) }}</td></tr>
    <tr><td class="muted">{{ __('booking.manage.paid') }}</td><td class="num">{{ number_format($booking->amount_paid, 2) }}</td></tr>
    <tr><td class="muted">{{ __('booking.manage.balance') }}</td><td class="num">{{ number_format($booking->balanceDue(), 2) }}</td></tr>
</table>
<p class="muted" style="margin-top:30px;">{{ setting('address') }} · {{ setting('contact_phone') }} · {{ setting('contact_email') }}</p>
</body></html>
