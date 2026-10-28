<?php

use App\Http\Controllers\API\AppSettingsController;
use App\Http\Controllers\API\ConfigurationsAPIController;
use App\Http\Controllers\API\CreditNoteController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\PurchaseController;
use App\Http\Controllers\API\PurchaseDCNController;
use App\Http\Controllers\API\StockItemController;
use App\Http\Controllers\API\ServiceManagerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

#controller related to application settings imported from zra interface.
Route::get('/standard-codes', [AppSettingsController::class, 'getStandardCodes'])->name('standard-codes')->middleware(["check.api.response"]);

Route::get('/item-class-lists', [ConfigurationsAPIController::class, 'getItemClassList'])->name('item-class-lists')->middleware(["check.api.response"]);;
Route::get('/zra-notices', [ConfigurationsAPIController::class, 'getZRANotices'])->name('zra-notices')->middleware(["check.api.response"]);;
Route::get('/get-branches', [ConfigurationsAPIController::class, 'getBranches'])->name('get-branches')->middleware(["check.api.response"]);;
Route::post('/create-branch-user', [ConfigurationsAPIController::class, 'createBranchUser'])->name('create-branch-user')->middleware(["check.api.response"]);;

#customer controller section
Route::get('/create-branch-customer', [CustomerController::class, 'storeCustomer'])->name('create-branch-customer')->middleware(["check.api.response"]);;
Route::post('/search-branch-customer', [CustomerController::class, 'searchCustomer'])->name('search-branch-customer')->middleware(["check.api.response"]);;

#item controller section
Route::get('/create-item', [ItemController::class, 'storeItem'])->name('create-item')->middleware(["check.api.response"]);;
Route::get('/composition-item', [ItemController::class, 'compositionItem'])->name('composition-item')->middleware(["check.api.response"]);;
Route::get('/import-items', [ItemController::class, 'importItems'])->name('import-items');;
Route::get('/select-item/{itemID}', [ItemController::class, 'selectItem'])->name('select-item')->middleware(["check.api.response"]);;

#invoice controller section
Route::get('/send-invoice', [InvoiceController::class, 'sendInvoice'])->name('send-invoice')->middleware(["check.api.response"]);

Route::get('/home', [\App\Http\Controllers\HomeController::class, 'index'])->name('api-home');

#credit note controller
Route::get('/send-credit-note', [CreditNoteController::class, 'sendCreditNote'])->name('send-credit-note')->middleware(["check.api.response"]);
Route::get('/split-send-credit-note', [CreditNoteController::class, 'splitAndSendCreditNote'])->name('split-send-credit-note');

#purchases controller
Route::get('/get-purchases',[PurchaseController::class, 'getPurchases'])->name('get-purchases')->middleware(["check.api.response"]);
Route::get('/send-purchase',[PurchaseController::class, 'sendPurchase'])->name('send-purchase')->middleware(["check.api.response"]);
Route::get('/get-cdnpurchases',[PurchaseDCNController::class, 'sendPurchaseDCN'])->name('get-cdnpurchases')->middleware(["check.api.response"]);

#stock item controller
Route::get('/get-stock-item',[StockItemController::class, 'getStockItem'])->name('get-stock-item')->middleware(["check.api.response"]);
Route::get('/send-stock-item',[StockItemController::class, 'sendStockItem'])->name('send-stock-item')->middleware(["check.api.response"]);

#interfaceFromSage
Route::get('/stock-adjustment-sage',[StockItemController::class, 'interfaceFromSage'])->name('stock-adjustment-sage')->middleware(["check.api.response"]);
Route::get('t1',[StockItemController::class, 'testSQLSERVER'])->middleware(["check.api.response"]);
// interfaceStockAdjustment - new
Route::get('/stock-adjustment-header',[StockItemController::class, 'interfaceStockAdjustment'])->name('stock-adjustment-header')->middleware(["check.api.response"]);


Route::get('service-manager-invoice',[ServiceManagerController::class, 'serviceManagerInvoice'])->middleware(["check.api.response"]);
Route::get('service-manager-credit-note',[ServiceManagerController::class, 'serviceManagerCNote'])->middleware(["check.api.response"]);

//Invoices
Route::get('/split-send-invoice', [InvoiceController::class, 'splitAndSendInvoice'])->name("split-send-invoice");
