<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\API\StockItemController;
use App\Http\Controllers\Controller;
use App\Item;
use App\OfflineTransaction;
use App\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    public function selectUnitOfmeasure($sageUOM){
        Log::debug(json_encode($sageUOM));
        Log::debug(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
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

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $items = Item::where('status','Failed')->orderBy('itemCd','desc')->limit(1000)->get();
        return view('pages.core.items', [
            'items' => $items,
        ]);
    }

    public function pull_items(){
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICItems?%24top=20000");
        $items_list = $response->json();
        if (isset($items_list['error'])){
            return redirect()->back()->with("status",$items_list["error"]["message"]["value"]);
        }

        Log::debug($items_list);
        if (isset($items_list["value"] )){
            foreach($items_list["value"] as $item){
                $itemCd = $item["UnformattedItemNumber"];
                #check if item exists before save
                $item_existence_check = Item::where("itemCd",$itemCd)->get();
                if (count($item_existence_check)<1){
                    $item = new Item;
                    $item->itemCd = $itemCd;
                    $item->status = "pending";
                    $item->save();
                }
            }
            return redirect()->back()->with("status","Latest Items Successfully Pulled from ERP.");
        }
        return redirect()->back()->with("status","Failed to connect to ERP, check connection settings!");


    }

    public function push_item(Request $request){
        $itemCd = $request->itemCd;
        $this->item_interfacing($itemCd);
        return redirect()->back()->with("status","Item Successfully Interfaced/Synced into Smart Invoicing.");
    }
    public function yourFunction()
    {
        while (true) {
            $this->batch_push_items();
            sleep(300); // 6 minutes
        }
    }
    public function batch_push_items(){
        $count=0;
        $items = Item::where("status","Failed")->get();
        foreach ($items as $item){
            Log::info($count.' => '.$item->itemCd);
            if (strpos($item->itemCd, '+') !== false) {
                $item->status = 'Failed';
                $item->message='Invalid Item Code';
                $item->save();
                continue;
             }
             elseif (strpos($item->itemCd, '#') !== false) {
                $item->status = 'Failed';
                $item->message='Invalid Item Code';
                $item->save();
                continue;
             }
            
                
            $this->item_interfacing($item->itemCd);
            $count++;
        }
        
        return redirect()->back()->with("status","Item Batch Interfacing/Syncing into Smart Invoicing Completed Successfully .");
    }

    public function item_interfacing($itemID)
    {

        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);

        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $baseUrl = $settings->baseUrl;

        Log::debug('Item Interfacing Procedure Called with id:'.$itemID);

        $zra_url = 'items/saveItem';

        #check if item already exists
         $existence_check= $this->selectItem($itemID);

         if ($existence_check){
            if($existence_check['resultCd']=="000"){
                $zra_url = 'items/updateItem';
                DB::update("update items set updated_at=now()");
                Log::debug('$zra_url=updateNow()'.$zra_url);
            }
         }
         else
         {
             Log::debug('$zra_url=createNow()'.$zra_url);
             #also check if exists in database
             $existence_check_db = Item::where("itemCd",$itemID)->get();
             if (count($existence_check_db)<1){
                 $item = new Item();
                 $item->itemCd = $itemID;
                 $item->save();
             }
         }

        $existence_check_db = Item::where("itemCd",$itemID)->get();
        if (count($existence_check_db) < 1)
        {
            $item = new Item();
            $item->itemCd = $itemID;
            $item->save();
        }

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $filter = '$filter';

        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICItems?$filter=ItemNumber eq '$itemID'");
        
        $result = $response->json();
        $result = $result['value'][0];

        // Log::alert($result);
        $quantityOnhand = $result["QuantityOnHand"] ?? 0;
    
        $orgnNatCd = $pkgUnitCd = $itemType = $unspsc = '';
        foreach($result['ItemOptionalFields'] as $optField)
        {
            if(($optField['OptionalField'] == 'COUNTRYCODE') || ($optField['OptionalField'] == 'ORIGIN')) $orgnNatCd = $optField['Value'];
            elseif($optField['OptionalField'] == 'PACKAGINGUNT') $pkgUnitCd = $optField['Value'];
            elseif($optField['OptionalField'] == 'UNSPSC') $unspsc = $optField['Value'];
            elseif($optField['OptionalField'] == 'ITEMTYPE') $itemType = $optField['Value'];
        }
        $vatCatCd = $tlCatCd = $iplCatCd = $exciseTxCatCd = null;
        $taxCategoryCd = trim(explode('-', $result['ItemTaxAuthorities'][0]['SalesTaxClassDescription'])[0]);

        if($taxCategoryCd == 'TL' OR $taxCategoryCd == 'F')
        {
            $tlCatCd = $taxCategoryCd;
        }
        elseif($taxCategoryCd == 'IPL1' or $taxCategoryCd == 'IPL2')
        {
            $iplCatCd = $taxCategoryCd;
        }
        elseif($taxCategoryCd == 'ECM' or $taxCategoryCd == 'EXEEG')
        {
            $exciseTxCatCd = $taxCategoryCd;
        }
        elseif($taxCategoryCd == 'TOT')
        {
            $vatCatCd = $tlCatCd = $iplCatCd = $exciseTxCatCd = null;
        }
        else
        {
            $vatCatCd = $taxCategoryCd;
        }


        $isrcAplcbYn= "N";
        $useYn= "Y";
        $regrNm= "Admin";
        $regrId= "Admin";
        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "itemCd" => $result["ItemNumber"],
            "itemClsCd" => $unspsc,
            "itemTyCd" => $itemType,
            "itemNm" => $result["Description"],
            "itemStdNm" => $result["Description"],
            "orgnNatCd" => $orgnNatCd,
            "pkgUnitCd" => $pkgUnitCd,
            "qtyUnitCd" => $this->selectUnitOfmeasure($result["StockingUnitOfMeasure"]),
            "useYn" => $useYn,
            "vatCatCd" => $vatCatCd,
            "exciseTxCatCd" => $exciseTxCatCd,
            "regrNm" => $regrNm,
            "regrId" => $regrId,
            "modrNm"=> "ADMIN",
            "modrId"=> "ADMIN",
            "iplCatCd" => $iplCatCd,
            "tlCatCd" => $tlCatCd,
            "btchNo" => $result["UnformattedLotNumber"],
            "bcd" => "",
            "dftPrc" => 0.00,
            "sftyQty" => 0.00,
            "isrcAplcbYn" => $isrcAplcbYn,
        ];
        // Log::debug($request_data);

        $response = Http::post($baseUrl.$zra_url, $request_data);
        $result = $response->json();

        ConfigController::LogDeviceData($zra_url, $request_data, $result);

        if($response->failed()){
            Log::alert('***ZRA Call Failed to be processed offline****');
            $offline_trans = new OfflineTransaction();
            $offline_trans->endpoint = "items/saveItems";
            $offline_trans->payload = json_encode($request_data,true);
            $offline_trans->save();
            return;
        }
        $message= $result['resultMsg'];
        if ($result['resultCd']=="000")
        {
            DB::update("update items set status='Interfaced',message='$message' where itemCd='$itemID'");
            Log::debug($result);
            // StockItemController::initialiseStockBalance($itemID);
            
            StockItemController::initialiseSingleStockBalance($itemID, $quantityOnhand);
        }
        else
        {
            Log::debug("error code triggered!");
            DB::update("update items set status='Failed',message='$message' where itemCd='$itemID'");
            Log::debug($result["resultMsg"]);
        }

        return 1;

    }
    public function selectItem($itemID){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $baseUrl = $settings->baseUrl;

        Log::debug('Data posted from sage300 server with id:'.$itemID);

        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "itemCd" => $itemID,
        ];

        $response = Http::post($baseUrl.'items/selectItem', $request_data);
        $result = $response->json();
        if($response->failed()){
            Log::alert('***ZRA Call Failed to be processed offline****');
            $offline_trans = new OfflineTransaction();
            $offline_trans->endpoint =  'items/selectItem' ;
            ;
            $offline_trans->payload = json_encode($request_data,true);
            $offline_trans->save();
        }

        return $result;
    }
}
