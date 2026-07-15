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
use App\Http\Controllers\BudgetLineItemController;
use App\Http\Controllers\BlotterController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\HouseholdController;
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

    Route::post('citizens/export', [CitizenController::class, 'export'])->middleware('can:citizens.export')->name('citizens.export');
    Route::get('citizens/demographics', [CitizenController::class, 'demographics'])->name('citizens.demographics');
    Route::get('citizens/register-minor', [CitizenController::class, 'createMinor'])->name('citizens.create-minor');
    Route::post('citizens/register-minor', [CitizenController::class, 'storeMinor'])->name('citizens.store-minor');
    Route::get('citizens/parent-search', [CitizenController::class, 'parentSearch'])->name('citizens.parent-search');
    Route::get('citizens/search', [CitizenController::class, 'search'])->name('citizens.search');
    Route::get('citizens/{citizen}/quick-history', [CitizenController::class, 'quickHistory'])->name('citizens.quick-history');
    Route::get('citizens/{citizen}/detail', [CitizenController::class, 'detail'])->name('citizens.detail');
    Route::post('citizens/{citizen}/tags', [TagController::class, 'assignToCitizen'])->name('citizens.tags.sync');

    // Tags management
    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::get('tags/all', [TagController::class, 'all'])->name('tags.all');
    Route::post('tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

    // Citizen IDs
    Route::get('citizens/ids', [CitizenIdController::class, 'index'])->name('citizens.ids.index');
    Route::post('citizens/ids', [CitizenIdController::class, 'store'])->name('citizens.ids.store');
    Route::get('citizens/ids/{citizenId}/print', [CitizenIdController::class, 'print'])->name('citizens.ids.print');
    Route::post('citizens/ids/{citizenId}/upload-signature', [CitizenIdController::class, 'uploadSignature'])->name('citizens.ids.upload-signature');
    Route::post('citizens/ids/{citizenId}/remove-signature', [CitizenIdController::class, 'removeSignature'])->name('citizens.ids.remove-signature');

    // ID Template Designer
    Route::get('citizens/ids/template/designer', [CitizenIdTemplateController::class, 'designer'])->name('citizens.ids.template.designer');
    Route::post('citizens/ids/template/save', [CitizenIdTemplateController::class, 'save'])->name('citizens.ids.template.save');
    Route::post('citizens/ids/template/upload-bg', [CitizenIdTemplateController::class, 'uploadBg'])->name('citizens.ids.template.upload-bg');
    Route::post('citizens/ids/template/remove-bg', [CitizenIdTemplateController::class, 'removeBg'])->name('citizens.ids.template.remove-bg');

    // Household module — must be BEFORE citizens resource to avoid {citizen} catching 'household'
    Route::prefix('citizens/household')->name('households.')->group(function () {
        Route::get('/',                         [HouseholdController::class, 'index'])->name('index');
        Route::get('retrieve',                  [HouseholdController::class, 'retrieve'])->name('retrieve');
        Route::get('details',                   [HouseholdController::class, 'getDetails'])->name('details');
        Route::post('store',                    [HouseholdController::class, 'store'])->name('store');
        Route::get('get',                       [HouseholdController::class, 'getHousehold'])->name('get');
        Route::post('update',                   [HouseholdController::class, 'update'])->name('update');
        Route::post('store-family',             [HouseholdController::class, 'storeFamily'])->name('store-family');
        Route::post('store-member',             [HouseholdController::class, 'storeFamilyMember'])->name('store-member');
        Route::post('remove-head',              [HouseholdController::class, 'removeFamilyHead'])->name('remove-head');
        Route::post('remove-member',            [HouseholdController::class, 'removeFamilyMember'])->name('remove-member');
        Route::post('set-household-head',       [HouseholdController::class, 'setHouseholdHead'])->name('set-household-head');
        Route::get('filter-citizens',           [HouseholdController::class, 'filterCitizens'])->name('filter-citizens');
        Route::get('citizen-address',           [HouseholdController::class, 'getCitizenAddress'])->name('citizen-address');
        Route::get('kpi',                       [HouseholdController::class, 'kpi'])->name('kpi');
        Route::get('search',                    [HouseholdController::class, 'search'])->name('search');
    });

    Route::resource('citizens', CitizenController::class);

    // Blotter module
    Route::patch('blotters/{blotter}/status', [BlotterController::class, 'updateStatus'])->name('blotters.status');
    Route::post('blotters/{blotter}/actions', [BlotterController::class, 'storeAction'])->name('blotters.actions.store');
    Route::patch('blotters/{blotter}/actions/{action}/outcome', [BlotterController::class, 'updateActionOutcome'])->name('blotters.actions.outcome');
    Route::delete('blotters/{blotter}/actions/{action}', [BlotterController::class, 'destroyAction'])->name('blotters.actions.destroy');
    Route::resource('blotters', BlotterController::class);

    // Events & Attendance module
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/',                                     [EventController::class, 'index'])->name('index');
        Route::get('kpi',                                   [EventController::class, 'kpi'])->name('kpi');
        Route::get('retrieve',                              [EventController::class, 'retrieve'])->name('retrieve');
        Route::get('create',                                [EventController::class, 'create'])->name('create');
        Route::post('/',                                    [EventController::class, 'store'])->name('store');
        Route::get('{event}',                               [EventController::class, 'show'])->name('show');
        Route::get('{event}/edit',                          [EventController::class, 'edit'])->name('edit');
        Route::put('{event}',                               [EventController::class, 'update'])->name('update');
        Route::delete('{event}',                            [EventController::class, 'destroy'])->name('destroy');
        Route::patch('{event}/toggle-status',               [EventController::class, 'toggleStatus'])->name('toggle-status');
        // Attendance
        Route::get('{event}/attendance',                    [EventController::class, 'attendanceList'])->name('attendance.list');
        Route::post('{event}/checkin/qr',                   [EventController::class, 'checkinQr'])->name('checkin.qr');
        Route::post('{event}/checkin/manual',               [EventController::class, 'checkinManual'])->name('checkin.manual');
        Route::post('{event}/attendance/remove',            [EventController::class, 'removeAttendance'])->name('attendance.remove');
        Route::get('{event}/citizens/search',               [EventController::class, 'searchCitizens'])->name('citizens.search');
        // Bulk add
        Route::post('{event}/bulk/preview',                 [EventController::class, 'bulkPreview'])->name('bulk.preview');
        Route::post('{event}/bulk/add',                     [EventController::class, 'bulkAdd'])->name('bulk.add');
        // Raffle (organizer — auth required)
        Route::get('{event}/raffle',                        [EventController::class, 'rafflePage'])->name('raffle.page');
        Route::post('{event}/raffle/generate-pin',          [EventController::class, 'generatePin'])->name('raffle.generate-pin');
        Route::get('{event}/raffle/pool',                   [EventController::class, 'rafflePool'])->name('raffle.pool');
        Route::post('{event}/raffle/winner',                [EventController::class, 'recordWinner'])->name('raffle.winner');
        Route::get('{event}/raffle/winners',                [EventController::class, 'winnerHistory'])->name('raffle.winners');
    });

    Route::get('birthdays', [BirthdayController::class, 'index'])->name('birthdays.index');
    Route::post('birthdays/export', [BirthdayController::class, 'export'])->name('birthdays.export');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('settings/officials', [OfficialController::class, 'index'])->name('officials.index');
    Route::post('settings/officials', [OfficialController::class, 'store'])->name('officials.store');
    Route::put('settings/officials/{official}', [OfficialController::class, 'update'])->name('officials.update');
    Route::delete('settings/officials/{official}', [OfficialController::class, 'destroy'])->name('officials.destroy');

    Route::get('settings/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('settings/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::put('settings/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::patch('settings/addresses/{address}/toggle', [AddressController::class, 'toggleActive'])->name('addresses.toggle');

    // Budget module
    Route::get('budget', [BudgetDashboardController::class, 'index'])->name('budget.index');

    Route::prefix('budget')->name('budget.')->group(function () {
        // Fiscal years
        Route::post('fiscal-years', [FiscalYearController::class, 'store'])->name('fiscal-years.store');
        Route::patch('fiscal-years/{fiscalYear}/activate', [FiscalYearController::class, 'setActive'])->name('fiscal-years.activate');
        Route::delete('fiscal-years/{fiscalYear}', [FiscalYearController::class, 'destroy'])->name('fiscal-years.destroy');

        // Income Estimates
        Route::get('income-estimates', [IncomeEstimateController::class, 'index'])->name('income-estimates.index');
        Route::post('income-estimates', [IncomeEstimateController::class, 'store'])->name('income-estimates.store');
        Route::put('income-estimates/{incomeEstimate}', [IncomeEstimateController::class, 'update'])->name('income-estimates.update');
        Route::delete('income-estimates/{incomeEstimate}', [IncomeEstimateController::class, 'destroy'])->name('income-estimates.destroy');

        // Budget Programs (PPA list)
        Route::get('programs', [BudgetProgramController::class, 'index'])->name('programs.index');
        Route::post('programs', [BudgetProgramController::class, 'store'])->name('programs.store');
        Route::put('programs/{program}', [BudgetProgramController::class, 'update'])->name('programs.update');
        Route::delete('programs/{program}', [BudgetProgramController::class, 'destroy'])->name('programs.destroy');

        // Budget Line Items
        Route::get('line-items', [BudgetLineItemController::class, 'index'])->name('line-items.index');
        Route::get('line-items/program-items', [BudgetLineItemController::class, 'programItems'])->name('line-items.program-items');
        Route::post('line-items', [BudgetLineItemController::class, 'store'])->name('line-items.store');
        Route::put('line-items/{lineItem}', [BudgetLineItemController::class, 'update'])->name('line-items.update');
        Route::delete('line-items/{lineItem}', [BudgetLineItemController::class, 'destroy'])->name('line-items.destroy');
        Route::post('line-items/seed-defaults', [BudgetLineItemController::class, 'seedDefaults'])->name('line-items.seed-defaults');

        // Budget Matrix — read-only view, amounts come from line items
        Route::get('allocations', [BudgetAllocationController::class, 'index'])->name('allocations.index');

        // Suppliers
        Route::get('suppliers', [BudgetSupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/search', [BudgetSupplierController::class, 'search'])->name('suppliers.search');
        Route::post('suppliers', [BudgetSupplierController::class, 'store'])->name('suppliers.store');
        Route::put('suppliers/{supplier}', [BudgetSupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [BudgetSupplierController::class, 'destroy'])->name('suppliers.destroy');

        // Vouchers (DV / PCV / Payroll)
        Route::get('transactions', [BudgetTransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/create', [BudgetTransactionController::class, 'create'])->name('transactions.create');
        Route::post('transactions', [BudgetTransactionController::class, 'store'])->name('transactions.store');
        Route::get('transactions/{transaction}', [BudgetTransactionController::class, 'show'])->name('transactions.show');
        Route::get('transactions/{transaction}/print', [BudgetTransactionController::class, 'print'])->name('transactions.print');
        Route::patch('transactions/{transaction}/status', [BudgetTransactionController::class, 'updateStatus'])->name('transactions.status');
        Route::delete('transactions/{transaction}', [BudgetTransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::post('transactions/{transaction}/attachments', [BudgetTransactionAttachmentController::class, 'store'])->name('transactions.attachments.store');
        Route::get('attachments/{attachment}/download', [BudgetTransactionAttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('attachments/{attachment}', [BudgetTransactionAttachmentController::class, 'destroy'])->name('attachments.destroy');

        // Accountable Officers
        Route::get('officers', [AccountableOfficerController::class, 'index'])->name('officers.index');
        Route::post('officers', [AccountableOfficerController::class, 'store'])->name('officers.store');
        Route::put('officers/{officer}', [AccountableOfficerController::class, 'update'])->name('officers.update');
        Route::delete('officers/{officer}', [AccountableOfficerController::class, 'destroy'])->name('officers.destroy');

        // Cash Advances
        Route::get('cash-advances', [CashAdvanceController::class, 'index'])->name('cash-advances.index');
        Route::get('cash-advances/create', [CashAdvanceController::class, 'create'])->name('cash-advances.create');
        Route::post('cash-advances', [CashAdvanceController::class, 'store'])->name('cash-advances.store');
        Route::get('cash-advances/{cashAdvance}', [CashAdvanceController::class, 'show'])->name('cash-advances.show');
        Route::get('cash-advances/{cashAdvance}/edit', [CashAdvanceController::class, 'edit'])->name('cash-advances.edit');
        Route::put('cash-advances/{cashAdvance}', [CashAdvanceController::class, 'update'])->name('cash-advances.update');

        // Liquidation Reports
        Route::get('cash-advances/{cashAdvance}/liquidate', [LiquidationReportController::class, 'create'])->name('liquidations.create');
        Route::post('cash-advances/{cashAdvance}/liquidate', [LiquidationReportController::class, 'store'])->name('liquidations.store');
        Route::get('liquidations/{liquidation}', [LiquidationReportController::class, 'show'])->name('liquidations.show');
        Route::get('liquidations/{liquidation}/edit', [LiquidationReportController::class, 'edit'])->name('liquidations.edit');
        Route::put('liquidations/{liquidation}', [LiquidationReportController::class, 'update'])->name('liquidations.update');
        Route::patch('liquidations/{liquidation}/close', [LiquidationReportController::class, 'close'])->name('liquidations.close');

        // Budget settings (statutory thresholds — configurable per barangay)
        Route::get('settings', [BudgetSettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [BudgetSettingController::class, 'update'])->name('settings.update');

        // Audit log
        Route::get('logs', [BudgetLogController::class, 'index'])->name('logs.index');
    });

    // Documents module
    Route::prefix('documents')->name('documents.')->group(function () {
        // Document Templates (paper library)
        Route::get('templates',                             [DocumentTemplateController::class, 'index'])->name('templates.index');
        Route::get('templates/create',                      [DocumentTemplateController::class, 'create'])->name('templates.create');
        Route::post('templates',                            [DocumentTemplateController::class, 'store'])->name('templates.store');
        Route::get('templates/{documentTemplate}/edit',                                      [DocumentTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('templates/{documentTemplate}',                                           [DocumentTemplateController::class, 'update'])->name('templates.update');
        Route::patch('templates/{documentTemplate}/versions/{version}/set-current',          [DocumentTemplateController::class, 'setVersion'])->name('templates.versions.set');
        Route::patch('templates/{documentTemplate}/toggle',                                  [DocumentTemplateController::class, 'toggle'])->name('templates.toggle');
        Route::post('templates/{documentTemplate}/duplicate',                                [DocumentTemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::delete('templates/{documentTemplate}',                                        [DocumentTemplateController::class, 'destroy'])->name('templates.destroy');

        // Document Types (CMS setup)
        Route::get('types',                         [DocumentTypeController::class, 'index'])->name('types.index');
        Route::get('types/create',                  [DocumentTypeController::class, 'create'])->name('types.create');
        Route::post('types',                        [DocumentTypeController::class, 'store'])->name('types.store');
        Route::get('types/{documentType}/edit',          [DocumentTypeController::class, 'edit'])->name('types.edit');
        Route::put('types/{documentType}',               [DocumentTypeController::class, 'update'])->name('types.update');
        Route::patch('types/{documentType}/toggle',      [DocumentTypeController::class, 'toggle'])->name('types.toggle');
        Route::delete('types/{documentType}',            [DocumentTypeController::class, 'destroy'])->name('types.destroy');

        // Document Requests
        Route::get('requests',                              [DocumentRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/create',                       [DocumentRequestController::class, 'create'])->name('requests.create');
        Route::get('requests/resolve-defaults',             [DocumentRequestController::class, 'resolveDefaults'])->name('requests.resolve-defaults');
        Route::post('requests/preview',                     [DocumentRequestController::class, 'preview'])->name('requests.preview');
        Route::post('requests',                             [DocumentRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{documentRequest}',            [DocumentRequestController::class, 'show'])->name('requests.show');
        Route::patch('requests/{documentRequest}/approve',  [DocumentRequestController::class, 'approve'])->name('requests.approve');
        Route::patch('requests/{documentRequest}/release',  [DocumentRequestController::class, 'release'])->name('requests.release');
        Route::patch('requests/{documentRequest}/reject',   [DocumentRequestController::class, 'reject'])->name('requests.reject');
        Route::delete('requests/{documentRequest}',         [DocumentRequestController::class, 'destroy'])->name('requests.destroy');
    });

    // Users & Roles module
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');

    // My Profile (self-service, for the currently logged-in user)
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile/info', [ProfileController::class, 'updateInfo'])->name('profile.update-info');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');

    // Inventory module
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // Categories
        Route::get('categories',                                [InventoryController::class, 'categoriesIndex'])->name('categories.index');
        Route::get('categories/create',                         [InventoryController::class, 'categoriesCreate'])->name('categories.create');
        Route::post('categories',                               [InventoryController::class, 'categoriesStore'])->name('categories.store');
        Route::get('categories/{category}/edit',                [InventoryController::class, 'categoriesEdit'])->name('categories.edit');
        Route::put('categories/{category}',                     [InventoryController::class, 'categoriesUpdate'])->name('categories.update');
        Route::delete('categories/{category}',                  [InventoryController::class, 'categoriesDestroy'])->name('categories.destroy');

        // Items
        Route::get('items',                                     [InventoryController::class, 'itemsIndex'])->name('items.index');
        Route::get('items/create',                              [InventoryController::class, 'itemsCreate'])->name('items.create');
        Route::post('items',                                    [InventoryController::class, 'itemsStore'])->name('items.store');
        Route::get('items/{item}/edit',                         [InventoryController::class, 'itemsEdit'])->name('items.edit');
        Route::put('items/{item}',                              [InventoryController::class, 'itemsUpdate'])->name('items.update');
        Route::post('items/{item}/stock-in',                    [InventoryController::class, 'itemsStockIn'])->name('items.stock-in');
        Route::post('items/{item}/adjust',                      [InventoryController::class, 'itemsAdjust'])->name('items.adjust');
        Route::delete('items/{item}/image',                     [InventoryController::class, 'itemsDeleteImage'])->name('items.image.delete');

        // Releases
        Route::get('releases',                                  [InventoryController::class, 'releasesIndex'])->name('releases.index');
        Route::get('releases/create',                           [InventoryController::class, 'releasesCreate'])->name('releases.create');
        Route::post('releases',                                 [InventoryController::class, 'releasesStore'])->name('releases.store');
        Route::get('releases/{release}',                        [InventoryController::class, 'releasesShow'])->name('releases.show');
        Route::patch('releases/{release}/approve',              [InventoryController::class, 'releasesApprove'])->name('releases.approve');
        Route::patch('releases/{release}/release',              [InventoryController::class, 'releasesRelease'])->name('releases.release');
        Route::patch('releases/{release}/reject',               [InventoryController::class, 'releasesReject'])->name('releases.reject');
        Route::get('releases/{release}/print',                  [InventoryController::class, 'releasesPrint'])->name('releases.print');
    });
});

// ── Public raffle audience view (no login required, PIN-protected) ────────────
Route::prefix('events')->name('events.')->group(function () {
    Route::get('{event}/raffle/public',              [EventController::class, 'publicRafflePage'])->name('raffle.public');
    Route::get('{event}/raffle/public/pool',         [EventController::class, 'publicRafflePool'])->name('raffle.public.pool');
    Route::get('{event}/raffle/public/winners',      [EventController::class, 'publicWinnerHistory'])->name('raffle.public.winners');
    Route::post('{event}/raffle/public/checkin-qr',  [EventController::class, 'publicCheckinQr'])->name('raffle.public.checkin-qr');
    Route::post('{event}/raffle/public/winner',      [EventController::class, 'publicRecordWinner'])->name('raffle.public.winner');
});

// Route::group(['middleware' => 'superadmin'], function() {

