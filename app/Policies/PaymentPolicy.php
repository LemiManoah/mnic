<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;

final class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any active member may submit their own payment; the treasurer records on
     * behalf of others. Which member a payment may be recorded for is enforced
     * by CreatePaymentRequest.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Maker-checker: a payment is reviewed by someone other than whoever
     * recorded it. Also enforced inside VerifyPayment / RejectPayment.
     */
    public function review(User $user, Payment $payment): bool
    {
        if (! $user->hasAnyRole([ClubRole::FinancialVerifier->value, ClubRole::Administrator->value])) {
            return false;
        }

        if ($payment->status !== PaymentStatus::Submitted) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $payment->recorded_by_member_id !== $member->id;
    }

    public function viewEvidence(User $user, Payment $payment): bool
    {
        if ($user->hasAnyRole([
            ClubRole::FinancialVerifier->value,
            ClubRole::Administrator->value,
            ClubRole::Treasurer->value,
        ])) {
            return true;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $payment->member_id === $member->id;
    }
}
