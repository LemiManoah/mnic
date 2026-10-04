<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

final readonly class ApplyMemberAdvances
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Member $member, ?Member $actor = null, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($member, $actor, $ipAddress): void {
            Member::query()->lockForUpdate()->findOrFail($member->id);

            $payments = Payment::query()
                ->where('member_id', $member->id)
                ->where('status', PaymentStatus::Verified->value)
                ->where('unapplied_amount', '>', 0)
                ->oldest('paid_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($payments->isEmpty()) {
                return;
            }

            $obligations = MemberObligation::query()
                ->with('contributionPeriod')
                ->where('member_id', $member->id)
                ->whereIn('status', [ObligationStatus::Unpaid->value, ObligationStatus::PartiallyPaid->value])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->sortBy(fn (MemberObligation $obligation): string => sprintf('%04d-%02d', $obligation->contributionPeriod->year ?? 0, $obligation->contributionPeriod->month ?? 0));

            foreach ($payments as $payment) {
                $before = $payment->toArray();
                $remaining = $payment->unapplied_amount;

                foreach ($obligations as $obligation) {
                    $applied = min($remaining, $obligation->outstanding());

                    if ($applied <= 0) {
                        continue;
                    }

                    $allocation = PaymentAllocation::query()->firstOrNew([
                        'payment_id' => $payment->id,
                        'member_obligation_id' => $obligation->id,
                    ]);
                    $allocation->fill(['amount' => (int) $allocation->amount + $applied])->save();

                    $amountPaid = $obligation->amount_paid + $applied;
                    $obligation->update([
                        'amount_paid' => $amountPaid,
                        'status' => $amountPaid >= $obligation->amount ? ObligationStatus::Paid : ObligationStatus::PartiallyPaid,
                    ]);
                    $remaining -= $applied;
                }

                if ($remaining !== $payment->unapplied_amount) {
                    $payment->update(['unapplied_amount' => $remaining]);
                    $this->recordAuditEvent->handle('payment.advance_applied', $payment, $actor, $before, $payment->toArray(), $ipAddress);
                }
            }
        });
    }
}
