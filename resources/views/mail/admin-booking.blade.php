@extends('mail.layout')
@php $bu = $booking->units->first(); @endphp
@section('body')
    <h1 style="font-family:Georgia,serif;font-weight:normal;font-size:26px;margin:0 0 16px;">{{ ['offline' => 'Awaiting bank transfer', 'approval' => 'Pay at property — please confirm', 'cancelled' => 'Booking cancelled', 'attention' => 'Action needed', 'new' => 'New confirmed booking'][$context] ?? 'Booking update' }} · {{ $booking->reference }}</h1>
    <table role="presentation" width="100%" style="font-size:14px;line-height:1.7;">
        <tr><td style="color:#8F877C;width:140px;">Guest</td><td>{{ $booking->guest->full_name }} · {{ $booking->guest->email }} · {{ $booking->guest->phone }} ({{ $booking->guest->country }})</td></tr>
        <tr><td style="color:#8F877C;">Stay</td><td>{{ $bu?->accommodation?->getTranslation('name', 'en') }} — {{ $booking->roomCount() > 1 ? $booking->roomCount().' units' : 'unit' }} {{ $booking->unitCodes() }}</td></tr>
        <tr><td style="color:#8F877C;">Dates</td><td>{{ $booking->check_in->format('D j M Y') }} → {{ $booking->check_out->format('D j M Y') }} ({{ $booking->nights }} nights)</td></tr>
        <tr><td style="color:#8F877C;">Guests</td><td>{{ $booking->adults }} adults, {{ $booking->children }} children{{ $booking->with_pet ? ', with pet' : '' }}</td></tr>
        <tr><td style="color:#8F877C;">Total / paid</td><td>{{ number_format($booking->total, 2) }} / {{ number_format($booking->amount_paid, 2) }} {{ $booking->currency }}</td></tr>
        @if ($booking->payment_method)<tr><td style="color:#8F877C;">Payment</td><td>{{ ['online' => 'Online (EasyKash)', 'bank_transfer' => 'Bank transfer / InstaPay', 'at_property' => 'Pay at property — collect on arrival'][$booking->payment_method->value] }}</td></tr>@endif
        @if ($booking->arrival_time)<tr><td style="color:#8F877C;">Arrival</td><td>{{ $booking->arrival_time }}</td></tr>@endif
        @if ($booking->special_requests)<tr><td style="color:#8F877C;">Requests</td><td>{{ $booking->special_requests }}</td></tr>@endif
        @if ($booking->cancellation_reason)<tr><td style="color:#8F877C;">Reason</td><td>{{ $booking->cancellation_reason }} (refund {{ number_format((float) $booking->refund_amount, 2) }})</td></tr>@endif
    </table>
    <p style="margin-top:24px;"><a href="{{ url('/admin/bookings/'.$booking->id) }}" style="color:#9A6C42;font-weight:bold;">Open in admin →</a></p>
@endsection
