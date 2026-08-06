<?php

declare(strict_types=1);

use App\Http\Controllers\ActionItemController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ContributionPeriodController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseApprovalController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpensePaymentController;
use App\Http\Controllers\ExpenseVerificationController;
use App\Http\Controllers\ExternalAccountController;
use App\Http\Controllers\MeetingAttendanceController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingMinutesController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberRoleController;
use App\Http\Controllers\MemberStatusController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentEvidenceController;
use App\Http\Controllers\PaymentRejectionController;
use App\Http\Controllers\PaymentVerificationController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalVotingController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ReconciliationItemController;
use App\Http\Controllers\ReconciliationRejectionController;
use App\Http\Controllers\ReconciliationReviewController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function (): void {
    // Members...
    Route::get('members', [MemberController::class, 'index'])->name('member.index');
    Route::post('members', [MemberController::class, 'store'])->name('member.store');
    Route::get('members/{member}', [MemberController::class, 'show'])->name('member.show');
    Route::get('members/{member}/edit', [MemberController::class, 'edit'])->name('member.edit');
    Route::put('members/{member}', [MemberController::class, 'update'])->name('member.update');
    Route::put('members/{member}/status', [MemberStatusController::class, 'update'])->name('member-status.update');
    Route::put('members/{member}/role', [MemberRoleController::class, 'update'])->name('member-role.update');

    // Contribution Periods...
    Route::get('contribution-periods', [ContributionPeriodController::class, 'index'])->name('contribution-period.index');
    Route::post('contribution-periods', [ContributionPeriodController::class, 'store'])->name('contribution-period.store');
    Route::get('contribution-periods/{contributionPeriod}', [ContributionPeriodController::class, 'show'])->name('contribution-period.show');

    // Payments...
    Route::get('payments', [PaymentController::class, 'index'])->name('payment.index');
    Route::post('payments', [PaymentController::class, 'store'])->name('payment.store');
    Route::put('payments/{payment}/verification', [PaymentVerificationController::class, 'update'])->name('payment-verification.update');
    Route::put('payments/{payment}/rejection', [PaymentRejectionController::class, 'update'])->name('payment-rejection.update');
    Route::get('payments/{payment}/evidence/{evidence}', [PaymentEvidenceController::class, 'show'])
        ->scopeBindings()
        ->name('payment-evidence.show');

    // Meetings...
    Route::get('meetings', [MeetingController::class, 'index'])->name('meeting.index');
    Route::post('meetings', [MeetingController::class, 'store'])->name('meeting.store');
    Route::get('meetings/{meeting}', [MeetingController::class, 'show'])->name('meeting.show');
    Route::put('meetings/{meeting}/attendance', [MeetingAttendanceController::class, 'update'])->name('meeting-attendance.update');
    Route::post('meetings/{meeting}/minutes', [MeetingMinutesController::class, 'store'])->name('meeting-minutes.store');
    Route::put('meetings/{meeting}/minutes', [MeetingMinutesController::class, 'update'])->name('meeting-minutes.update');

    // Proposals and voting...
    Route::get('proposals', [ProposalController::class, 'index'])->name('proposal.index');
    Route::post('proposals', [ProposalController::class, 'store'])->name('proposal.store');
    Route::get('proposals/{proposal}', [ProposalController::class, 'show'])->name('proposal.show');
    Route::post('proposals/{proposal}/voting', [ProposalVotingController::class, 'store'])->name('proposal-voting.store');
    Route::put('proposals/{proposal}/voting', [ProposalVotingController::class, 'update'])->name('proposal-voting.update');
    Route::post('proposals/{proposal}/votes', [VoteController::class, 'store'])->name('vote.store');

    // Action items...
    Route::get('action-items', [ActionItemController::class, 'index'])->name('action-item.index');
    Route::post('action-items', [ActionItemController::class, 'store'])->name('action-item.store');
    Route::put('action-items/{actionItem}', [ActionItemController::class, 'update'])->name('action-item.update');

    // Expenses...
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expense.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expense.store');
    Route::post('expenses/{expense}/approval', [ExpenseApprovalController::class, 'store'])->name('expense-approval.store');
    Route::put('expenses/{expense}/approval', [ExpenseApprovalController::class, 'update'])->name('expense-approval.update');
    Route::put('expenses/{expense}/payment', [ExpensePaymentController::class, 'update'])->name('expense-payment.update');
    Route::put('expenses/{expense}/verification', [ExpenseVerificationController::class, 'update'])->name('expense-verification.update');

    // Reconciliation and monthly close...
    Route::get('reconciliations', [ReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::post('reconciliations', [ReconciliationController::class, 'store'])->name('reconciliation.store');
    Route::post('reconciliations/{reconciliation}/review', [ReconciliationReviewController::class, 'store'])
        ->defaults('ability', 'update')
        ->name('reconciliation-review.store');
    Route::put('reconciliations/{reconciliation}/review', [ReconciliationReviewController::class, 'update'])
        ->defaults('ability', 'confirm')
        ->name('reconciliation-review.update');
    Route::delete('reconciliations/{reconciliation}/review', [ReconciliationReviewController::class, 'destroy'])
        ->defaults('ability', 'lock')
        ->name('reconciliation-review.destroy');
    Route::put('reconciliations/{reconciliation}/rejection', [ReconciliationRejectionController::class, 'update'])
        ->name('reconciliation-rejection.update');
    Route::post('reconciliations/{reconciliation}/items', [ReconciliationItemController::class, 'store'])
        ->name('reconciliation-item.store');
    Route::put('reconciliations/{reconciliation}/items/{item}', [ReconciliationItemController::class, 'update'])
        ->scopeBindings()
        ->name('reconciliation-item.update');

    // Monthly transparency report...
    Route::get('reports/monthly', [MonthlyReportController::class, 'index'])->name('monthly-report.index');
    Route::get('reports/monthly/{contributionPeriod}', [MonthlyReportController::class, 'show'])->name('monthly-report.show');

    // External accounts...
    Route::get('external-accounts', [ExternalAccountController::class, 'index'])->name('external-account.index');
    Route::post('external-accounts', [ExternalAccountController::class, 'store'])->name('external-account.store');

    // Settings (club configuration)...
    Route::get('club-settings', [SettingController::class, 'index'])->name('setting.index');
    Route::put('club-settings/{setting}', [SettingController::class, 'update'])->name('setting.update');

    // Audit Log...
    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // User Profile...
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

Route::middleware('guest')->group(function (): void {
    // User...
    // Self-registration is disabled: members are onboarded by the Secretary and
    // seeded via Database\Seeders\MusuwaNationSeeder. Re-enable to restore it.
    // Route::get('register', [UserController::class, 'create'])
    //     ->name('register');
    // Route::post('register', [UserController::class, 'store'])
    //     ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
