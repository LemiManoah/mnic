<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * A printable receipt for a verified payment.
 *
 * Rendered as a plain Blade page rather than an Inertia screen, and styled so
 * the browser's own "save as PDF" produces something a member can keep. That
 * avoids adding a PDF library for one document — if the club later wants
 * server-generated PDFs, this view is already the template.
 */
final readonly class PaymentReceiptController
{
    public function show(Payment $payment): View
    {
        Gate::authorize('viewEvidence', $payment);

        // Only a verified payment is proof of anything. Issuing a receipt for a
        // submitted one would let a member wave it about before an officer had
        // confirmed the money arrived.
        abort_unless($payment->status === PaymentStatus::Verified, 404);

        $payment->loadMissing(['member', 'recordedByMember', 'reviewedByMember']);

        $allocations = $payment->allocations()
            ->with('memberObligation.contributionPeriod')
            ->get()
            ->map(fn (PaymentAllocation $allocation): array => [
                'period' => $allocation->memberObligation?->contributionPeriod?->label() ?? '—',
                'amount' => $allocation->amount,
            ]);

        return view('payment-receipt', [
            'payment' => $payment,
            'allocations' => $allocations,
        ]);
    }
}
