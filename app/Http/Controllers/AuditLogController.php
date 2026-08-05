<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AuditLogController
{
    public function index(): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        return Inertia::render('audit-log/index', [
            'auditLogs' => AuditLog::query()
                ->with('actorMember')
                ->latest()
                ->paginate(20),
        ]);
    }
}
