<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\ItemImport;
use App\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ItemImportsController extends Controller
{
    public static function fetch_item_imports(){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $lastReqDt= $settings->lastRequestDate;
        $baseUrl = $settings->baseUrl;
        $zra_url = '/imports/selectImportItems';
        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastReqDt,
        ];
        Log::debug('Fetching Items Imports Procedure:');
        Log::debug($request_data);
        $response = Http::post($baseUrl.$zra_url, $request_data);
        $result = $response->json();
        $message= $result['resultMsg'];
        Log::debug($result);
        if ($result['resultCd']=="000"){
            foreach ($result['data']["itemList"] as $item){
                $dclNo = $item['dclNo'];
                $hsCd = $item['hsCd'];
                $taskCd = $item['taskCd'];
                $existence_check_db = ItemImport::where("dclNo",$dclNo)->where('hsCd',$hsCd)->where("taskCd",$taskCd)->get();
                if (!count($existence_check_db)) {
                    $item_import = new ItemImport();
                    $item_import->taskCd = $item['taskCd'];
                    $item_import->dclDe = $item['dclDe'];
                    $item_import->itemSeq = $item['itemSeq'];
                    $item_import->dclNo = $item['dclNo'];
                    $item_import->hsCd = $item['hsCd'];
                    $item_import->itemNm = $item['itemNm'];
                    $item_import->imptItemsttsCd = $item['imptItemsttsCd'];
                    $item_import->orgnNatCd = $item['orgnNatCd'];
                    $item_import->exptNatCd = $item['exptNatCd'];
                    $item_import->pkg = $item['pkg'];
                    $item_import->pkgUnitCd = $item['pkgUnitCd'];
                    $item_import->qty = $item['qty'];
                    $item_import->qtyUnitCd = $item['qtyUnitCd'];
                    $item_import->totWt = $item['totWt'];
                    $item_import->netWt = $item['netWt'];
                    $item_import->spplrNm = $item['spplrNm'];
                    $item_import->agntNm = $item['agntNm'];
                    $item_import->invcFcurAmt = $item['invcFcurAmt'];
                    $item_import->invcFcurCd = $item['invcFcurCd'];
                    $item_import->invcFcurExcrt = $item['invcFcurExcrt'];
                    $item_import->save();
                    ItemImportsController::sage_create_method($item);
                }
                if ($result['resultCd']=="000"){
                    DB::update("update item_imports set status='Interfaced',message='$message' where hsCd='$hsCd' and  dclNo='$dclNo';");
                    Log::debug($result);
                }
                else{
                    Log::debug("error code triggered!");
                    DB::update("update item_imports set status='Pending',message='$message' where hsCd='$hsCd' and  dclNo='$dclNo';");

                    Log::debug($result["resultMsg"]);

                }
            }
        }
    }

    public static function sage_create_method($item){
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->post("$sage_base_url/PO/POReceipts",[
                "Vendor"=> "IMP001",
                "VendorAccountSet"=> "2000",
                "TermsCode"=> "30DAYS",
                "MultiplePurchaseOrders"=> false,
                "PurchaseOrders"=> 1,
                "Currency"=> "USD",
                "TaxGroup"=> "D",
                "TaxAuthority1"=> "ZRA",
                "TaxAuthority2"=> "",
                "TaxAuthority3"=> "",
                "TaxAuthority4"=> "",
                "TaxAuthority5"=> "",
                "TaxClass1"=> 6,
                "TaxClass2"=> 0,
                "TaxClass3"=> 0,
                "TaxClass4"=> 0,
                "TaxClass5"=> 0,
                "TaxBase1"=> 0,
                "TaxBase2"=> 0,
                "TaxBase3"=> 0,
                "TaxBase4"=> 0,
                "TaxBase5"=> 0,
                "ReceiptLine2s"=> [
                    [
                        "ItemNumber"=> "50221114",
                        "Location"=> "LSK",
                        "QuantityReceived"=> $item['qty'],
                        "UnitOfMeasure"=> $item['qtyUnitCd'],
                        "PurchaseOrderSequenceKey"=> $item['hsCd'],
                        "PurchaseOrderLineSequence"=> 49653901,
                        "ItemExists"=> true,
                        "ItemDescription"=> "Exempt - Wheat - 50Kg",
                        "UnitCost"=> 15.03,
                        "PurchaseOrderNumber"=> "PO49928",
                         "StockItem"=> true
                    ]
                ],
                "ReceiptPurchaseOrders"=> [
                    [
                        "PurchaseOrderSequenceKey"=>49653958,
                        "PurchaseOrderNumber"=> "PO49928",
                        "StoredInDatabaseTable"=> true
                    ]
                ]

        ]);
        $result = $response->json();
        Log::alert($result);
    }
}
