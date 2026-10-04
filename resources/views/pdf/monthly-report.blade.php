@extends('pdf.layout')

@section('title', 'Monthly report ' . $period['label'])
@section('doc-type', 'Monthly transparency report · ' . $period['label'])

@section('report-styles')
    body { font-size: 9pt; line-height: 1.25; }
    img { width: 100px !important; margin-bottom: 4px !important; }
    .doc-type { margin-bottom: 8pt; }
    h2 { margin-top: 12pt; }
    table.data th, table.data td { padding: 3pt 4pt; }
    table.data th { font-size: 8pt; letter-spacing: 0; }
@endsection

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
            <td>{{ $period['is_overdue'] ? 'Members in arrears' : 'Members with a balance' }}</td>
            <td class="num">{{ $contributions['members_in_arrears'] }}</td>
        </tr>
    </table>

    <h2>{{ $period['is_overdue'] ? 'Members in arrears' : 'Outstanding contributions' }}</h2>
    <p>Balances for {{ $period['label'] }} reflect verified payments to date. Grace ends on {{ $period['grace_ends_on'] }}.</p>
    <table class="data">
        <thead>
            <tr><th>Member</th><th class="num">Expected</th><th class="num">Paid</th><th class="num">Outstanding</th></tr>
        </thead>
        <tbody>
            @forelse ($arrears as $member)
                <tr>
                    <td>{{ $member['member_name'] }}<br>{{ $member['member_number'] }}</td>
                    <td class="num">{{ number_format($member['amount']) }}</td>
                    <td class="num">{{ number_format($member['amount_paid']) }}</td>
                    <td class="num">{{ number_format($member['outstanding']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No outstanding contributions for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Cash movement</h2>

    <table class="data">
        <tr>
            <td>Total money received</td>
            <td class="num">{{ number_format($cash['inflows']) }}</td>
        </tr>
        <tr>
            <td>Contributions and advances received</td>
            <td class="num">{{ number_format($cash['inflows'] - $cash['withdrawal_fees']) }}</td>
        </tr>
        <tr>
            <td>Withdrawal fees received</td>
            <td class="num">{{ number_format($cash['withdrawal_fees']) }}</td>
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

        <p>These corrections were approved after the period closed. Contribution balances
            above remain live and can change when later payments are verified.</p>

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

    <h2>Verified payments received this month</h2>
    <p>Payments are shown by payment date and may settle earlier contribution periods.</p>

    <table class="data">
        <colgroup><col style="width: 30%;"><col style="width: 22%;"><col style="width: 18%;"><col style="width: 15%;"><col style="width: 15%;"></colgroup>
        <thead>
            <tr>
                <th>Member</th>
                <th>Reference</th>
                <th>Paid on</th>
                <th class="num">Withdrawal fee</th>
                <th class="num">Received</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payments as $payment)
                <tr>
                    <td>{{ $payment['member_name'] }}</td>
                    <td>{{ $payment['reference'] }}</td>
                    <td>{{ $payment['paid_on'] }}</td>
                    <td class="num">{{ number_format($payment['withdrawal_fee_amount']) }}</td>
                    <td class="num">{{ number_format($payment['amount']) }}</td>
                </tr>
            @endforeach

            @if ($payments->isEmpty())
                <tr><td colspan="5">No verified payments received this month.</td></tr>
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
    Amounts are in Ugandan shillings. Contribution totals use verified payments.
    Settled outflows include paid expenses awaiting verification.
@endsection
