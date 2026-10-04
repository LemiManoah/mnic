@extends('pdf.layout')

@section('title', 'Statement ' . $member->member_number)
@section('doc-type', 'Member statement · ' . $member->member_number)

@section('content')
    <table class="meta">
        <tr>
            <td class="label">Member</td>
            <td>{{ $member->full_name }}</td>
        </tr>
        <tr>
            <td class="label">Joined</td>
            <td>{{ $member->joined_at->toFormattedDateString() }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>{{ $member->status->label() }}</td>
        </tr>
    </table>

    <h2>Contributions</h2>

    <table class="data">
        <thead>
            <tr>
                <th>Period</th>
                <th class="num">Expected</th>
                <th class="num">Paid</th>
                <th class="num">Outstanding</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($obligations as $obligation)
                <tr>
                    <td>{{ $obligation['period'] }}</td>
                    <td class="num">{{ number_format($obligation['amount']) }}</td>
                    <td class="num">{{ number_format($obligation['amount_paid']) }}</td>
                    <td class="num @if ($obligation['outstanding'] > 0) negative @endif">
                        {{ number_format($obligation['outstanding']) }}
                    </td>
                    <td>{{ $obligation['status'] }}</td>
                </tr>
            @endforeach

            @if ($obligations->isEmpty())
                <tr>
                    <td colspan="5">No obligations recorded.</td>
                </tr>
            @endif

            <tr class="total-row">
                <td>Total</td>
                <td class="num">{{ number_format($totals['expected']) }}</td>
                <td class="num">{{ number_format($totals['paid']) }}</td>
                <td class="num @if ($totals['outstanding'] > 0) negative @endif">
                    {{ number_format($totals['outstanding']) }}
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @if ($advance > 0)
        <p>Unapplied advance held against future months:
            <strong>UGX {{ number_format($advance) }}</strong></p>
    @endif
    <h2>Verified receipts</h2>
    <table class="data">
        <thead><tr><th>Reference</th><th>Paid on</th><th class="num">Withdrawal fee</th><th class="num">Received</th></tr></thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->reference }}</td>
                    <td>{{ $payment->paid_on->toDateString() }}</td>
                    <td class="num">{{ number_format($payment->withdrawal_fee_amount) }}</td>
                    <td class="num">{{ number_format($payment->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No verified receipts.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('note')
    Amounts are in Ugandan shillings. Only payments a second officer has
    verified are counted as paid — anything you have submitted but which is not
    yet verified does not appear here.
@endsection
