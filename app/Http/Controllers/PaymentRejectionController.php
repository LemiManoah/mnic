<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RejectPayment;
use App\Http\Requests\RejectPaymentRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class PaymentRejectionController
{
    public function update(
        RejectPaymentRequest $request,
        Payment $payment,
        #[CurrentUser] User $user,
        RejectPayment $action,
    ): RedirectResponse {
        // PaymentPolicy::review already guarantees the reviewer has a member
        // record, so this cannot miss.
        $verifier = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle($payment, $verifier, $request->string('reason')->value(), $request->ip());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment rejected.'),
        ]);

        return to_route('payment.index');
    }
}
