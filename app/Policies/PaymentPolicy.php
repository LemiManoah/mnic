<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;

final class PaymentPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    /**
     * Any active member may submit their own payment; the treasurer records on
     * behalf of others. Which member a payment may be recorded for is enforced
     * by CreatePaymentRequest.
     */
    public function create(): bool
    {
        return true;
    }

    /**
     * Maker-checker: a payment is reviewed by someone other than whoever
     * recorded it. The "not your own" half is a row-level rule, not a
     * permission — it is also enforced inside VerifyPayment / RejectPayment so
     * an administrator bypassing this gate still cannot self-verify.
     */
    public function review(User $user, Payment $payment): bool
    {
        if (! $user->can(Permission::PaymentsReview->value)) {
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
        if ($user->can(Permission::PaymentsViewEvidence->value)) {
            return true;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $payment->member_id === $member->id;
    }
}
