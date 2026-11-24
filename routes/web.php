<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\CitizenIdController;
use App\Http\Controllers\CitizenIdTemplateController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\BirthdayController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\OfficialController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BudgetDashboardController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\BudgetAllocationController;
use App\Http\Controllers\BudgetTransactionController;
use App\Http\Controllers\BudgetTransactionAttachmentController;
use App\Http\Controllers\BudgetLogController;
use App\Http\Controllers\BudgetSettingController;
use App\Http\Controllers\BudgetSupplierController;
use App\Http\Controllers\AccountableOfficerController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\LiquidationReportController;
use App\Http\Controllers\IncomeEstimateController;
use App\Http\Controllers\BudgetProgramController;
use App\Http\Controllers\BudgetEntryController;
use App\Http\Controllers\BudgetLineItemController;
use App\Http\Controllers\BlotterController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryRentalController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\CitizenHoldController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProfileController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

require __DIR__ . '/auth.php';

// Root: send logged-in users to their home, guests to the login page.
Route::get('/', function () {
    return auth()->check()
        ? redirect(\App\Providers\RouteServiceProvider::HOME)
        : redirect()->route('login');
})->name('root');

// Serve public storage files through PHP. The hosting server (LiteSpeed)
// refuses to follow the public/storage symlink, so /storage/* 404s at the
// web-server level. This route streams the file straight from disk instead.
// Public (no auth) — assets like logos/photos must load for guests too.
Route::get('/storage/{path}', function (string $path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');

    abort_unless($disk->exists($path), 404);

    return response()->file($disk->path($path));
})->where('path', '.*')->name('storage.serve');




// ── Public citizen self-registration (no login; link from Settings → Registration Links) ──
Route::get('register/citizen/{token}', [\App\Http\Controllers\PublicRegistrationController::class, 'show'])->name('register.citizen');
Route::post('register/citizen/{token}', [\App\Http\Controllers\PublicRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')->name('register.citizen.store');
// ── Self-service kiosk: any device logged in with an account that has kiosk.use ──
Route::prefix('kiosk')->name('kiosk.')->middleware(['auth', 'can:kiosk.use', 'throttle:120,1'])->group(function () {
    Route::get('/',             [\App\Http\Controllers\KioskController::class, 'home'])->name('home');
    Route::get('register',      [\App\Http\Controllers\KioskController::class, 'register'])->name('register');
    Route::post('scan',         [\App\Http\Controllers\KioskController::class, 'scan'])->name('scan');
    Route::post('preview',      [\App\Http\Controllers\KioskController::class, 'preview'])->name('preview');
    Route::post('submit',       [\App\Http\Controllers\KioskController::class, 'submit'])->name('submit');
    Route::post('update',       [\App\Http\Controllers\KioskController::class, 'updateDetails'])->name('update');
    Route::post('join',         [\App\Http\Controllers\KioskController::class, 'join'])->name('join');
    Route::get('events',        [\App\Http\Controllers\KioskController::class, 'events'])->name('events');
    // A fresh CSRF token for the Log out button: the kiosk page stays open longer than its token lasts.
    Route::get('token',         fn () => response()->json(['token' => csrf_token()])->header('Cache-Control', 'no-store'))->name('token');
});
Route::get('register/citizen/{token}/done', [\App\Http\Controllers\PublicRegistrationController::class, 'done'])->name('register.citizen.done');

// Route::group(['prefix' => '/', 'middleware'=>'auth'], function () {
//     Route::get('', [RoutingController::class, 'index'])->name('root');
//     Route::get('/home', fn()=>view('index'))->name('home');
//     Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
//     Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
//     Route::get('{any}', [RoutingController::class, 'root'])->name('any');
// });


// Route::middleware(['auth', 'role:physician'])->group(function () {
//     Route::resource('emr', EMRController::class);
// });

Route::middleware('auth')->group(function () {
    Route::get('dashboard/activity', [\App\Http\Controllers\ActivityDashboardController::class, 'index'])->name('dashboard.activity');

    // Top-bar bell: the signed-in user's own notifications.
    Route::get('notifications/unread', [\App\Http\Controllers\NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{id}/open', [\App\Http\Controllers\NotificationController::class, 'open'])->name('notifications.open');

    Route::post('citizens/export', [CitizenController::class, 'export'])->middleware('can:citizens.export')->name('citizens.export');

    // These lookups all return resident PII, so they require citizens.view —
    // being merely signed in is not enough.
    Route::get('citizens/demographics', [CitizenController::class, 'demographics'])
        ->middleware('can:citizens.demographics')->name('citizens.demographics');

    // Detail changes asked for at the kiosk (declared before "citizens/{citizen}").
    Route::middleware('can:citizens.edit')->prefix('citizens/updates')->name('citizens.updates.')->group(function () {
        Route::get('/',                 [\App\Http\Controllers\CitizenUpdateRequestController::class, 'index'])->name('index');
        Route::post('{update}/approve', [\App\Http\Controllers\CitizenUpdateRequestController::class, 'approve'])->name('approve');
        Route::post('{update}/reject',  [\App\Http\Controllers\CitizenUpdateRequestController::class, 'reject'])->name('reject');
    });

    // Online registrations waiting for review (declared before "citizens/{citizen}").
    Route::middleware('can:citizens.approve')->prefix('citizens/registrations')->name('citizens.registrations.')->group(function () {
        Route::get('/',                      [\App\Http\Controllers\CitizenRegistrationController::class, 'index'])->name('index');
        Route::get('{registration}',         [\App\Http\Controllers\CitizenRegistrationController::class, 'show'])->name('show');
        Route::get('{registration}/proof/{group}/{side}', [\App\Http\Controllers\CitizenRegistrationController::class, 'proof'])
            ->whereIn('group', ['pwd', 'solo', 'senior'])->name('proof');
        Route::post('{registration}/update',  [\App\Http\Controllers\CitizenRegistrationController::class, 'update'])->name('update');
        Route::post('{registration}/approve', [\App\Http\Controllers\CitizenRegistrationController::class, 'approve'])->name('approve');
        Route::post('{registration}/reject',  [\App\Http\Controllers\CitizenRegistrationController::class, 'reject'])->name('reject');
    });

    Route::middleware('can:citizens.view')->group(function () {
        Route::get('citizens/parent-search', [CitizenController::class, 'parentSearch'])->name('citizens.parent-search');
        Route::get('citizens/search', [CitizenController::class, 'search'])->name('citizens.search');
        Route::get('citizens/recent', [CitizenController::class, 'recent'])->name('citizens.recent');
        Route::get('citizens/activity', [CitizenController::class, 'activity'])->name('citizens.activity');
        Route::get('citizens/{citizen}/quick-history', [CitizenController::class, 'quickHistory'])->name('citizens.quick-history');
        Route::get('citizens/{citizen}/detail', [CitizenController::class, 'detail'])->name('citizens.detail');
    });

    // Registering a minor creates a citizen record.
    Route::middleware('can:citizens.create')->group(function () {
        Route::get('citizens/register-minor', [CitizenController::class, 'createMinor'])->name('citizens.create-minor');
        Route::post('citizens/register-minor', [CitizenController::class, 'storeMinor'])->name('citizens.store-minor');
    });

    // Holds block a citizen from being issued documents.
    Route::middleware('can:citizens.hold')->group(function () {
        Route::post('citizens/{citizen}/hold', [CitizenHoldController::class, 'store'])->name('citizens.hold.store');
        Route::delete('citizens/{citizen}/hold', [CitizenHoldController::class, 'destroy'])->name('citizens.hold.destroy');
    });

    // Assigning tags to a citizen.
    Route::post('citizens/{citizen}/tags', [TagController::class, 'assignToCitizen'])
        ->middleware('can:citizens.tag')
        ->name('citizens.tags.sync');

    // Tags management
    Route::get('tags', [TagController::class, 'index'])->middleware('can:tags.view')->name('tags.index');
    Route::get('tags/all', [TagController::class, 'all'])->middleware('can:tags.view')->name('tags.all');
    Route::post('tags', [TagController::class, 'store'])->middleware('can:tags.create')->name('tags.store');
    Route::put('tags/{tag}', [TagController::class, 'update'])->middleware('can:tags.edit')->name('tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->middleware('can:tags.delete')->name('tags.destroy');

    // Barangay ID cards (citizen_ids.*) — official identity cards.
    Route::get('citizens/ids', [CitizenIdController::class, 'index'])
        ->middleware('can:citizen_ids.view')->name('citizens.ids.index');
    Route::get('citizens/ids/{citizenId}/print', [CitizenIdController::class, 'print'])
        ->middleware('can:citizen_ids.print')->name('citizens.ids.print');
    Route::post('citizens/ids', [CitizenIdController::class, 'store'])
        ->middleware('can:citizen_ids.generate')
        ->name('citizens.ids.store');
    // Photo taken or uploaded while creating a Barangay ID (saved as the citizen's profile photo).
    Route::post('citizens/ids/photo/{citizen}', [CitizenIdController::class, 'uploadPhoto'])
        ->middleware('can:citizen_ids.generate')
        ->name('citizens.ids.photo');
    Route::middleware('can:citizen_ids.signature')->group(function () {
        Route::post('citizens/ids/{citizenId}/upload-signature', [CitizenIdController::class, 'uploadSignature'])->name('citizens.ids.upload-signature');
        Route::post('citizens/ids/{citizenId}/remove-signature', [CitizenIdController::class, 'removeSignature'])->name('citizens.ids.remove-signature');
    });

    // ID Template Designer — edits the official ID layout for every citizen.
    Route::middleware('can:citizen_ids.template')->group(function () {
        Route::get('citizens/ids/template/designer', [CitizenIdTemplateController::class, 'designer'])->name('citizens.ids.template.designer');
        Route::post('citizens/ids/template/save', [CitizenIdTemplateController::class, 'save'])->name('citizens.ids.template.save');
        Route::post('citizens/ids/template/upload-bg', [CitizenIdTemplateController::class, 'uploadBg'])->name('citizens.ids.template.upload-bg');
        Route::post('citizens/ids/template/remove-bg', [CitizenIdTemplateController::class, 'removeBg'])->name('citizens.ids.template.remove-bg');
        Route::get('citizens/ids/template/preview-data/{citizen}', [CitizenIdTemplateController::class, 'previewData'])->name('citizens.ids.template.preview-data');
    });

    // Household module — must be BEFORE citizens resource to avoid {citizen} catching 'household'
    Route::prefix('citizens/household')->name('households.')->middleware('can:households.view')->group(function () {
        Route::get('/',                         [HouseholdController::class, 'index'])->name('index');
        Route::get('retrieve',                  [HouseholdController::class, 'retrieve'])->name('retrieve');
        Route::get('details',                   [HouseholdController::class, 'getDetails'])->name('details');
        Route::get('get',                       [HouseholdController::class, 'getHousehold'])->name('get');
        Route::get('filter-citizens',           [HouseholdController::class, 'filterCitizens'])->name('filter-citizens');
        Route::get('citizen-address',           [HouseholdController::class, 'getCitizenAddress'])->name('citizen-address');
        Route::get('kpi',                       [HouseholdController::class, 'kpi'])->name('kpi');
        Route::get('search',                    [HouseholdController::class, 'search'])->name('search');
        Route::post('export',                   [HouseholdController::class, 'export'])->name('export');

        // Households are archived rather than deleted, so this sits behind
        // households.delete — the closest-matching grant.
        Route::middleware('can:households.delete')->group(function () {
            Route::post('archive',              [HouseholdController::class, 'archive'])->name('archive');
            Route::post('restore',              [HouseholdController::class, 'restore'])->name('restore');
        });

        // Creating a household or attaching a family/member to one.
        Route::middleware('can:households.create')->group(function () {
            Route::post('store',                [HouseholdController::class, 'store'])->name('store');
            Route::post('store-family',         [HouseholdController::class, 'storeFamily'])->name('store-family');
            Route::post('store-member',         [HouseholdController::class, 'storeFamilyMember'])->name('store-member');
        });

        // Editing an existing household. Removing a head/member re-shapes the
        // household rather than deleting the record, so it is an edit.
        Route::middleware('can:households.edit')->group(function () {
            Route::post('update',               [HouseholdController::class, 'update'])->name('update');
            Route::post('remove-head',          [HouseholdController::class, 'removeFamilyHead'])->name('remove-head');
            Route::post('remove-member',        [HouseholdController::class, 'removeFamilyMember'])->name('remove-member');
            Route::post('set-household-head',   [HouseholdController::class, 'setHouseholdHead'])->name('set-household-head');
        });

        // Optional family income (Income Classification) — its own permission.
        Route::post('family-income',            [HouseholdController::class, 'updateFamilyIncome'])
            ->middleware('can:households.income')->name('family-income');
    });

    Route::middleware('can:citizens.view')->group(function () {
        // Declared before the resource, otherwise "citizens/inactive" is
        // swallowed by the "citizens/{citizen}" show route.
        Route::get('citizens/inactive', [CitizenController::class, 'inactive'])->name('citizens.inactive');
        Route::patch('citizens/{citizen}/restore', [CitizenController::class, 'restore'])
            ->middleware('can:citizens.restore')
            ->name('citizens.restore');

        // Permanent deletion is intentionally not exposed: barangay records
        // are archived (set inactive) rather than removed. The historical
        // eb_deleted_citizens data is kept for audit.

        // "destroy" archives rather than deletes, so it is gated behind
        // citizens.archive instead of the resource group's citizens.view.
        Route::delete('citizens/{citizen}', [CitizenController::class, 'destroy'])
            ->middleware('can:citizens.archive')
            ->name('citizens.destroy');

        // Split by verb so that citizens.view alone cannot write. The read-only
        // half stays on citizens.view; create/edit each need their own grant.
        // create/store must be registered BEFORE show — otherwise the
        // "citizens/{citizen}" wildcard matches "citizens/create" and 404s.
        Route::resource('citizens', CitizenController::class)
            ->only(['create', 'store'])
            ->middleware('can:citizens.create');
        Route::resource('citizens', CitizenController::class)
            ->only(['index', 'show']);
        Route::resource('citizens', CitizenController::class)
            ->only(['edit', 'update'])
            ->middleware('can:citizens.edit');
    });

    // Blotter module
    Route::middleware('can:blotter.view')->group(function () {
        // Logging an action against a blotter is part of working the case, so it
        // follows blotter.edit rather than blotter.create (which makes new cases).
        Route::middleware('can:blotter.edit')->group(function () {
            Route::patch('blotters/{blotter}/status', [BlotterController::class, 'updateStatus'])->name('blotters.status');
            Route::post('blotters/{blotter}/actions', [BlotterController::class, 'storeAction'])->name('blotters.actions.store');
            Route::patch('blotters/{blotter}/actions/{action}/outcome', [BlotterController::class, 'updateActionOutcome'])->name('blotters.actions.outcome');
        });
        Route::delete('blotters/{blotter}/actions/{action}', [BlotterController::class, 'destroyAction'])
            ->middleware('can:blotter.delete')
            ->name('blotters.actions.destroy');

        // create/store must be registered BEFORE show — otherwise the
        // "blotters/{blotter}" wildcard matches "blotters/create" and 404s.
        Route::resource('blotters', BlotterController::class)->only(['create', 'store'])->middleware('can:blotter.create');
        // Paper-ready report (standalone page, opens the print dialog).
        Route::get('blotters/{blotter}/print', [BlotterController::class, 'print'])->name('blotters.print');
        Route::resource('blotters', BlotterController::class)->only(['index', 'show']);
        Route::resource('blotters', BlotterController::class)->only(['edit', 'update'])->middleware('can:blotter.edit');
        Route::resource('blotters', BlotterController::class)->only(['destroy'])->middleware('can:blotter.delete');
    });

    // Pets of citizens (Citizen → Pets)
    Route::middleware('can:pets.view')->group(function () {
        // "create" before "{pet}" so the wildcard doesn't swallow it.
        Route::resource('pets', \App\Http\Controllers\PetController::class)->only(['create', 'store'])->middleware('can:pets.create');
        Route::resource('pets', \App\Http\Controllers\PetController::class)->only(['index', 'show']);
        Route::resource('pets', \App\Http\Controllers\PetController::class)->only(['edit', 'update'])->middleware('can:pets.edit');
        Route::resource('pets', \App\Http\Controllers\PetController::class)->only(['destroy'])->middleware('can:pets.delete');
    });

    // Health check-ups (libreng check-up, walk-in consultations)
    Route::middleware('can:health.view')->group(function () {
        Route::get('health/patients', [\App\Http\Controllers\HealthCheckupController::class, 'patients'])->middleware('can:health.create')->name('health.patients');
        // "create" before "{checkup}" so the wildcard doesn't swallow it.
        Route::resource('health', \App\Http\Controllers\HealthCheckupController::class)->parameters(['health' => 'checkup'])->only(['create', 'store'])->middleware('can:health.create');
        Route::resource('health', \App\Http\Controllers\HealthCheckupController::class)->parameters(['health' => 'checkup'])->only(['index', 'show']);
        Route::get('health/{checkup}/print', [\App\Http\Controllers\HealthCheckupController::class, 'print'])->name('health.print');
        Route::get('health/{checkup}/prescription', [\App\Http\Controllers\HealthCheckupController::class, 'prescription'])->name('health.prescription');
        Route::resource('health', \App\Http\Controllers\HealthCheckupController::class)->parameters(['health' => 'checkup'])->only(['edit', 'update'])->middleware('can:health.edit');
        Route::resource('health', \App\Http\Controllers\HealthCheckupController::class)->parameters(['health' => 'checkup'])->only(['destroy'])->middleware('can:health.delete');
    });

    // Ordinances & Resolutions (Sangguniang Barangay legislation)
    Route::middleware('can:legislation.view')->group(function () {
        // "create" before "{legislation}" so the wildcard doesn't swallow it.
        Route::resource('legislation', \App\Http\Controllers\LegislationController::class)->only(['create', 'store'])->middleware('can:legislation.create');
        Route::resource('legislation', \App\Http\Controllers\LegislationController::class)->only(['index', 'show']);
        Route::resource('legislation', \App\Http\Controllers\LegislationController::class)->only(['edit', 'update'])->middleware('can:legislation.edit');
        Route::resource('legislation', \App\Http\Controllers\LegislationController::class)->only(['destroy'])->middleware('can:legislation.delete');
        Route::get('legislation/{legislation}/file', [\App\Http\Controllers\LegislationController::class, 'file'])->name('legislation.file');
        Route::middleware('can:legislation.approve')->group(function () {
            Route::post('legislation/{legislation}/advance', [\App\Http\Controllers\LegislationController::class, 'advance'])->name('legislation.advance');
            Route::post('legislation/{legislation}/events', [\App\Http\Controllers\LegislationController::class, 'addEvent'])->name('legislation.events.store');
            Route::delete('legislation/{legislation}/events/{event}', [\App\Http\Controllers\LegislationController::class, 'destroyEvent'])->name('legislation.events.destroy');
        });
    });

    // Events & Attendance module
    Route::prefix('events')->name('events.')->middleware('can:events.view')->group(function () {
        // ── Create (events.create) ──────────────────────────────────────────
        // Declared BEFORE "{event}" — otherwise the wildcard matches
        // "events/create" and 404s looking for an event with id "create".
        Route::middleware('can:events.create')->group(function () {
            Route::get('create',                            [EventController::class, 'create'])->name('create');
            Route::post('/',                                [EventController::class, 'store'])->name('store');
        });

        // ── Read-only (events.view) ─────────────────────────────────────────
        Route::get('/',                                     [EventController::class, 'index'])->name('index');
        Route::get('kpi',                                   [EventController::class, 'kpi'])->name('kpi');
        Route::get('retrieve',                              [EventController::class, 'retrieve'])->name('retrieve');
        Route::get('{event}',                               [EventController::class, 'show'])->name('show');
        Route::get('{event}/attendance',                    [EventController::class, 'attendanceList'])->name('attendance.list');
        Route::get('{event}/citizens/search',               [EventController::class, 'searchCitizens'])->name('citizens.search');
        Route::get('{event}/raffle/pool',                   [EventController::class, 'rafflePool'])->name('raffle.pool');
        Route::get('{event}/raffle/winners',                [EventController::class, 'winnerHistory'])->name('raffle.winners');
        // Winners after the draw: list + export (prize edits/claims are under events.edit)
        Route::get('{event}/winners',                       [\App\Http\Controllers\EventWinnerController::class, 'index'])->name('winners.index');
        Route::get('{event}/winners/export',                [\App\Http\Controllers\EventWinnerController::class, 'export'])->name('winners.export');

        // ── Edit (events.edit) ──────────────────────────────────────────────
        // Attendance check-in, bulk add and raffle draws all mutate an existing
        // event's records, so they sit under events.edit.
        Route::middleware('can:events.edit')->group(function () {
            Route::get('{event}/edit',                      [EventController::class, 'edit'])->name('edit');
            Route::put('{event}',                           [EventController::class, 'update'])->name('update');
            Route::patch('{event}/toggle-status',           [EventController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('{event}/checkin/qr',               [EventController::class, 'checkinQr'])->name('checkin.qr');
            Route::post('{event}/checkin/manual',           [EventController::class, 'checkinManual'])->name('checkin.manual');
            Route::post('{event}/attendance/remove',        [EventController::class, 'removeAttendance'])->name('attendance.remove');
            Route::post('{event}/bulk/preview',             [EventController::class, 'bulkPreview'])->name('bulk.preview');
            Route::post('{event}/bulk/add',                 [EventController::class, 'bulkAdd'])->name('bulk.add');
            // Raffle: one logged-in screen runs the draw (no PIN, no public/live pages)
            Route::get('{event}/raffle',                    [EventController::class, 'rafflePage'])->name('raffle.page');
            Route::post('{event}/raffle/winner',            [EventController::class, 'recordWinner'])->name('raffle.winner');
            Route::post('{event}/raffle/winners/save',      [EventController::class, 'recordWinners'])->name('raffle.winners.store');
            Route::post('{event}/raffle/no-show',           [EventController::class, 'noShow'])->name('raffle.no-show');
            Route::post('{event}/raffle/undo',              [EventController::class, 'undoWinner'])->name('raffle.undo');
            Route::patch('{event}/winners/bulk',            [\App\Http\Controllers\EventWinnerController::class, 'bulk'])->name('winners.bulk');
            Route::patch('{event}/winners/{winner}/prize',  [\App\Http\Controllers\EventWinnerController::class, 'updatePrize'])->name('winners.prize');
        });

        // ── Delete (events.delete) ──────────────────────────────────────────
        Route::delete('{event}', [EventController::class, 'destroy'])
            ->middleware('can:events.delete')
            ->name('destroy');
    });

    // Birthdays read from the citizen register, so they follow citizen perms.
    Route::get('birthdays', [BirthdayController::class, 'index'])->middleware('can:birthdays.view')->name('birthdays.index');
    Route::post('birthdays/export', [BirthdayController::class, 'export'])->middleware('can:birthdays.export')->name('birthdays.export');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('can:activity_logs.view')->name('activity-logs.index');

    Route::get('settings', [SettingController::class, 'index'])->middleware('can:settings.view')->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->middleware('can:settings.edit')->name('settings.update');
    Route::get('settings/updates', [SettingController::class, 'updates'])->middleware('can:settings.view')->name('settings.updates');
    Route::get('settings/login-history', [\App\Http\Controllers\LoginHistoryController::class, 'index'])->middleware('can:login_logs.view')->name('settings.login-history');
    // Registration links for online citizen self-registration — Super Admin only (checked in the controller).
    Route::get('settings/registration-links', [\App\Http\Controllers\RegistrationTokenController::class, 'index'])->name('settings.registration-links');
    Route::post('settings/registration-links', [\App\Http\Controllers\RegistrationTokenController::class, 'store'])->name('settings.registration-links.store');
    Route::patch('settings/registration-links/{token}/toggle', [\App\Http\Controllers\RegistrationTokenController::class, 'toggle'])->name('settings.registration-links.toggle');
    Route::delete('settings/registration-links/{token}', [\App\Http\Controllers\RegistrationTokenController::class, 'destroy'])->name('settings.registration-links.destroy');
    // Kiosk accounts + start screen pictures — Super Admin only (checked in the controller).
    Route::get('settings/kiosks',                      [\App\Http\Controllers\KioskSettingsController::class, 'index'])->name('settings.kiosks');
    Route::post('settings/kiosk-slides',               [\App\Http\Controllers\KioskSettingsController::class, 'slideStore'])->name('settings.kiosk-slides.store');
    Route::patch('settings/kiosk-slides/{slide}',      [\App\Http\Controllers\KioskSettingsController::class, 'slideUpdate'])->name('settings.kiosk-slides.update');
    Route::delete('settings/kiosk-slides/{slide}',     [\App\Http\Controllers\KioskSettingsController::class, 'slideDestroy'])->name('settings.kiosk-slides.destroy');
    Route::get('settings/print-paper', [SettingController::class, 'printPaper'])->middleware('can:settings.view')->name('settings.print-paper');
    Route::post('settings/print-paper', [SettingController::class, 'updatePrintPaper'])->middleware('can:settings.edit')->name('settings.print-paper.update');

    // Officials live under Settings and have no permission of their own, so
    // they reuse settings.view / settings.edit like addresses do.
    Route::get('settings/officials', [OfficialController::class, 'index'])->middleware('can:settings.view')->name('officials.index');
    Route::post('settings/officials', [OfficialController::class, 'store'])->middleware('can:settings.edit')->name('officials.store');
    Route::put('settings/officials/{official}', [OfficialController::class, 'update'])->middleware('can:settings.edit')->name('officials.update');
    Route::delete('settings/officials/{official}', [OfficialController::class, 'destroy'])->middleware('can:settings.edit')->name('officials.destroy');

    Route::get('settings/addresses', [AddressController::class, 'index'])->middleware('can:addresses.view')->name('addresses.index');
    Route::post('settings/addresses', [AddressController::class, 'store'])->middleware('can:addresses.create')->name('addresses.store');
    Route::put('settings/addresses/{address}', [AddressController::class, 'update'])->middleware('can:addresses.edit')->name('addresses.update');
    Route::patch('settings/addresses/{address}/toggle', [AddressController::class, 'toggleActive'])->middleware('can:addresses.edit')->name('addresses.toggle');

    // Budget module — Pro plan only (SUBSCRIPTION_PLAN in .env; see config/plan.php)
    Route::middleware('feature:budget')->group(function () {
    Route::get('budget', [BudgetDashboardController::class, 'index'])->middleware('can:budget.view')->name('budget.index');

    Route::prefix('budget')->name('budget.')->middleware('can:budget.view')->group(function () {

        // ── Read-only (budget.view) ─────────────────────────────────────────
        Route::get('income-estimates', [IncomeEstimateController::class, 'index'])->name('income-estimates.index');
        Route::get('programs', [BudgetProgramController::class, 'index'])->name('programs.index');
        Route::get('line-items', [BudgetLineItemController::class, 'index'])->name('line-items.index');
        Route::get('entry', [BudgetEntryController::class, 'index'])->name('entry.index');
        Route::get('line-items/program-items', [BudgetLineItemController::class, 'programItems'])->name('line-items.program-items');
        Route::get('allocations', [BudgetAllocationController::class, 'index'])->name('allocations.index');
        Route::get('suppliers', [BudgetSupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/search', [BudgetSupplierController::class, 'search'])->name('suppliers.search');
        Route::get('transactions', [BudgetTransactionController::class, 'index'])->name('transactions.index');
        Route::get('attachments/{attachment}/download', [BudgetTransactionAttachmentController::class, 'download'])->name('attachments.download');
        Route::get('officers', [AccountableOfficerController::class, 'index'])->name('officers.index');
        Route::get('cash-advances', [CashAdvanceController::class, 'index'])->name('cash-advances.index');
        Route::get('liquidations/{liquidation}', [LiquidationReportController::class, 'show'])->name('liquidations.show');
        Route::get('settings', [BudgetSettingController::class, 'index'])->name('settings.index');
        Route::get('logs', [BudgetLogController::class, 'index'])->name('logs.index');

        // ── Create (budget.create) — record new records ─────────────────────
        Route::middleware('can:budget.create')->group(function () {
            Route::post('fiscal-years', [FiscalYearController::class, 'store'])->name('fiscal-years.store');
            Route::post('income-estimates', [IncomeEstimateController::class, 'store'])->name('income-estimates.store');
            Route::post('programs', [BudgetProgramController::class, 'store'])->name('programs.store');
            Route::post('line-items', [BudgetLineItemController::class, 'store'])->name('line-items.store');
            Route::post('line-items/seed-defaults', [BudgetLineItemController::class, 'seedDefaults'])->name('line-items.seed-defaults');
            Route::post('suppliers', [BudgetSupplierController::class, 'store'])->name('suppliers.store');
            Route::get('transactions/create', [BudgetTransactionController::class, 'create'])->name('transactions.create');
            Route::post('transactions', [BudgetTransactionController::class, 'store'])->name('transactions.store');
            Route::post('transactions/{transaction}/attachments', [BudgetTransactionAttachmentController::class, 'store'])->name('transactions.attachments.store');
            Route::post('officers', [AccountableOfficerController::class, 'store'])->name('officers.store');
            Route::get('cash-advances/create', [CashAdvanceController::class, 'create'])->name('cash-advances.create');
            Route::post('cash-advances', [CashAdvanceController::class, 'store'])->name('cash-advances.store');
            Route::get('cash-advances/{cashAdvance}/liquidate', [LiquidationReportController::class, 'create'])->name('liquidations.create');
            Route::post('cash-advances/{cashAdvance}/liquidate', [LiquidationReportController::class, 'store'])->name('liquidations.store');
        });

        // Wildcard reads are declared AFTER the create group — otherwise
        // "{param}" matches ".../create" and 404s.
        Route::get('transactions/{transaction}', [BudgetTransactionController::class, 'show'])->name('transactions.show');
        Route::get('transactions/{transaction}/print', [BudgetTransactionController::class, 'print'])->name('transactions.print');
        Route::get('cash-advances/{cashAdvance}', [CashAdvanceController::class, 'show'])->name('cash-advances.show');

        // ── Edit / approve (budget.edit) — modify existing + status changes ─
        Route::middleware('can:budget.edit')->group(function () {
            Route::patch('fiscal-years/{fiscalYear}/activate', [FiscalYearController::class, 'setActive'])->name('fiscal-years.activate');
            Route::put('income-estimates/{incomeEstimate}', [IncomeEstimateController::class, 'update'])->name('income-estimates.update');
            Route::put('programs/{program}', [BudgetProgramController::class, 'update'])->name('programs.update');
            Route::put('line-items/{lineItem}', [BudgetLineItemController::class, 'update'])->name('line-items.update');
            Route::post('line-items/save-amounts', [BudgetLineItemController::class, 'saveAmounts'])->name('line-items.save-amounts');
            Route::put('suppliers/{supplier}', [BudgetSupplierController::class, 'update'])->name('suppliers.update');
            Route::patch('transactions/{transaction}/status', [BudgetTransactionController::class, 'updateStatus'])->name('transactions.status');
            Route::put('officers/{officer}', [AccountableOfficerController::class, 'update'])->name('officers.update');
            Route::get('cash-advances/{cashAdvance}/edit', [CashAdvanceController::class, 'edit'])->name('cash-advances.edit');
            Route::put('cash-advances/{cashAdvance}', [CashAdvanceController::class, 'update'])->name('cash-advances.update');
            Route::get('liquidations/{liquidation}/edit', [LiquidationReportController::class, 'edit'])->name('liquidations.edit');
            Route::put('liquidations/{liquidation}', [LiquidationReportController::class, 'update'])->name('liquidations.update');
            Route::patch('liquidations/{liquidation}/close', [LiquidationReportController::class, 'close'])->name('liquidations.close');
            Route::post('settings', [BudgetSettingController::class, 'update'])->name('settings.update');
        });

        // ── Delete (budget.delete) ──────────────────────────────────────────
        Route::middleware('can:budget.delete')->group(function () {
            Route::delete('fiscal-years/{fiscalYear}', [FiscalYearController::class, 'destroy'])->name('fiscal-years.destroy');
            Route::delete('income-estimates/{incomeEstimate}', [IncomeEstimateController::class, 'destroy'])->name('income-estimates.destroy');
            Route::delete('programs/{program}', [BudgetProgramController::class, 'destroy'])->name('programs.destroy');
            Route::delete('line-items/{lineItem}', [BudgetLineItemController::class, 'destroy'])->name('line-items.destroy');
            Route::delete('suppliers/{supplier}', [BudgetSupplierController::class, 'destroy'])->name('suppliers.destroy');
            Route::delete('transactions/{transaction}', [BudgetTransactionController::class, 'destroy'])->name('transactions.destroy');
            Route::delete('attachments/{attachment}', [BudgetTransactionAttachmentController::class, 'destroy'])->name('attachments.destroy');
            Route::delete('officers/{officer}', [AccountableOfficerController::class, 'destroy'])->name('officers.destroy');
        });
    });
    }); // end feature:budget

    // Documents module
    Route::prefix('documents')->name('documents.')->middleware('can:documents.view')->group(function () {
        // Documents dashboard (KPIs + rankings)
        Route::get('dashboard', [\App\Http\Controllers\DocumentDashboardController::class, 'index'])->name('dashboard');

        // Document Templates (paper library)
        Route::get('templates',                             [DocumentTemplateController::class, 'index'])->name('templates.index');
        Route::middleware('can:documents.create')->group(function () {
            Route::get('templates/create',                  [DocumentTemplateController::class, 'create'])->name('templates.create');
            Route::post('templates',                        [DocumentTemplateController::class, 'store'])->name('templates.store');
            Route::post('templates/{documentTemplate}/duplicate', [DocumentTemplateController::class, 'duplicate'])->name('templates.duplicate');
        });
        Route::middleware('can:documents.edit')->group(function () {
            Route::get('templates/{documentTemplate}/edit',                             [DocumentTemplateController::class, 'edit'])->name('templates.edit');
            Route::put('templates/{documentTemplate}',                                  [DocumentTemplateController::class, 'update'])->name('templates.update');
            Route::patch('templates/{documentTemplate}/versions/{version}/set-current', [DocumentTemplateController::class, 'setVersion'])->name('templates.versions.set');
            Route::patch('templates/{documentTemplate}/toggle',                         [DocumentTemplateController::class, 'toggle'])->name('templates.toggle');
        });
        Route::delete('templates/{documentTemplate}', [DocumentTemplateController::class, 'destroy'])
            ->middleware('can:documents.delete')
            ->name('templates.destroy');

        // Document Types (CMS setup)
        Route::get('types',                         [DocumentTypeController::class, 'index'])->name('types.index');
        Route::get('types/samples',                 [DocumentTypeController::class, 'samples'])->name('types.samples');
        Route::middleware('can:documents.create')->group(function () {
            Route::get('types/create',              [DocumentTypeController::class, 'create'])->name('types.create');
            Route::post('types',                    [DocumentTypeController::class, 'store'])->name('types.store');
        });
        Route::middleware('can:documents.edit')->group(function () {
            Route::get('types/{documentType}/edit',      [DocumentTypeController::class, 'edit'])->name('types.edit');
            Route::put('types/{documentType}',           [DocumentTypeController::class, 'update'])->name('types.update');
            Route::patch('types/{documentType}/toggle',  [DocumentTypeController::class, 'toggle'])->name('types.toggle');
        });
        Route::delete('types/{documentType}', [DocumentTypeController::class, 'destroy'])
            ->middleware('can:documents.delete')
            ->name('types.destroy');

        // Document Requests
        // NOTE: the controller additionally narrows per document type via its
        // issue_document.* permission (see DocumentRequestController::canIssue).
        // These module-level gates are the floor, not the whole check.
        Route::get('requests',                              [DocumentRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/resolve-defaults',             [DocumentRequestController::class, 'resolveDefaults'])->name('requests.resolve-defaults');
        Route::get('requests/fee-summary',                  [DocumentRequestController::class, 'feeSummaryPrint'])->name('requests.fee-summary')
            ->middleware('can:documents.fees');

        // Declared BEFORE "requests/{documentRequest}" — otherwise the wildcard
        // matches first and "requests/create" 404s looking for a request whose
        // id is the literal string "create".
        Route::middleware('can:documents.create')->group(function () {
            Route::get('requests/create',                   [DocumentRequestController::class, 'create'])->name('requests.create');
            Route::post('requests/preview',                 [DocumentRequestController::class, 'preview'])->name('requests.preview');
            Route::post('requests',                         [DocumentRequestController::class, 'store'])->name('requests.store');
            // Walk-ins (not registered residents) + what a document needs from the person
            Route::get('requests/walkins',                  [DocumentRequestController::class, 'walkinSearch'])->name('requests.walkins.search');
            Route::post('requests/walkins',                 [DocumentRequestController::class, 'walkinStore'])->name('requests.walkins.store');
            Route::get('requests/person-check',             [DocumentRequestController::class, 'personCheck'])->name('requests.person-check');
            Route::post('requests/person-photo',            [DocumentRequestController::class, 'personPhoto'])->name('requests.person-photo');
        });

        Route::get('requests/{documentRequest}',            [DocumentRequestController::class, 'show'])->name('requests.show');

        // Approving / releasing / rejecting moves a request through its
        // workflow — an edit of an existing record.
        Route::middleware('can:documents.edit')->group(function () {
            Route::patch('requests/{documentRequest}/approve',  [DocumentRequestController::class, 'approve'])->name('requests.approve');
            Route::patch('requests/{documentRequest}/release',  [DocumentRequestController::class, 'release'])->name('requests.release');
            Route::patch('requests/{documentRequest}/reject',   [DocumentRequestController::class, 'reject'])->name('requests.reject');
            Route::patch('requests/{documentRequest}/print',    [DocumentRequestController::class, 'countPrint'])->name('requests.print');
        });

        // Voiding cancels a released document but keeps it on record.
        Route::patch('requests/{documentRequest}/void', [DocumentRequestController::class, 'void'])
            ->middleware('can:documents.void')
            ->name('requests.void');

        Route::delete('requests/{documentRequest}', [DocumentRequestController::class, 'destroy'])
            ->middleware('can:documents.delete')
            ->name('requests.destroy');
    });

    // Users & Roles module
    // User management assigns roles, so leaving it ungated allowed any signed-in
    // account to create a user and grant it any role (privilege escalation).
    Route::get('users', [UserController::class, 'index'])->middleware('can:users.view')->name('users.index');
    Route::post('users', [UserController::class, 'store'])->middleware('can:users.create')->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('can:users.edit')->name('users.update');
    Route::post('users/{user}/reset-password', [UserController::class, 'sendResetLink'])->middleware(['can:users.reset', 'throttle:10,1'])->name('users.reset-password');
    Route::patch('users/{user}/toggle', [UserController::class, 'toggleActive'])->middleware('can:users.edit')->name('users.toggle');

    Route::get('roles', [RoleController::class, 'index'])->middleware('can:roles.view')->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->middleware('can:roles.create')->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.edit')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete')->name('roles.destroy');
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->middleware('can:roles.view')->name('roles.permissions');

    // My Profile (self-service, for the currently logged-in user)
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile/info', [ProfileController::class, 'updateInfo'])->name('profile.update-info');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');

    // Inventory module — one permission set per area (see PermissionSeeder):
    // inventory_items / _categories / _releases / _rentals / _schedule.
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // ── Categories ──────────────────────────────────────────────────────
        Route::middleware('can:inventory_categories.view')->group(function () {
            Route::get('categories',                            [InventoryController::class, 'categoriesIndex'])->name('categories.index');
            Route::middleware('can:inventory_categories.create')->group(function () {
                Route::get('categories/create',                 [InventoryController::class, 'categoriesCreate'])->name('categories.create');
                Route::post('categories',                       [InventoryController::class, 'categoriesStore'])->name('categories.store');
            });
            Route::middleware('can:inventory_categories.edit')->group(function () {
                Route::get('categories/{category}/edit',        [InventoryController::class, 'categoriesEdit'])->name('categories.edit');
                Route::put('categories/{category}',             [InventoryController::class, 'categoriesUpdate'])->name('categories.update');
            });
            Route::delete('categories/{category}',              [InventoryController::class, 'categoriesDestroy'])->name('categories.destroy')
                ->middleware('can:inventory_categories.delete');
        });

        // ── Items ───────────────────────────────────────────────────────────
        Route::middleware('can:inventory_items.view')->group(function () {
            Route::get('items',                                 [InventoryController::class, 'itemsIndex'])->name('items.index');
            Route::middleware('can:inventory_items.create')->group(function () {
                Route::get('items/create',                      [InventoryController::class, 'itemsCreate'])->name('items.create');
                Route::post('items',                            [InventoryController::class, 'itemsStore'])->name('items.store');
            });
            // The manage page serves both editing details (edit) and stock-in /
            // adjustments (stock); the controller checks for either.
            Route::get('items/{item}/edit',                     [InventoryController::class, 'itemsEdit'])->name('items.edit');
            Route::middleware('can:inventory_items.edit')->group(function () {
                Route::put('items/{item}',                      [InventoryController::class, 'itemsUpdate'])->name('items.update');
                Route::delete('items/{item}/image',             [InventoryController::class, 'itemsDeleteImage'])->name('items.image.delete');
            });
            Route::middleware('can:inventory_items.stock')->group(function () {
                Route::post('items/{item}/stock-in',            [InventoryController::class, 'itemsStockIn'])->name('items.stock-in');
                Route::post('items/{item}/adjust',              [InventoryController::class, 'itemsAdjust'])->name('items.adjust');
            });
            Route::delete('items/{item}',                       [InventoryController::class, 'itemsDestroy'])->name('items.destroy')
                ->middleware('can:inventory_items.delete');
        });

        // ── Releases ────────────────────────────────────────────────────────
        Route::middleware('can:inventory_releases.view')->group(function () {
            Route::get('releases',                              [InventoryController::class, 'releasesIndex'])->name('releases.index');
            Route::middleware('can:inventory_releases.create')->group(function () {
                Route::get('releases/create',                   [InventoryController::class, 'releasesCreate'])->name('releases.create');
                Route::post('releases',                         [InventoryController::class, 'releasesStore'])->name('releases.store');
            });
            // Wildcards after "create" — otherwise {release} matches ".../create".
            Route::get('releases/{release}',                    [InventoryController::class, 'releasesShow'])->name('releases.show');
            Route::get('releases/{release}/print',              [InventoryController::class, 'releasesPrint'])->name('releases.print');
            Route::middleware('can:inventory_releases.approve')->group(function () {
                Route::patch('releases/{release}/approve',      [InventoryController::class, 'releasesApprove'])->name('releases.approve');
                Route::patch('releases/{release}/release',      [InventoryController::class, 'releasesRelease'])->name('releases.release');
                Route::patch('releases/{release}/reject',       [InventoryController::class, 'releasesReject'])->name('releases.reject');
            });
        });

        // ── Rentals ─────────────────────────────────────────────────────────
        Route::middleware('can:inventory_rentals.view')->group(function () {
            Route::get('rentals',                               [InventoryRentalController::class, 'index'])->name('rentals.index');
            Route::middleware('can:inventory_rentals.create')->group(function () {
                Route::get('rentals/create',                    [InventoryRentalController::class, 'create'])->name('rentals.create');
                Route::post('rentals',                          [InventoryRentalController::class, 'store'])->name('rentals.store');
            });
            Route::get('rentals/{rental}',                      [InventoryRentalController::class, 'show'])->name('rentals.show');
            Route::middleware('can:inventory_rentals.edit')->group(function () {
                Route::patch('rentals/{rental}/release',        [InventoryRentalController::class, 'release'])->name('rentals.release');
                Route::post('rentals/{rental}/return',          [InventoryRentalController::class, 'returnItems'])->name('rentals.return');
                Route::patch('rentals/{rental}/payment',        [InventoryRentalController::class, 'updatePayment'])->name('rentals.payment');
                Route::patch('rentals/{rental}/cancel',         [InventoryRentalController::class, 'cancel'])->name('rentals.cancel');
            });
        });

        // ── Schedule ────────────────────────────────────────────────────────
        Route::middleware('can:inventory_schedule.view')->group(function () {
            Route::get('schedule',                              [\App\Http\Controllers\InventoryScheduleController::class, 'index'])->name('schedule.index');
            Route::get('schedule/events',                       [\App\Http\Controllers\InventoryScheduleController::class, 'events'])->name('schedule.events');
            Route::get('schedule/availability',                 [\App\Http\Controllers\InventoryScheduleController::class, 'availability'])->name('schedule.availability');
            Route::post('schedule',                             [\App\Http\Controllers\InventoryScheduleController::class, 'store'])->name('schedule.store')
                ->middleware('can:inventory_schedule.create');
            Route::middleware('can:inventory_schedule.edit')->group(function () {
                Route::put('schedule/{schedule}',               [\App\Http\Controllers\InventoryScheduleController::class, 'update'])->name('schedule.update');
                Route::patch('schedule/{schedule}/complete',    [\App\Http\Controllers\InventoryScheduleController::class, 'complete'])->name('schedule.complete');
                Route::patch('schedule/{schedule}/cancel',      [\App\Http\Controllers\InventoryScheduleController::class, 'cancel'])->name('schedule.cancel');
            });
        });
    });
});

// Route::group(['middleware' => 'superadmin'], function() {

