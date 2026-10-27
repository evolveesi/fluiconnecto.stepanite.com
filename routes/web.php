<?php

use App\Http\Controllers\API\AppSettingsController;
use App\Http\Controllers\API\ConfigurationsAPIController;
use App\Http\Controllers\API\CreditNoteController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\PurchaseController;
use App\Http\Controllers\Users\PermissionController;
use App\Http\Controllers\Users\RoleController;
use App\Http\Controllers\Users\RolePermissionController;
use App\Http\Controllers\Users\UserController;
use \App\Http\Controllers\Core\ItemController;
use App\Http\Controllers\Core\StandardCodesController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'PagesController@index');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

#user management routes
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, "index"])->name('view-users')->middleware('has_permission:view-users');
    Route::post('update', [UserController::class, "update"])->name('update-user')->middleware('has_permission:update-user');
    Route::post('store', [UserController::class, "store"])->name('store-user')->middleware('has_permission:store-user');
});

#permission management routes
Route::prefix('permissions')->group(function () {
    Route::get('/', [PermissionController::class, "index"])->name('view-permissions');
    Route::post('store', [PermissionController::class, "store"])->name('store-permission')->middleware('has_permission:store-permission');
    Route::post('update', [PermissionController::class, "update"])->name('update-permission')->middleware('has_permission:update-permission');
});

#role management routes
Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, "index"])->name('view-roles')->middleware('has_permission:view-roles');
    Route::post('store', [RoleController::class, "store"])->name('store-role')->middleware('has_permission:store-role');
    Route::post('update', [RoleController::class, "update"])->name('update-role')->middleware('has_permission:update-role');
    Route::get('configure/{role_id}', [RoleController::class, "configure"])->name('configure-role');
});

#role permissions management routes
Route::prefix('role-permissions')->group(function () {
    Route::post('remove', [RolePermissionController::class, "remove"])->name('remove-permission')->middleware('has_permission:remove-permission');
    Route::post('add', [RolePermissionController::class, "add"])->name('add-permission')->middleware('has_permission:add-permission');
});
#role permissions management routes
Route::prefix('items-interfacing-report')->group(function () {
    Route::get('/', [ItemController::class, "index"])->name('view-items-report');//->middleware('has_permission:view-items-report');
    Route::post('pull-items', [ItemController::class, "pull_items"])->name('pull-items');//->middleware('has_permission:pull-items');
    Route::get('batch-push-items', [ItemController::class, "batch_push_items"])->name('batch-push-items');//->middleware('has_permission:batch-push-items');
    Route::post('push-item', [ItemController::class, "push_item"])->name('push-item');//->middleware('has_permission:push-items');
});

// Invoices
Route::get('invoices-interfacing-report', [InvoiceController::class, "index"])->name('invoices-interfacing-report');
Route::get('invoice-print/{id}', [InvoiceController::class, "printInvoice"])->name('invoice-print');
Route::get('invoice-reprint/{id}', [InvoiceController::class, "reprintInvoice"])->name('invoice-reprint');
Route::post('reinterface-invoice', [InvoiceController::class, "reinterfaceInvoice"])->name('reinterface-invoice');
Route::get('check-sage-uninterfaced-invoices', [InvoiceController::class, "checkSageUninterfacedInvoices"])->name('check-sage-uninterfaced-invoices');
Route::get('check-sage-uninterfaced-invoices/{invoice_number}', [InvoiceController::class, "checkSageUninterfacedInvoice"])->name('check-sage-uninterfaced-invoices');
Route::get('check-sage-uninterfaced-invoice/{invoiceUniquifier}', [InvoiceController::class, "checkSageUninterfacedInvoiceUniquifier"])->name('check-sage-uninterfaced-invoice-uniquifier');
Route::get('invoice-print-multi-sdc/{id}', [InvoiceController::class, "printInvoiceMultiSDC"])->name('invoice-print-multi-sdc');


//CRN & DN
Route::get('credit-notes-interfacing-report', [CreditNoteController::class, "index"])->name('credit-notes-interfacing-report');
Route::get('crdn-print/{id}', [CreditNoteController::class, "printCRDN"])->name('invoice-crdn');
Route::get('crdn-reprint/{id}', [CreditNoteController::class, "reprintCRDN"])->name('invoice-recrdn');
Route::post('reinterface-credit-note', [CreditNoteController::class, "reinterfaceCRDN"])->name('reinterface-credit-note');
Route::get('check-sage-uninterfaced-credit-notes', [CreditNoteController::class, "checkSageUninterfacedCreditNotes"])->name('check-sage-uninterfaced-credit-notes');

//CRN & DN
Route::get('xyz', [CreditNoteController::class, "index"])->name('credit-notes-interfacing-report');
Route::get('xyz/{id}', [CreditNoteController::class, "printCRDN"])->name('invoice-crdn');

#device initialization
Route::post('initialise-device', [\App\Http\Controllers\Core\ReportsController::class, "deviceIntit"])->name('initialise-device');
Route::get('device-requests-responses', [\App\Http\Controllers\Core\ReportsController::class, "deviceRequestResponses"])->name('device-requests-responses');

#item Classification Codes
Route::get('classification-codes', [\App\Http\Controllers\Core\ReportsController::class, "classificationCodes"])->name('classification-codes');
Route::get('classification-codes-list', [\App\Http\Controllers\Core\ReportsController::class, "classLists"])->name('classification-codes-list');

#reports
Route::get('get-standard-codes', [\App\Http\Controllers\Core\ReportsController::class, "getStandardCodes"])->name('get-standard-codes');
Route::get('standard-codes-list', [\App\Http\Controllers\Core\ReportsController::class, "standard_codes"])->name('standard-codes-list');
Route::get('fetch-imports',[\App\Http\Controllers\Core\ReportsController::class, 'getImports'])->name('fetch-imports');
Route::post('process-imports',[\App\Http\Controllers\Core\ReportsController::class, 'processImports'])->name('imports-process');
Route::post('process-purchase',[\App\Http\Controllers\Core\ReportsController::class, 'processPurchases'])->name('process-purchases');
Route::get('purchases-report',[\App\Http\Controllers\Core\ReportsController::class, 'index'])->name('purchases-report');
Route::get('cpurchases-report',[\App\Http\Controllers\Core\ReportsController::class, 'cPurchasesReport'])->name('cpurchases-report');
Route::get('imports-report',[\App\Http\Controllers\Core\ReportsController::class, 'importsReport'])->name('imports-report');
Route::get('cimports-report',[\App\Http\Controllers\Core\ReportsController::class, 'cimportsReport'])->name('cimports-report');
Route::get('crdr-purchases-report',[\App\Http\Controllers\Core\ReportsController::class, 'crdr'])->name('crdr-purchases-report');
Route::get('process-offline',[\App\Http\Controllers\Core\ReportsController::class, 'processOffline'])->name('process-offline');

#purchases
Route::get('fetch-purchases',[\App\Http\Controllers\Core\ReportsController::class, 'getPurchases'])->name('fetch-purchases');

#settings
Route::get('settings',[\App\Http\Controllers\SettingsController::class, 'index'])->name('get-settings');
Route::post('settings',[\App\Http\Controllers\SettingsController::class, 'store'])->name('update-settings');
