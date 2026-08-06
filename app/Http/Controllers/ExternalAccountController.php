<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExternalAccountType;
use App\Http\Requests\CreateExternalAccountRequest;
use App\Models\ExternalAccount;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ExternalAccountController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', ExternalAccount::class);

        return Inertia::render('external-account/index', [
            'accounts' => ExternalAccount::query()->orderBy('name')->get(),
            'canCreate' => $user->can('create', ExternalAccount::class),
            'typeOptions' => array_map(
                static fn (ExternalAccountType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ],
                ExternalAccountType::cases(),
            ),
        ]);
    }

    public function store(CreateExternalAccountRequest $request): RedirectResponse
    {
        ExternalAccount::query()->create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('External account added.'),
        ]);

        return to_route('external-account.index');
    }
}
