<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\ConfigController;
use App\Item;
use App\ItemComposition;
use App\ItemImport;
use App\OfflineTransaction;
use App\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Psy\debug;

class ItemController extends Controller
{
    public function selectUnitOfmeasure($sageUOM){
        if (strtolower($sageUOM)=="meter"){
            return "2X";
        }
        else if (strtolower($sageUOM)=="hour"){
            return "hr";
        }
        if (strtolower($sageUOM)=="packet"){
            return "PA";
        }
        else{
            return $sageUOM;
        }
    }

    public function storeItem(Request $request){
        Log::debug($request);
        $itemID= $request->query("ItemID");
        Log::debug('Data posted from sage300 server with id:'.$itemID);

        $itemController = new \App\Http\Controllers\Core\ItemController;
        $itemController->item_interfacing($itemID);

        return response()->json([
            "data"=>"success"
        ]);
    }

    public function compositionItem(Request $request){
        Log::debug($request);
        $itemID= $request->query("ItemID");
        $bomNumber= $request->query("BOMNumber");
        $itemCompID= $request->query("itemCompID");
        $Quantity= $request->query("Quantity");
        Log::debug('Data posted from sage300 server with id:'.$itemID);
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $baseUrl = $settings->baseUrl;

        Log::debug('Item Interfacing Procedure Called with id:'.$itemID);

        $zra_url = '/items/saveItemComposition';

            $existence_check_db = ItemComposition::where("itemCd",$itemID)->where('cpstItemCd',$itemCompID)->get();
            if (!count($existence_check_db)){
                $item = new ItemComposition();
                $item->itemCd = $itemID;
                $item->bom_number = $bomNumber;
                $item->cpstItemCd = $itemCompID;
                $item->cpstQty = $Quantity;
                $item->save();
                $regrNm= "Admin";
                $regrId= "Admin";

                $request_data = [
                    "tpin" => $tinNumber,
                    "bhfId" => $branchCode,
                    "itemCd" => $itemID,
                    "cpstItemCd" => $itemCompID,
                    "cpstQty" => $Quantity,
                    "regrNm" => $regrNm,
                    "regrId" => $regrId,
                ];
                Log::debug($request_data);

                $response = Http::post($baseUrl.$zra_url, $request_data);
                $result = $response->json();
                $message= $result['resultMsg'];
                ConfigController::LogDeviceData($zra_url,$request_data,$result);

                if ($result['resultCd']=="000"){

                    DB::update("update item_compositions set status='Interfaced',message='$message' where itemCd='$itemID'");
                    Log::debug($result);
                }
                else{
                    Log::debug("error code triggered!");
                    DB::update("update item_compositions set status='Pending',message='$message' where itemCd='$itemID'");

                    Log::debug($result["resultMsg"]);
                    if($response->failed()){
                        Log::alert('***ZRA Call Failed to be processed offline****');
                        $offline_trans = new OfflineTransaction();
                        $offline_trans->endpoint =  $zra_url ;
                        ;
                        $offline_trans->payload = json_encode($request_data,true);
                        $offline_trans->save();
                    }

                }
            }

        }

        public function importItems(Request $request){
            $settings = Settings::find(1);
            $tinNumber = $settings->tinNumber;
            $branchCode= $settings->branchCode;
            $lastReqDt= $settings->lastRequestDate;
            $baseUrl = $settings->baseUrl;
            $zra_url = '/imports/selectImportItems';
            Log::debug('Item Interfacing Procedure Called with request body:'.$request);
            $request_data = [
                "tpin" => $tinNumber,
                "bhfId" => $branchCode,
                "lastReqDt" => $lastReqDt,
            ];
            Log::debug($request_data);
            $response = Http::post($baseUrl.$zra_url, $request_data);
            $result = $response->json();
            $message= $result['resultMsg'];
            Log::debug($result);
            if ($result['resultCd']!="000"){
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
                    }
                    ConfigController::LogDeviceData($zra_url,$request_data,$result);
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
}
