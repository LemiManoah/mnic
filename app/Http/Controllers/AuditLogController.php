<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AuditLogController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $search = $request->string('search')->trim()->value();
        $event = $request->string('event')->value();

        return Inertia::render('audit-log/index', [
            'auditLogs' => AuditLog::query()
                ->with('actorMember')
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $inner): Builder => $inner
                        ->where('event', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('auditable_type', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('actorMember', fn (Builder $member): Builder => $member
                            ->where('full_name', 'like', sprintf('%%%s%%', $search)))))
                ->when($event !== '', fn (Builder $query): Builder => $query
                    ->where('event', $event))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => [
                'search' => $search === '' ? null : $search,
                'event' => $event === '' ? null : $event,
            ],
            // The catalogue of events is whatever has actually been recorded —
            // there is no fixed list, and a filter offering events that never
            // happened would be noise.
            'eventOptions' => AuditLog::query()
                ->select('event')
                ->distinct()
                ->orderBy('event')
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'value' => $log->event,
                    'label' => $log->event,
                ])
                ->all(),
        ]);
    }
}
