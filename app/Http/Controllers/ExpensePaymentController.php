<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordExpensePayment;
use App\Http\Requests\RecordExpensePaymentRequest;
use App\Models\Expense;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class ExpensePaymentController
{
    public function update(
        RecordExpensePaymentRequest $request,
        Expense $expense,
        #[CurrentUser] User $user,
        RecordExpensePayment $action,
    ): RedirectResponse {
        $action->handle(
            $expense,
            $request->string('external_account_id')->value(),
            $request->string('payment_reference')->value(),
            $request->string('paid_on')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Expense payment recorded.'),
        ]);

        return to_route('expense.index');
    }
}
