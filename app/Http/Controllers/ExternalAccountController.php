<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExternalAccountType;
use App\Http\Requests\CreateExternalAccountRequest;
use App\Models\ExternalAccount;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ExternalAccountController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', ExternalAccount::class);

        $search = $request->string('search')->trim()->value();
        $type = $request->string('type')->value();

        return Inertia::render('external-account/index', [
            'accounts' => ExternalAccount::query()
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $inner): Builder => $inner
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('institution', 'like', sprintf('%%%s%%', $search))))
                ->when($type !== '', fn (Builder $query): Builder => $query
                    ->where('type', $type))
                ->orderBy('name')
                ->get(),
            'filters' => [
                'search' => $search === '' ? null : $search,
                'type' => $type === '' ? null : $type,
            ],
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
