<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AdjustMemberObligation;
use App\Enums\ObligationStatus;
use App\Http\Requests\AdjustMemberObligationRequest;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class MemberObligationAdjustmentController
{
    public function update(
        AdjustMemberObligationRequest $request,
        MemberObligation $memberObligation,
        #[CurrentUser] User $user,
        AdjustMemberObligation $action,
    ): RedirectResponse {
        $actor = Member::query()->where('user_id', $user->id)->firstOrFail();

        $action->handle(
            $memberObligation,
            ObligationStatus::from($request->string('status')->value()),
            $request->string('reason')->value(),
            $actor,
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Obligation adjusted.'),
        ]);

        return back();
    }
}
