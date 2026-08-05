<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateSetting;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Member;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SettingController
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Setting::class);

        return Inertia::render('setting/index', [
            'settings' => Setting::query()->get()->map(function (Setting $setting): array {
                $versions = $setting->versions()
                    ->orderByDesc('effective_from')
                    ->orderByDesc('created_at')
                    ->get();

                $today = now()->toDateString();

                return [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'label' => $setting->label,
                    'type' => $setting->type,
                    'current' => $versions->first(fn (mixed $version): bool => $version->effective_from->toDateString() <= $today),
                    'versions' => $versions,
                ];
            }),
        ]);
    }

    public function update(UpdateSettingRequest $request, Setting $setting, #[CurrentUser] User $user, UpdateSetting $action): RedirectResponse
    {
        $action->handle(
            $setting,
            $request->string('value')->value(),
            $request->string('effective_from')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Setting updated.'),
        ]);

        return to_route('setting.index');
    }
}
