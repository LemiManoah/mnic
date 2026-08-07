@extends('pdf.layout')

@section('title', 'Monthly report ' . $period['label'])
@section('doc-type', 'Monthly transparency report · ' . $period['label'])

@section('content')
    @unless ($reconciliation && $reconciliation['is_confirmed'])
        {{-- The same warning the screen carries. A report that looks official
             must not let anyone mistake unreconciled figures for settled ones. --}}
        <p style="border: 1px solid #a11; color: #a11; padding: 6pt;">
            <strong>Unreconciled.</strong> These figures have not been checked
            against the external statement and are provisional.
        </p>
    @endunless

    <h2>Contributions</h2>

    <table class="data">
        <tr>
            <td>Expected</td>
            <td class="num">{{ number_format($contributions['expected']) }}</td>
        </tr>
        <tr>
            <td>Collected</td>
            <td class="num">{{ number_format($contributions['collected']) }}</td>
        </tr>
        <tr>
            <td>Outstanding</td>
            <td class="num @if ($contributions['outstanding'] > 0) negative @endif">
                {{ number_format($contributions['outstanding']) }}
            </td>
        </tr>
        <tr>
            <td>Members in arrears</td>
            <td class="num">{{ $contributions['members_in_arrears'] }}</td>
        </tr>
    </table>

    <h2>Cash movement</h2>

    <table class="data">
        <tr>
            <td>Verified inflows</td>
            <td class="num">{{ number_format($cash['inflows']) }}</td>
        </tr>
        <tr>
            <td>Settled outflows</td>
            <td class="num">{{ number_format($cash['outflows']) }}</td>
        </tr>
        <tr class="total-row">
            <td>Net</td>
            <td class="num">{{ number_format($cash['inflows'] - $cash['outflows']) }}</td>
        </tr>
    </table>

    @if ($reconciliation)
        <h2>Reconciliation</h2>

        <table class="data">
            <tr>
                <td>Status</td>
                <td class="num">{{ $reconciliation['status'] }}</td>
            </tr>
            <tr>
                <td>Opening balance</td>
                <td class="num">{{ number_format($reconciliation['opening_balance']) }}</td>
            </tr>
            <tr>
                <td>Expected closing</td>
                <td class="num">{{ number_format($reconciliation['expected_closing_balance']) }}</td>
            </tr>
            <tr>
                <td>Statement closing</td>
                <td class="num">{{ number_format($reconciliation['statement_closing_balance']) }}</td>
            </tr>
            <tr class="total-row">
                <td>Difference</td>
                <td class="num @if ($reconciliation['difference'] !== 0) negative @endif">
                    {{ number_format($reconciliation['difference']) }}
                </td>
            </tr>
        </table>
    @endif

    @if ($adjustments->isNotEmpty())
        <h2>Corrections after this month closed</h2>

        <p>The figures above are the ones the club signed off and have not been
            changed. These corrections were approved afterwards by two officers.</p>

        <table class="data">
            <thead>
                <tr>
                    <th>Reason</th>
                    <th>Raised by</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment['reason'] }}</td>
                        <td>{{ $adjustment['requested_by'] ?? 'An officer' }}</td>
                        <td class="num @if ($adjustment['amount'] < 0) negative @endif">
                            {{ number_format($adjustment['amount']) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Verified contributions</h2>

    <table class="data">
        <thead>
            <tr>
                <th>Member</th>
                <th>Reference</th>
                <th>Paid on</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payments as $payment)
                <tr>
                    <td>{{ $payment['member_name'] }}</td>
                    <td>{{ $payment['reference'] }}</td>
                    <td>{{ $payment['paid_on'] }}</td>
                    <td class="num">{{ number_format($payment['amount']) }}</td>
                </tr>
            @endforeach

            @if ($payments->isEmpty())
                <tr><td colspan="4">No verified contributions this month.</td></tr>
            @endif
        </tbody>
    </table>

    <h2>Expenses</h2>

    <table class="data">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Purpose</th>
                <th>Payee</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($expenses as $expense)
                <tr>
                    <td>{{ $expense['reference'] }}</td>
                    <td>{{ $expense['purpose'] }}</td>
                    <td>{{ $expense['payee'] }}</td>
                    <td class="num">{{ number_format($expense['amount']) }}</td>
                </tr>
            @endforeach

            @if ($expenses->isEmpty())
                <tr><td colspan="4">No expenses settled this month.</td></tr>
            @endif
        </tbody>
    </table>
@endsection

@section('note')
    Amounts are in Ugandan shillings. Contributions count only once a second
    officer has verified them, and expenses only once they have been approved,
    paid and verified.
@endsection
