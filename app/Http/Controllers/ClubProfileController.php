<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateClubProfile;
use App\Http\Requests\UpdateClubProfileRequest;
use App\Models\ClubProfile;
use App\Models\Member;
use App\Models\Setting;
use App\Models\User;
use App\Services\PdfExport;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final readonly class ClubProfileController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Setting::class);

        return Inertia::render('club-profile/index', [
            'profile' => ClubProfile::query()->findOrFail(1)->content,
            'canUpdate' => $user->can('update', Setting::class),
        ]);
    }

    public function update(
        UpdateClubProfileRequest $request,
        #[CurrentUser] User $user,
        UpdateClubProfile $action,
    ): RedirectResponse {
        /** @var array<string, array<string, string>> $content */
        $content = $request->validated('profile');

        $action->handle(
            ClubProfile::query()->findOrFail(1),
            $content,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Club profile updated.'),
        ]);

        return to_route('club-profile.index');
    }

    public function download(PdfExport $pdf): HttpResponse
    {
        Gate::authorize('viewAny', Member::class);
        $profile = ClubProfile::query()->findOrFail(1)->content;

        return $pdf->download(
            'pdf.club-profile',
            'mnic-club-profile.pdf',
            [
                'profile' => $profile,
                'clubName' => $profile['cover']['club_name'],
            ],
        );
    }
}
