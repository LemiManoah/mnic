<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\PdfExport;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * A PDF receipt for a verified payment.
 *
 * Rendered server-side so the member gets the same document however they opened
 * it — a browser's "print to PDF" varies by browser, by margin settings and by
 * whether the user remembers to turn headers off.
 */
final readonly class PaymentReceiptController
{
    public function show(Payment $payment, PdfExport $pdf): Response
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

        return $pdf->stream(
            'pdf.payment-receipt',
            sprintf('receipt-%s.pdf', $payment->reference),
            [
                'payment' => $payment,
                'allocations' => $allocations,
            ],
        );
    }
}
