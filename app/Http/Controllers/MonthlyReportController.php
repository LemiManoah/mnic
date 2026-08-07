<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContributionPeriod;
use App\Services\MonthlyReportData;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The monthly transparency report every active member can read.
 *
 * Each report states its period and whether the month's reconciliation has
 * been confirmed, so nobody mistakes unreconciled figures for settled ones.
 *
 * The figures themselves come from MonthlyReportData, shared with the PDF so
 * the screen and the printed copy can never disagree.
 */
final readonly class MonthlyReportController
{
    public function index(): Response
    {
        return Inertia::render('monthly-report/index', [
            'periods' => ContributionPeriod::query()
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->map(fn (ContributionPeriod $period): array => [
                    'id' => $period->id,
                    'label' => $period->label(),
                    'status' => $period->status,
                ]),
        ]);
    }

    public function show(ContributionPeriod $contributionPeriod, MonthlyReportData $report): Response
    {
        return Inertia::render('monthly-report/show', $report->for($contributionPeriod));
    }
}
