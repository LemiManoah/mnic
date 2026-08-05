<?php

declare(strict_types=1);

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ContributionPeriodController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberRoleController;
use App\Http\Controllers\MemberStatusController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentEvidenceController;
use App\Http\Controllers\PaymentRejectionController;
use App\Http\Controllers\PaymentVerificationController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');
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
