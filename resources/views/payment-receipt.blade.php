<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->reference }}</title>
    <style>
        :root { color-scheme: light; }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #111;
            background: #f5f5f5;
            margin: 0;
            padding: 2rem 1rem;
        }

        .sheet {
            max-width: 40rem;
            margin: 0 auto;
            background: #fff;
            padding: 2.5rem;
            border: 1px solid #e5e5e5;
        }

        h1 { font-size: 1.25rem; margin: 0 0 .25rem; }
        .muted { color: #666; font-size: .875rem; }
        .amount { font-size: 2rem; font-weight: 600; margin: 1.5rem 0; }

        dl { display: grid; grid-template-columns: 10rem 1fr; gap: .5rem 1rem; margin: 0; font-size: .9375rem; }
        dt { color: #666; }
        dd { margin: 0; }

        hr { border: 0; border-top: 1px solid #e5e5e5; margin: 1.5rem 0; }

        .note { font-size: .8125rem; color: #666; line-height: 1.5; }

        .actions { max-width: 40rem; margin: 1rem auto 0; text-align: right; }
        button {
            font: inherit;
            padding: .5rem 1rem;
            border: 1px solid #d4d4d4;
            background: #fff;
            border-radius: .375rem;
            cursor: pointer;
        }

        /* Printing is how this becomes a PDF, so the page furniture goes away
           and the sheet fills the paper. */
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { border: 0; max-width: none; padding: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <h1>{{ config('app.tenant.name', config('app.name')) }}</h1>
        <p class="muted">Payment receipt · {{ $payment->reference }}</p>

        <div class="amount">UGX {{ number_format($payment->amount) }}</div>

        <dl>
            <dt>Received from</dt>
            <dd>{{ $payment->member?->full_name ?? 'Unknown member' }}</dd>

            <dt>Member number</dt>
            <dd>{{ $payment->member?->member_number ?? '—' }}</dd>

            <dt>Paid on</dt>
            <dd>{{ $payment->paid_on->toFormattedDateString() }}</dd>

            <dt>Method</dt>
            <dd>{{ $payment->method->label() }}</dd>

            <dt>Recorded by</dt>
            <dd>{{ $payment->recordedByMember?->full_name ?? '—' }}</dd>

            <dt>Verified by</dt>
            <dd>{{ $payment->reviewedByMember?->full_name ?? '—' }}</dd>

            <dt>Verified on</dt>
            <dd>{{ $payment->reviewed_at?->toFormattedDateString() ?? '—' }}</dd>
        </dl>

        @if ($allocations->isNotEmpty())
            <hr>
            <p class="muted">Applied to</p>
            <dl>
                @foreach ($allocations as $allocation)
                    <dt>{{ $allocation['period'] }}</dt>
                    <dd>UGX {{ number_format($allocation['amount']) }}</dd>
                @endforeach

                @if ($payment->unapplied_amount > 0)
                    <dt>Held as advance</dt>
                    <dd>UGX {{ number_format($payment->unapplied_amount) }}</dd>
                @endif
            </dl>
        @endif

        <hr>

        <p class="note">
            This receipt confirms that the club has recorded and verified the
            payment above. Two different officers were involved: one recorded it
            and another confirmed it against the account. Issued
            {{ now()->toFormattedDateString() }}.
        </p>
    </div>

    <div class="actions">
        <button type="button" onclick="window.print()">Print or save as PDF</button>
    </div>
</body>
</html>
