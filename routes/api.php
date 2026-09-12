<?php

use App\Http\Controllers\Api\V1\Admin\AppealController as AdminAppealController;
use App\Http\Controllers\Api\V1\Admin\OrganizationController;
use App\Http\Controllers\Api\V1\Admin\SignatoryController;
use App\Http\Controllers\Api\V1\Admin\SubmissionController as AdminSubmissionController;
use App\Http\Controllers\Api\V1\Signatory\AppealController as SignatoryAppealController;
use App\Http\Controllers\Api\V1\Signatory\SubmissionController as SignatorySubmissionController;
use App\Http\Controllers\Api\V1\Student\AppealController as StudentAppealController;
use App\Http\Controllers\Api\V1\Student\DeadlineController;
use App\Http\Controllers\Api\V1\Student\NotificationController;
use App\Http\Controllers\Api\V1\Student\SubmissionController as StudentSubmissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.token:student', 'throttle:student'])->group(function (): void {
    Route::get('/ping', function () {
        return ['ok' => true];
    });

    Route::get('/hello', function () {
        return ['message' => 'Benji Buendia is a freshman barely surviving Apuma University, an elite institution infamous for its grueling, high-pressure workload. Against his judgment, he risked joining the student council, only to find that this supposed chaotic job turns suspiciously smooth. Every late-night paperwork is signed by the very second, every impossible deadline mysteriously moves through the dates, and every path to his success seems meticulously prepared by an unseen hand. The architect behind it all? His captivating, strictly off-limits council advisor. But this secret devotion is far from a selfless endeavor. As academic pressure turns into mounting tension, Benji learns that every favor his advisor grants comes at a steep price. It is a price that must be paid behind closed doors, until every "debt" is cleared on his name.'];
    });

    Route::get('/verity', function () {
        return ['message' => 'FUCK YOU SANTOS'];
    });
});

Route::prefix('v1')->name('v1.')->group(function (): void {
    Route::middleware(['api.token:student', 'cognito.jwt:student', 'throttle:student'])
        ->prefix('students')
        ->name('students.')
        ->group(function (): void {
            Route::get('submissions', [StudentSubmissionController::class, 'index'])->name('submissions.index');
            Route::post('submissions', [StudentSubmissionController::class, 'store'])
                ->middleware('throttle:student-write')
                ->name('submissions.store');
            Route::get('events/{event}/submissions/{submission}', [StudentSubmissionController::class, 'show'])
                ->name('submissions.show');
            Route::put('events/{event}/submissions/{submission}', [StudentSubmissionController::class, 'update'])
                ->middleware('throttle:student-write')
                ->name('submissions.update');
            Route::get('events/{event}/submissions/{submission}/notifications', [NotificationController::class, 'index'])
                ->name('submissions.notifications.index');
            Route::get('events/{event}/submissions/{submission}/appeals', [StudentAppealController::class, 'index'])
                ->name('submissions.appeals.index');
            Route::post('appeals', [StudentAppealController::class, 'store'])
                ->middleware('throttle:student-write')
                ->name('appeals.store');
            Route::get('deadlines', [DeadlineController::class, 'index'])->name('deadlines.index');
        });

    Route::middleware(['api.token:signatory', 'cognito.jwt:signatory', 'throttle:signatory'])
        ->prefix('signatories')
        ->name('signatories.')
        ->group(function (): void {
            Route::get('submissions', [SignatorySubmissionController::class, 'index'])->name('submissions.index');
            Route::get('events/{event}/submissions/{submission}', [SignatorySubmissionController::class, 'show'])
                ->name('submissions.show');
            Route::post('events/{event}/submissions/{submission}/approve', [SignatorySubmissionController::class, 'approve'])
                ->middleware('throttle:signatory-write')
                ->name('submissions.approve');
            Route::post('events/{event}/submissions/{submission}/deny', [SignatorySubmissionController::class, 'deny'])
                ->middleware('throttle:signatory-write')
                ->name('submissions.deny');
            Route::get('appeals', [SignatoryAppealController::class, 'index'])->name('appeals.index');
            Route::post('events/{event}/submissions/{submission}/appeals/{appeal}/resolve', [SignatoryAppealController::class, 'resolve'])
                ->middleware('throttle:signatory-write')
                ->name('appeals.resolve');
        });

    Route::middleware(['api.token:admin', 'cognito.jwt:admin', 'throttle:admin'])
        ->prefix('admins')
        ->name('admins.')
        ->group(function (): void {
            Route::get('submissions', [AdminSubmissionController::class, 'index'])->name('submissions.index');
            Route::get('events/{event}/submissions/{submission}', [AdminSubmissionController::class, 'show'])
                ->name('submissions.show');
            Route::get('appeals', [AdminAppealController::class, 'index'])->name('appeals.index');
            Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
            Route::post('organizations', [OrganizationController::class, 'store'])
                ->middleware('throttle:admin-write')
                ->name('organizations.store');
            Route::get('signatories', [SignatoryController::class, 'index'])->name('signatories.index');
            Route::post('signatories', [SignatoryController::class, 'store'])
                ->middleware('throttle:admin-write')
                ->name('signatories.store');
            Route::put('signatories/{signatory}', [SignatoryController::class, 'update'])
                ->middleware('throttle:admin-write')
                ->name('signatories.update');
        });
});
