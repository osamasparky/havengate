@extends('mail.layout')
@php $bu = $booking->units->first(); @endphp
@section('body')
    <h1 style="font-family:Georgia,serif;font-weight:normal;font-size:30px;margin:0 0 12px;">{{ __("mail.$kind.title") }}</h1>
    <p style="font-size:16px;line-height:1.6;color:#5C554C;margin:0 0 24px;">{{ __("mail.$kind.body") }}</p>
    @if ($kind === 'cancelled' && $booking->refund_amount > 0)
        <p style="font-size:15px;line-height:1.6;margin:0 0 24px;">{{ __('mail.cancelled.refund', ['amount' => money($booking->refund_amount)]) }}</p>
    @endif
    @if ($kind === 'received')
        <p style="font-size:15px;line-height:1.6;background:#F3EADC;border-radius:10px;padding:16px;margin:0 0 24px;">{{ setting('offline_payment_instructions') }}</p>
    @endif
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-top:1px solid #E8D9C2;">
        <tr><td style="padding:12px 0;color:#8F877C;">{{ __('booking.reference') }}</td><td style="padding:12px 0;text-align:end;font-family:monospace;font-size:16px;font-weight:bold;" dir="ltr">{{ $booking->reference }}</td></tr>
        <tr><td style="padding:12px 0;color:#8F877C;border-top:1px solid #E8D9C2;">{{ __('mail.stay') }}</td><td style="padding:12px 0;text-align:end;border-top:1px solid #E8D9C2;">{{ $bu?->accommodation?->name }}{{ $booking->roomCount() > 1 ? ' × '.trans_choice('booking.rooms_count', $booking->roomCount(), ['count' => $booking->roomCount()]) : '' }}</td></tr>
        <tr><td style="padding:12px 0;color:#8F877C;border-top:1px solid #E8D9C2;">{{ __('mail.dates') }}</td><td style="padding:12px 0;text-align:end;border-top:1px solid #E8D9C2;">{{ $booking->check_in->translatedFormat('D j M Y') }} → {{ $booking->check_out->translatedFormat('D j M Y') }}<br><span style="color:#8F877C;">{{ setting('check_in_time') }} / {{ setting('check_out_time') }}</span></td></tr>
        <tr><td style="padding:12px 0;color:#8F877C;border-top:1px solid #E8D9C2;">{{ __('mail.guests') }}</td><td style="padding:12px 0;text-align:end;border-top:1px solid #E8D9C2;">{{ trans_choice('site.guests', $booking->guestCount(), ['count' => $booking->guestCount()]) }}</td></tr>
        @foreach ($booking->extras as $x)
            <tr><td style="padding:12px 0;color:#8F877C;border-top:1px solid #E8D9C2;">{{ $x->name }}</td><td style="padding:12px 0;text-align:end;border-top:1px solid #E8D9C2;">× {{ $x->quantity }}</td></tr>
        @endforeach
        <tr><td style="padding:12px 0;border-top:1px solid #E8D9C2;font-weight:bold;">{{ __('booking.total') }}</td><td style="padding:12px 0;text-align:end;border-top:1px solid #E8D9C2;font-family:Georgia,serif;font-size:22px;">{{ money($booking->total) }}</td></tr>
        @if ((float) $booking->amount_paid > 0)
            <tr><td style="padding:6px 0;color:#4E7D5B;">{{ __('mail.paid') }}</td><td style="padding:6px 0;text-align:end;color:#4E7D5B;">{{ money($booking->amount_paid) }}</td></tr>
        @endif
        @if ($booking->balanceDue() > 0 && $kind === 'confirmed')
            <tr><td style="padding:6px 0;">{{ __('mail.balance') }}</td><td style="padding:6px 0;text-align:end;font-weight:bold;">{{ money($booking->balanceDue()) }}</td></tr>
        @endif
    </table>
    <p style="text-align:center;margin:32px 0 0;"><a href="{{ $booking->manageUrl() }}" style="display:inline-block;background:#B8875A;color:#0B1424;text-decoration:none;font-weight:bold;padding:14px 28px;border-radius:999px;">{{ __('mail.manage') }}</a></p>
@endsection
