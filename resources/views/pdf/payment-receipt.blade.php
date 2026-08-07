@extends('pdf.layout')

@section('title', 'Receipt ' . $payment->reference)
@section('doc-type', 'Payment receipt · ' . $payment->reference)

@section('content')
    <p class="headline">UGX {{ number_format($payment->amount) }}</p>

    <table class="meta">
        <tr>
            <td class="label">Received from</td>
            <td>{{ $payment->member?->full_name ?? 'Unknown member' }}</td>
        </tr>
        <tr>
            <td class="label">Member number</td>
            <td>{{ $payment->member?->member_number ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Paid on</td>
            <td>{{ $payment->paid_on->toFormattedDateString() }}</td>
        </tr>
        <tr>
            <td class="label">Method</td>
            <td>{{ $payment->method->label() }}</td>
        </tr>
        <tr>
            <td class="label">Reference</td>
            <td>{{ $payment->reference }}</td>
        </tr>
        <tr>
            <td class="label">Recorded by</td>
            <td>{{ $payment->recordedByMember?->full_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Verified by</td>
            <td>{{ $payment->reviewedByMember?->full_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Verified on</td>
            <td>{{ $payment->reviewed_at?->toFormattedDateString() ?? '—' }}</td>
        </tr>
    </table>

    @if ($allocations->isNotEmpty())
        <h2>Applied to</h2>

        <table class="data">
            <thead>
                <tr>
                    <th>Period</th>
                    <th class="num">Amount (UGX)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($allocations as $allocation)
                    <tr>
                        <td>{{ $allocation['period'] }}</td>
                        <td class="num">{{ number_format($allocation['amount']) }}</td>
                    </tr>
                @endforeach

                @if ($payment->unapplied_amount > 0)
                    <tr>
                        <td>Held as advance against future months</td>
                        <td class="num">{{ number_format($payment->unapplied_amount) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif
@endsection

@section('note')
    This receipt confirms that the club has recorded and verified the payment
    above. Two different officers were involved: one recorded it and another
    confirmed it against the account.
@endsection
