<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Core\ConfigController;
use App\Models\StockItemHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Settings;
use App\StockItem;
use Illuminate\Support\Facades\DB;

class StockItemController extends Controller
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

    public function testSQLSERVER(){
        $data =DB::connection('sqlsrv')->table('icitems')->get();
        dd($data);
    }
    public function getSarTypeCode($transtypeCode,$type){
        /*
        *01 - Import Incoming
        * 02-Purchase Incoming
        * 03-Return Incoming
        * 04-Stock Movement Incoming
        * 05-Processing Incoming
        * 06-Adjustment Incoming
        * 11-Sale Outgoing
        * 12-Return Outgoing
        * 13-Stock Movement Outgoing
        * 14-Processing Outgoing
        * 15-Discading Outgoing
        * 16-Adjustment Outgoing

        */
        Log::debug("Ndatanga");
        Log::debug($type);
        Log::debug($transtypeCode);
        Log::debug("Ndapendza");

        if($type=="ADJ"){
            switch ($transtypeCode) {
                case "1":  return '06';//Stock Adjustment In
                case "2": return "16"; // Stock Adjustment Out
                case "5":  return '06';//Stock Adjustment In
                case "6": return "16"; // Stock Adjustment Out
                default: return '16';
              }
        }
        elseif($type=="ASM"){
            switch ($transtypeCode) {
                case "1":  return '05';//PROCESING INCOMING
                case "101": return "14"; // PROCESING OUTGOING
              }
        }
        elseif($type=="IU"){
            switch ($transtypeCode) {
                case "15":  return '16';//OUTGOING DISCARDING
              }
        }
        else{
            switch ($transtypeCode) {
                case "1":  return '01'; // Receipt
                case "2": return "13"; //decrease
                case "12":  return '12'; // Receipt Adjustment
                case "6":  return '06'; // Receipt Return
                case "16":  return '16'; // Shipment
                case "4":  return '04'; // Shipment Return
                case "13":  return '16'; // Adjustment Quantity Increase
                case "11":  return '11'; // Adjustment Quantity Decrease
                case "3":  return '03'; // Adjustment Cost Increase
                case "12":  return '16'; // Adjustment Cost Decrease
                default: return '00';
              }
        }

    }


    public function fetchRawMaterials($itemNo, $qty,$transType,$transRef,$transDate,$typer){
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICBillsOfMaterial(UnformattedItemNumber='$itemNo',BOMNumber='$transRef')");
        $result = $response->json();

        Log::debug('========= result of ICBillsOfMaterial');
        Log::debug($result);
        #fomulation
        $buildQty = $result['BuildQuantity'] ?? 0;
        $BillsOfMaterialComponents = $result['BillsOfMaterialComponents'] ?? [];
        foreach($BillsOfMaterialComponents as $BillsOfMaterialComponent){
            $itemID_ = $BillsOfMaterialComponent['ComponentItemNumber'];
            $interfaceQuantity = $buildQty*$qty*$BillsOfMaterialComponent['Quantity'];
            $transRef .= $BillsOfMaterialComponent['LineNumber'];
            $this->stockItemInterfacingProgram($itemID_,$interfaceQuantity,'101',$transRef,$transDate,$typer);
        }
    }

    public function stockItemInterfacingProgram($itemID,$qty,$transType,$transRef,$transDate, $typer){
        ini_set('max_execution_time', 36000); // 3600 seconds = 60 minutes
        set_time_limit(36000);
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $baseUrl = $settings->baseUrl;
        // Log::debug($transRef,$itemID);

        $totTaxblAmt = 0;//$request->query("totTaxblAmt");
        $splyAmnt = 0;//$request->query("splyAmnt");
        $prc = 0;//$request->query("prc");
        $iplAmt = 0;//$request->query("iplAmt");
        $tlAmt = 0;//$request->query("tlAmt");
        $totAmt = 0;//$request->query("totAmt");
        $totTaxAmt = 0;//$request->query("totTaxAmt");

        Log::debug('Stock Item Adjustment for Item with No.:'.$itemID);


        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');
        $filter = '$filter';
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICItems?$filter=ItemNumber eq '$itemID'");
        $result = $response->json();
        $result = $result['value'][0];

        $orgnNatCd = $pkgUnitCd = $unspsc = '';
        foreach($result['ItemOptionalFields'] as $optField)
        {
            if(($optField['OptionalField'] == 'COUNTRYCODE') || ($optField['OptionalField'] == 'ORIGIN')) $orgnNatCd = $optField['Value'];
            elseif($optField['OptionalField'] == 'PACKAGINGUNT') $pkgUnitCd = $optField['Value'];
            elseif($optField['OptionalField'] == 'UNSPSC') $unspsc = $optField['Value'];
        }
        $vatCatCd = $tlCatCd = $iplCatCd = $exciseTxCatCd = null;
        $taxCategoryCd= trim(explode('-', $result['ItemTaxAuthorities'][0]['SalesTaxClassDescription'])[0]);

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
        else
        {
            $vatCatCd = $taxCategoryCd;
        }
        $zra_stock_item_array = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            'sarNo' => $transRef,
            'orgSarNo' => 0,
            'regTyCd' => "M",
            'custTpin' => NULL,
            'custNm' => NULL,
            'custBhfId' => NULL,
            'sarTyCd' => $this->getSarTypeCode($transType,$typer),
            'ocrnDt' => $transDate,
            'regrId' => 'Admin',
            'regrNm' => 'Admin',
            'modrNm' => 'Admin',
            'modrId' => 'Admin',
            'remark' => 'STOCK ADJUSTMENT',
        ];
        $qtyUnitCd = $this->selectUnitOfmeasure($result['StockingUnitOfMeasure']);

        $stock_itemS = [
            "itemSeq"=> 1,
            "itemCd"=> $result['ItemNumber'],
            "itemClsCd"=> $unspsc,
            "itemNm"=> $result['Description'],
            "bcd"=> NULL,
            "pkg"=> floatval($qty),
            "pkgUnitCd"=> $pkgUnitCd,
            "qtyUnitCd"=>$qtyUnitCd,
            "qty"=> $qty,
            "itemExprDt"=> NULL,
            "totDcAmt"=> 0,
            "iplCatCd"=> $iplCatCd,
            "tlCatCd"=> $tlCatCd,
            "exciseTxCatC"=> $exciseTxCatCd,
            "prc"=> round($prc,2),
            "splyAmt"=> round($splyAmnt,2),
            "iplAmt"=> round($iplAmt,2),
            "tlAmt"=> round($tlAmt,2),
            "exciseTxAmt"=> round(0,2),
            "taxblAmt"=> round(0,2),
            "vatCatCd"=> $vatCatCd,
            "taxAmt"=> round($totTaxAmt,2),
            "totAmt"=> round($totAmt,2)
        ];
        $zra_stock_item_array["itemList"][] = $stock_itemS;
        $zra_stock_item_array["totTaxblAmt"] = round($totTaxblAmt,2);
        $zra_stock_item_array["totTaxAmt"] = round($totTaxAmt,2);
        $zra_stock_item_array["totAmt"] = round($totAmt,2);
        $zra_stock_item_array["totItemCnt"] = 1;
        StockItemController::interfaceStockItem($zra_stock_item_array);
    }

    public function interfaceFromSage(Request $request){

        //parameters from stock adjustments
        $itemID= $request->query("itemCd");
        $qty= $request->query("qty");
        $transType= $request->query("sarTyCd");
        $transRef= $request->query("transRef");
        // Log::debug($transRef,$itemID);

        $transDate= $request->query("ocrnDt");
        $typer= $request->query("typer");
        Log::debug($typer);
        $this->stockItemInterfacingProgram($itemID,$qty,$transType,$transRef,$transDate,$typer);
        $this->fetchRawMaterials($itemID,$qty,'101',$transRef,$transDate,$typer);

    }

    public function interfaceStockAdjustment(Request $request)
    {
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $sequenceNumber = $request->query('sequenceNumber');
        Log::info('the sequence number '.$sequenceNumber);

        ///IC/ICAdjustments(1473)
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICAdjustments($sequenceNumber)");
        $result = $response->json();

        Log::info("+++++++ Stock Adjustment respose");
        Log::info($result);

        $details_arr = $result['AdjustmentDetails'] ?? [];
        $transDate = $result['PostingDate'] ?? null;
        if(!empty($transDate)) $transDate = date('Ymd', strtotime($result['PostingDate']));
        foreach($details_arr as $adjustment_record)
        {
            $itemID = $adjustment_record['ItemNumber'];
            $qty = $adjustment_record['Quantity'];
            $transType = $this->translateAdjustmentTransType($adjustment_record['TransactionType']);
            $transRef = intval($adjustment_record['SequenceNumber'].''.$adjustment_record['LineNumber']);

            $typer = 'ADJ';
            $this->interfaceFromSageAdjustment($itemID,$qty,$transType,$transRef,$transDate,$typer);
        }
    }

    public function translateAdjustmentTransType($type)
    {
        if($type == 'QuantityIncrease') return 1;
        elseif($type == 'QuantityDecrease') return 2;
        elseif($type == 'CostIncrease') return 3;
        elseif($type == 'CostDecrease') return 4;
        elseif($type == 'BothIncrease') return 5;
        elseif($type == 'BothDecrease') return 6;
        else return null;
    }

    public function interfaceFromSageAdjustment($itemID,$qty,$transType,$transRef,$transDate,$typer){

        Log::debug($typer);
        $this->stockItemInterfacingProgram($itemID,$qty,$transType,$transRef,$transDate,$typer);
        $this->fetchRawMaterials($itemID,$qty,'101',$transRef,$transDate,$typer);

    }

    public function getStockItem(Request $request)
    {
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode = $settings->branchCode;
        $zra_base_url = $settings->baseUrl;

        $lastReqDt = "20231215000000";
        $last_req_lookup = StockItem::latest()->first();
        if (!is_null($last_req_lookup)) {
            $lastReqDt = $last_req_lookup->resultDt;
        }

        $zra_stock_item_array = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastReqDt,
        ];

        $response = Http::post($zra_base_url . "/stock/selectStockItems", $zra_stock_item_array);
        $result = $response->json();
        ConfigController::LogDeviceData("/stock/selectStockItems", $zra_stock_item_array, $result);

        Log::info($result);
        Log::info(json_encode($zra_stock_item_array));

        return response()->json($result);
    }

    public static function interfaceStockItem($stockItem)
    {
        Log::debug('*****Start intefacing stock item****');
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);

        $settings = Settings::find(1);
        $baseUrl = $settings->baseUrl;
        $zra_url = 'stock/saveStockItems';
        // Log::debug('after array:');
        Log::debug(json_encode($stockItem));

        $stockItemList = $stockItem["itemList"];
        $response = Http::timeout(720)->post($baseUrl . $zra_url, $stockItem);
        $result = $response->json();
        ConfigController::LogDeviceData("/stock/saveStockItems", $stockItem, $result);

        Log::debug($result);
        Log::debug('*****Finished intefacing stock item****');

        if (isset($result['resultCd']) and $result['resultCd'] == "000")
        {
            StockItemController::interfaceStockMaster($stockItemList);
            Log::debug("SUCCESS RESPONSE FROM STOCK code triggered!");
            Log::debug($result);
            // Log::debug($stockItem);
        }
        else
        {
            Log::debug("error code triggered!");
            if (isset($result['resultMsg'])) Log::debug($result["resultMsg"]);
            Log::debug($stockItem);
        }

    }

    public static function interfaceStockMaster($stockItemList)
    {
        Log::info('****Interfacing stock master*****');
        $settings = Settings::find(1);
        $baseUrl = $settings->baseUrl;
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $request_data = [
            "tpin" => $settings->tinNumber,
            "bhfId" => $settings->branchCode,
            "regrId" => "Admin",
            "regrNm" => "Admin",
            "modrNm" => "Admin",
            "modrId" => "Admin"
        ];
        foreach ($stockItemList as $stockItem){
            $itemID = $stockItem['itemCd'];
            $filter = '$filter';
            $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICItems?$filter=ItemNumber eq '$itemID'");
            $result = $response->json();
            $result = $result['value'][0];

            $quantity = $result["QuantityOnHand"];
            $request_data_list=
                [
                    "itemCd" => $itemID,
                    "rsdQty" => $quantity
                ];
            $request_data["stockItemList"][]= $request_data_list;
        }
        $zra_url = "/stockMaster/saveStockMaster";
        $response = Http::timeout(720)->post($baseUrl . $zra_url, $request_data);
        $result = $response->json();
        if ($result['resultCd'] == "000")
        {
            Log::debug($result);
        }
        else
        {
            Log::debug("error code triggered!");
            Log::debug($result["resultMsg"]);
        }
    }

    public static function initialiseStockBalance($itemID)
    {
        Log::info('****Interfacing stock master*****');
        $settings = Settings::find(1);
        $baseUrl = $settings->baseUrl;
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $request_data = [
            "tpin" => $settings->tinNumber,
            "bhfId" => $settings->branchCode,
            "regrId" => "Admin",
            "regrNm" => "Admin",
            "modrNm" => "Admin",
            "modrId" => "Admin"
        ];
        $filter = '$filter';
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICItems?$filter=ItemNumber eq '$itemID'");
        $result = $response->json();
        $result = $result['value'][0];

        $quantity = $result["QuantityOnHand"] ?? 0;
        $request_data_list=
            [
                "itemCd" => $itemID,
                "rsdQty" => $quantity
            ];
        $request_data["stockItemList"][]= $request_data_list;

        $zra_url = "/stockMaster/saveStockMaster";
        $response = Http::post($baseUrl . $zra_url, $request_data);
        $result = $response->json();
        if ($result['resultCd'] == "000")
        {
            Log::debug($result);
        }
        else
        {
            Log::debug("error code triggered!");
            Log::debug($result["resultMsg"]);
        }
    }

    public static function interfaceStockItemNew($stockItem)
    {
        Log::debug('*****Start intefacing stock item newwwwwww****');
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);

        $settings = Settings::find(1);
        $baseUrl = $settings->baseUrl;
        $zra_url = 'stock/saveStockItems';
        Log::debug(json_encode($stockItem));

        $stockItemList = $stockItem["itemList"];
        $response = Http::post($baseUrl . $zra_url, $stockItem);
        $result = $response->json();
        ConfigController::LogDeviceData("/stock/saveStockItems", $stockItem, $result);

        Log::debug($result);
        Log::debug('*****Finished intefacing stock item****');

        if (isset($result['resultCd']) and $result['resultCd'] == "000")
        {
            foreach($stockItemList as $itemList)
            {
                $itemID = $itemList['itemCd'];
                $quantity = $itemList['quantityOnhand'];
                StockItemController::initialiseSingleStockBalance($itemID, $quantity);
            }
        }
        else
        {
            Log::debug("error code triggered!");
            if (isset($result['resultMsg'])) Log::debug($result["resultMsg"]);
            Log::debug($stockItem);
        }

    }

    public static function initialiseSingleStockBalance($itemID, $quantity)
    {
        Log::info('****Interfacing stock master new*****');
        $settings = Settings::find(1);
        $baseUrl = $settings->baseUrl;

        $request_data = [
            "tpin" => $settings->tinNumber,
            "bhfId" => $settings->branchCode,
            "regrId" => "Admin",
            "regrNm" => "Admin",
            "modrNm" => "Admin",
            "modrId" => "Admin"
        ];

        $request_data_list =
            [
                "itemCd" => $itemID,
                "rsdQty" => $quantity
            ];
        $request_data["stockItemList"][]= $request_data_list;

        $zra_url = "/stockMaster/saveStockMaster";
        $response = Http::post($baseUrl . $zra_url, $request_data);
        $result = $response->json();
        if ($result['resultCd'] == "000")
        {
            Log::debug($result);
        }
        else
        {
            Log::debug("error code triggered!");
            Log::debug($result["resultMsg"]);
        }
    }
}
