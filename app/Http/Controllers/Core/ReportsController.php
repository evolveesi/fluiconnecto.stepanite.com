<?php

namespace App\Http\Controllers\Core;

use App\AppSettings;
use App\DeviceResponses;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\StockItemController;
use App\Http\Controllers\Controller;
use App\ImportRequest;
use App\Item;
use App\ItemClassList;
use App\ItemImport;
use App\OfflineTransaction;
use App\Purchase;
use App\PurchaseCreditDebit;
use App\PurchaseRequest;
use App\PurchasesImport;
use App\SalesInvoice;
use App\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        $purchases = PurchasesImport::where("status","Pending")->get();
        $items = Item::where('status','Interfaced')->get();
            return view('pages.core.purchases',[
                'purchases'=>$purchases,
                'items'=>$items,
            ]);
    }
    public function cPurchasesReport(){
        $purchases = PurchasesImport::where("status","Interfaced")->get();
        $items = Item::where('status','Interfaced')->get();
        return view('pages.core.purchases',[
            'purchases'=>$purchases,
            'items'=>$items,

        ]);
    }
    public function importsReport(){
        $unprocessed_imports = ItemImport::where("status","Pending")->orderBy('updated_at','desc')->get();
        $items = Item::where('status','Interfaced')->get();
        return view('pages.core.imports-preview',[
            'unprocessed_imports'=>$unprocessed_imports,
            'items'=>$items,
        ]);
    }
    public function cimportsReport(){
        $unprocessed_imports = ItemImport::where("status","Interfaced")->orderBy('updated_at','desc')->get();
        $items = Item::where('status','Interfaced')->get();
        return view('pages.core.imports-completed',[
            'unprocessed_imports'=>$unprocessed_imports,
            'items'=>$items,
        ]);
    }
    public function crdr(){
        $purchases = PurchaseCreditDebit::all();
        return view('pages.core.crdr-purchases',[
            'purchases'=>$purchases
        ]);
    }
    public static function LogDeviceData($zra_url,$request_data,$result){
        $device = new DeviceResponses();
        $device->url = $zra_url;
        $device->zra_request = json_encode($request_data,true);
        $device->zra_response = json_encode($result,true);
        $device->save();
    }
    public function getImports(){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $zra_base_url = $settings->baseUrl;

        $last_req_lookup = ImportRequest::latest()->first();
        if ( !is_null($last_req_lookup) )
        {
            $lastReqDt = $last_req_lookup->resultDt;
        }
        else
        {
            $lastReqDt = "20231215000000";
        }
        $zra_purchase_array = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastReqDt,
        ];

        $response = Http::post($zra_base_url."/imports/selectImportItems", $zra_purchase_array);
        $result = $response->json();
        ConfigController::LogDeviceData("/imports/selectImportItems",$zra_purchase_array,$result);
        Log::info($result);
         if ($result['resultCd']!="000"){
             $unprocessed_imports = ItemImport::where("status","Pending")->get();
             $items = Item::where('status','Interfaced')->get();
             return view('pages.core.imports-preview',[
                 'unprocessed_imports'=>$unprocessed_imports,
                 'items'=>$items,
             ]);
         }

        #save to database
        foreach ($result['data']['itemList'] as $item){
            $import_item = new ItemImport();
            $import_item->taskCd = $item['taskCd'];
            $import_item->dclDe = $item['dclDe'];
            $import_item->itemSeq = $item['itemSeq'];
            $import_item->dclNo = $item['dclNo'];
            $import_item->hsCd = $item['hsCd'];
            $import_item->itemNm = $item['itemNm'];
            $import_item->imptItemsttsCd = $item['imptItemsttsCd'];
            $import_item->orgnNatCd = $item['orgnNatCd'];
            $import_item->exptNatCd = $item['exptNatCd'];
            $import_item->pkg = $item['pkg'];
            $import_item->pkgUnitCd = $item['pkgUnitCd'];
            $import_item->qty = $item['qty'];
            $import_item->qtyUnitCd = $item['qtyUnitCd'];
            $import_item->netWt = $item['netWt'];
            $import_item->totWt = $item['totWt'];
            $import_item->spplrNm = $item['spplrNm'];
            $import_item->agntNm = $item['agntNm'];
            $import_item->invcFcurAmt = $item['invcFcurAmt'];
            $import_item->invcFcurCd = $item['invcFcurCd'];
            $import_item->invcFcurExcrt = $item['invcFcurExcrt'];
            $import_item->save();
        }

        // save resultDt
        $last_req_ = new ImportRequest();
        $last_req_->resultDt = $result['resultDt'];
        // $last_req_->imports_response = $result;
        $last_req_->save();

        $unprocessed_imports = ItemImport::where("status","Pending")->get();
        $items = Item::where('status','Interfaced')->get();
        return view('pages.core.imports-preview',[
            'unprocessed_imports'=>$unprocessed_imports,
            'items'=>$items,
        ]);

    }
    public function processImports(Request $request)
    {
        $id = $request->id;
        $sage_item_code = $request->sage_item_code;
        $payment_type = $request->payment_type;
        $import_status = $request->import_status;
        $spplrNm = $request->spplrNm;
        $location = $request->location;
        $terms_code = $request->terms_code;
        $qty = $request->qty;
        $vendor_account_set = $request->vendor_account_set;

        //get sage item
        $item_name = $this->getItemName($sage_item_code);

        $item = ItemImport::find($id);
        $item->sage_item_code = $sage_item_code;
        $item->payment_type = $payment_type;
        $item->spplrNm = $spplrNm;
        $item->location = $location;
        $item->qty = $qty;
        $item->terms_code = $terms_code;
        $item->vendor_account_set = $vendor_account_set;
        $item->itemNm = $item_name;
        $item->update();

        $taskCd = $item->taskCd;

        $zra_to_sage_purchase_array = [
            "Vendor" => (string)$spplrNm,
            "VendorAccountSet" => (string)$vendor_account_set,
            "TermsCode" => $terms_code,
            "MultiplePurchaseOrders" => false,
            "PurchaseOrders" => 1,
            "Currency" => $item->invcFcurCd,
            "TaxGroup" => "D",
            "TaxAuthority1" => "ZRA",
            "TaxAuthority2" => "",
            "TaxAuthority3" => "",
            "TaxAuthority4" => "",
            "TaxAuthority5" => "",
            "TaxClass1" => 6,
            "TaxClass2" => 0,
            "TaxClass3" => 0,
            "TaxClass4" => 0,
            "TaxClass5" => 0,
            "TaxBase1" => 0,
            "TaxBase2" => 0,
            "TaxBase3" => 0,
            "TaxBase4" => 0,
            "TaxBase5" => 0,
            "NumberOfOptionalFields" => 1,
        ];

        $zra_to_sage_purchase_array["ReceiptOptionalField2s"][] = [
            "ReceiptSequenceKey" => 0,
            "OptionalField" => "PAYMENTTYPE",
            "Value" => $payment_type,
            "ReceiptOptionalField2Type" => "Text",
            "Length" => 0,
            "Decimals" => 0,
            "AllowBlank" => true,
            "Validate" => true,
            "UpdateOperation" => "Unspecified"
        ];

        $zra_to_sage_purchase_array["ReceiptPurchaseOrders"][] = [
            "PurchaseOrderSequenceKey" => (int)$item->hsCd,
            "PurchaseOrderNumber" =>(string)$item->dclNo,
            "StoredInDatabaseTable" => true
        ];

        $item_optional_fields[] =  [
            "ReceiptSequenceKey" => 0,
            "LineNumber" => 0,
            "OptionalField" => "PACKAGINGUNT",
            "Value" => $item->pkgUnitCd,
            "ReceiptDetailOptionalField2Type" => "Text",
            "Length" => 0,
            "Decimals" => 0,
            "AllowBlank" => true,
            "Validate" => true,
            "UpdateOperation" => "Unspecified"
        ];

        $zra_to_sage_purchase_array["ReceiptLine2s"][] = [
            "ItemNumber" => $sage_item_code,
            "Location" => $location,
            "QuantityReceived" => (int)$item->qty,
            "UnitOfMeasure" => $item->qtyUnitCd,
            "PurchaseOrderSequenceKey" => (int)$item->hsCd,
            "PurchaseOrderLineSequence" => intval($item->itemSeq),
            "ItemExists" => true,
            "ItemDescription" => $item->itemNm,
            "UnitCost" => (float)$item->invcFcurAmt,
            "PurchaseOrderNumber" =>  $item->dclNo,
            "StockItem" => true,
            "NumberOfOptionalFields" => 1,
            "ReceiptDetailOptionalField2s" => $item_optional_fields,
        ];

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->post("$sage_base_url/PO/POReceipts", $zra_to_sage_purchase_array);
        $result = $response->json();

        Log::info(json_encode($zra_to_sage_purchase_array));
        Log::debug($result);

        if(isset($result['error'])){
            return redirect()->back()->with('error',$result['error']['message']['value']);
        }
        else
        {
            $item->status="interfaced";
            $item->update();

            Log::alert("*****Start interfacing accept/reject import****");

            $settings = Settings::find(1);
            $tinNumber = $settings->tinNumber;
            $branchCode= $settings->branchCode;
            $zra_base_url = $settings->baseUrl;

            $zra_import_accepted_array["tpin"] = $tinNumber;
            $zra_import_accepted_array["bhfId"] = $branchCode;
            $zra_import_accepted_array["taskCd"] = $taskCd;
            $zra_import_accepted_array["dclDe"] = $item->dclDe;

            $itemClsCd = InvoiceController::getItemUNCode($sage_item_code);
            $zra_import_accepted_array["importItemList"][] = [
                "itemSeq"=> (int)$item->itemSeq,
                "hsCd"=> (int)$item->hsCd,
                "itemClsCd"=> $itemClsCd,
                "itemCd"=> $sage_item_code,
                "imptItemSttsCd"=> $import_status,
                // "remark"=> "Import Accepted",
                "modrNm"=> "Admin",
                "modrId"=> "Admin"
            ];

            Log::info(json_encode($zra_import_accepted_array));

            $response = Http::post($zra_base_url."/imports/updateImportItems", $zra_import_accepted_array);
            $result = $response->json();

            ConfigController::LogDeviceData("/imports/updateImportItems",$zra_import_accepted_array,$result);
            Log::info($result);

            Log::alert("*****Finished interfacing accept/reject import****");
        }

        return redirect()->back()->with('status','Successfully Imported Purchase!');
    }
    public static function getItemName($itemCode)
    {
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICItems('$itemCode')");
        $result = $response->json();

        // Log::alert($result);
        return $result['Description'];    
    }
    public function getStandardCodes()
    {

        #get request token details
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $lastRequestDate = $settings->lastRequestDate;
        $baseUrl = $settings->baseUrl;

        #building request data
        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastRequestDate
        ];

        $zra_select_codes_url = $baseUrl.'/code/selectCodes';
        #making application request
        $response = Http::post($zra_select_codes_url, $request_data);
        $result = $response->json();
        ConfigController::LogDeviceData("/code/selectCodes",$request_data,$result);

        Log::alert('select codes url========'.$zra_select_codes_url);
        Log::alert('select codes response========');
        Log::alert($result);

        #saving new device settings in database
        $standard_codes_data = [];
        if (isset($result['data']['clsList'])) 
        {
            $standard_codes_data = $result['data']['clsList'];
            #truncating old data to make space for new data
            Schema::disableForeignKeyConstraints();
            AppSettings::truncate();
            Schema::enableForeignKeyConstraints();
        } 
        else 
        {
            return redirect()->back()->with("error","Result array empty. Check if ZRA is up");
        }
        
        $count = 0;
        foreach($standard_codes_data as $standard_code_data){
            Log::debug($standard_codes_data[$count]['userDfnNm1']);
            $standard_code_details = $standard_codes_data[$count]['dtlList'];
            foreach($standard_code_details as $standard_code_detail){
                $standard_code = new AppSettings();
                $standard_code->standard_code_class = $standard_code_data['cdCls'];
                $standard_code->standard_code_class_name = $standard_code_data['cdClsNm'];
                $standard_code->standard_code_class_value = $standard_code_data['userDfnNm1'];
                $standard_code->standard_code = $standard_code_detail['cd'];
                $standard_code->standard_code_name = $standard_code_detail['cdNm'];
                $standard_code->standard_code_value = $standard_code_detail['userDfnCd1'];
                $standard_code->save();
            }
            $count += 1;
            $standard_code->save();
        }

        if ($result['resultCd']=="000"){
            return redirect()->back()->with("status",$result['resultMsg']);
        }
        return redirect()->back()->with("error",$result['resultMsg']);
    }
    public function standard_codes()
    {
        $standard_codes = AppSettings::all();
        return view('pages.core.standard-code-list',['standard_codes'=>$standard_codes]);
    }
    public function deviceIntit(Request  $request)
    {
        $tinNumber = $request->tinNumber;
        $branchCode = $request->branchCode;
        $dvcSrlNo = $request->dvcSrlNo;

        $settings = Settings::find(1);
        $settings->dvcSrlNo = $dvcSrlNo;
        $settings->tinNumber = $tinNumber;
        $settings->branchCode = $branchCode;
        $settings->save();

        $baseUrl = $settings->baseUrl;;
        $zra_url = '/initializer/selectInitInfo';
        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "dvcSrlNo" => $dvcSrlNo,
        ];
        Log::debug($request_data);
        $response = Http::post($baseUrl.$zra_url, $request_data);
        $result = $response->json();
        self::LogDeviceData($zra_url,$request_data,$result);
        if ($result['resultCd']=="000"){
            return redirect()->back()->with("status",$result['resultMsg']);
        }
        return redirect()->back()->with("error",$result['resultMsg']);
    }
    public function classificationCodes(){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $lastRequestDate = $settings->lastRequestDate;

        $baseUrl = $settings->baseUrl;;
        $zra_url = '/itemClass/selectItemsClass';
        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastRequestDate,
        ];
        Log::debug($request_data);
        $response = Http::post($baseUrl.$zra_url, $request_data);
        $result = $response->json();
        self::LogDeviceData($zra_url,$request_data,$result);
        if ($result['resultCd']=="000"){
            Schema::disableForeignKeyConstraints();
            ItemClassList::truncate();
            Schema::enableForeignKeyConstraints();
            $itemLists = $result['data']['itemClsList'];
            foreach ($itemLists as $itemList){
                $items_class_list = new ItemClassList();
                $items_class_list->itemClsCd = $itemList['itemClsCd'];
                $items_class_list->itemClsNm = $itemList['itemClsNm'];
                $items_class_list->itemClsLvl = $itemList['itemClsLvl'];
                $items_class_list->taxTyCd = $itemList['taxTyCd'];
                $items_class_list->mjrTgYn = $itemList['mjrTgYn'];
                $items_class_list->useYn = $itemList['useYn'];
                $items_class_list->save();
            }
            return redirect()->back()->with("status",$result['resultMsg']);
        }
        return redirect()->back()->with("error",$result['resultMsg']);
    }
    public function deviceRequestResponses(){
        // $device_responses = DeviceResponses::where('url','/trnsSales/saveSales')->orderBy('created_at','desc')->take(1000)->get();
        $device_responses = DeviceResponses::latest()->take(1000)->get();
        return view('pages.core.device',[
            'device_responses'=>$device_responses
        ]);
    }
    public function classLists(){
        $classLists = ItemClassList::all();
        return view('pages.core.class-lists',[
            'classLists'=>$classLists
        ]);
    }
    public function getPurchases()
    {
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $zra_base_url = $settings->baseUrl;
        $items = Item::where('status','Interfaced')->get();

        $last_req_lookup = PurchaseRequest::latest()->first();
        if ( !is_null($last_req_lookup) )
        {
            $lastReqDt = $last_req_lookup->resultDt;
        }
        else{
            $lastReqDt = "20231215000000";
        }


        $zra_purchase_array = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastReqDt,
        ];

        $response = Http::post($zra_base_url."/trnsPurchase/selectTrnsPurchaseSales", $zra_purchase_array);
        $result = $response->json();
        ConfigController::LogDeviceData("/trnsPurchase/selectTrnsPurchaseSales",$zra_purchase_array,$result);
        Log::info($result);

        if (!empty($result['resultCd']) and $result['resultCd']=="000")
        {
            foreach($result['data']['saleList'] as $vendor)
            {
                $spplrTpin = $vendor['spplrTpin'];
                $spplrNm = $vendor['spplrNm'];
                $spplrBhfId = $vendor['spplrBhfId'];
                $spplrInvcNo = $vendor['spplrInvcNo'];
                $rcptTyCd = $vendor['rcptTyCd'];
                $pmtTyCd = $vendor['pmtTyCd'];
                $cfmDt = $vendor['cfmDt'];
                $salesDt = $vendor['salesDt'];
                $stockRlsDt = $vendor['stockRlsDt'];
                $totItemCnt = $vendor['totItemCnt'];
                $totTaxblAmt = $vendor['totTaxblAmt'];
                $totTaxAmt = $vendor['totTaxAmt'];
                $totAmt = $vendor['totAmt'];
                $remark = $vendor['remark'];

                foreach($vendor['itemList'] as $item)
                {
                    $purchase_import = new PurchasesImport();
                    $purchase_import->spplrTpin	= $spplrTpin;
                    $purchase_import->spplrNm	= $spplrNm;
                    $purchase_import->spplrBhfId	= $spplrBhfId;
                    $purchase_import->spplrInvcNo	= $spplrInvcNo;
                    $purchase_import->rcptTyCd	= $rcptTyCd;
                    $purchase_import->pmtTyCd	= $pmtTyCd;
                    $purchase_import->cfmDt		= $cfmDt;
                    $purchase_import->salesDt	= $salesDt;
                    $purchase_import->stockRlsDt	= $stockRlsDt;
                    $purchase_import->totItemCnt	= $totItemCnt;
                    $purchase_import->totTaxblAmt	= $totTaxblAmt;
                    $purchase_import->totTaxAmt		= $totTaxAmt;
                    $purchase_import->totAmt		= $totAmt;
                    $purchase_import->remark		= $remark;

                    $purchase_import->itemSeq = $item['itemSeq'];
                    $purchase_import->itemCd	 = $item['itemCd'];
                    $purchase_import->itemClsCd	 = $item['itemClsCd'];
                    $purchase_import->itemNm	 = $item['itemNm'];
                    $purchase_import->bcd	 = $item['bcd'];
                    $purchase_import->pkgUnitCd	 = $item['pkgUnitCd'];
                    $purchase_import->pkg	 = $item['pkg'];
                    $purchase_import->qtyUnitCd	 = $item['qtyUnitCd'];
                    $purchase_import->qty	 = $item['qty'];
                    $purchase_import->prc	 = $item['prc'];
                    $purchase_import->splyAmt = $item['splyAmt'];
                    $purchase_import->dcRt	 = $item['dcRt'];
                    $purchase_import->dcAmt	 = $item['dcAmt'];
                    $purchase_import->vatCatCd	 = $item['vatCatCd'];
                    $purchase_import->iplCatCd	 = $item['iplCatCd'];
                    $purchase_import->tlCatCd	 = $item['tlCatCd'];
                    $purchase_import->exciseTxCatCd	 = $item['exciseTxCatCd'];
                    $purchase_import->vatTaxblAmt	 = $item['vatTaxblAmt'];
                    $purchase_import->exciseTaxblAmt	 = $item['exciseTaxblAmt'];
                    $purchase_import->iplTaxblAmt	 = $item['iplTaxblAmt'];
                    $purchase_import->tlTaxblAmt	 = $item['tlTaxblAmt'];
                    $purchase_import->taxblAmt	 = $item['taxblAmt'];
                    $purchase_import->vatAmt	 = $item['vatAmt'];
                    $purchase_import->iplAmt	 = $item['iplAmt'];
                    $purchase_import->tlAmt	 = $item['tlAmt'];
                    $purchase_import->exciseTxAmt		 = $item['exciseTxAmt'];

                    $purchase_import->save();
                }
            }

            $purchases = PurchasesImport::where("status","Pending")->get();
            return view('pages.core.purchases',[
                'purchases'=>$purchases,
                "items"=>$items
            ]);
        }
        else
        {
            return back()->with('error', $result['resultMsg']);
        }
        // save resultDt
        $last_req_ = new PurchaseRequest();
        $last_req_->resultDt = $result['resultDt'];
        $last_req_->zra_response = $result;
        $last_req_->save();
        foreach($result['data']['saleList'] as $salesListItem) {
        }


        }
    public function processPurchases(Request $request)
    {    
        $vatTaxblAmt = $request->vatTaxblAmt;
        $iplTaxblAmt = $request->iplTaxblAmt;
        $exciseTaxblAmt = $request->exciseTaxblAmt;
        $tlTaxblAmt = $request->tlTaxblAmt;
        $taxblAmt = $request->taxblAmt;
        $exciseTxAmt = $request->exciseTxAmt;
        $vatAmt = $request->vatAmt;
        $iplAmt = $request->iplAmt;
        $tlAmt= $request->tlAmt;
        $totAmt= $request->totAmt;
        $itemCd= $request->itemCd;
        $location= $request->location;
        $spplrTpin = $request->spplrTpin;
        $spplrNm = $request->spplrNm;
        $sageItemCd = $request->sageItemCd;
        $sageVendorCd = $request->sageVendorCd;
        $currency = $request->currency;
        $pmtTyCd = $request->pmtTyCd;
        $cfmDt = $request->cfmDt;
        $rcptTyCd = $request->rcptTyCd;
        $itemCd = $request->itemCd;
        $salesDt = $request->salesDt;
        $itemClsCd = $request->itemClsCd;
        $bcd = $request->bcd;
        $pkg = $request->pkg;
        $qty= $request->qty;
        $prc = $request->prc;
        $pkgUnitCd = $request->pkgUnitCd;
        $qtyUnitCd= $request->qtyUnitCd;
        $splyAmt = $request->splyAmt;
        $dcRt = $request->dcRt;
        $dcAmt = $request->dcAmt;
        $vatCatCd = $request->vatCatCd;
        $iplCatCd = $request->iplCatCd;
        $tlCatCd = $request->tlCatCd;
        $exciseTxCatCd = $request->exciseTxCatCd;
        
        $purchase_import = PurchasesImport::find($request->id);
        $purchase_import->currency = $currency;
        $purchase_import->cfmDt = $cfmDt;
        $purchase_import->spplrNm = $spplrNm;
        $purchase_import->rcptTyCd = $rcptTyCd;
        $purchase_import->spplrTpin = $spplrTpin;
        $purchase_import->pmtTyCd = $pmtTyCd;
        $purchase_import->sageVendorCd = $sageVendorCd;
        $purchase_import->sageItemCd = $sageItemCd;
        $purchase_import->qty = $qty;
        $purchase_import->vendorAccountSet =  $request->vendorAccountSet;
        $purchase_import->PurchaseOrderNumber =  $request->PurchaseOrderNumber;
        $purchase_import->termsCode =  $request->termsCode;
        $purchase_import->exciseTxCatCd =  $exciseTxCatCd;
        $purchase_import->tlCatCd =  $tlCatCd;
        $purchase_import->iplCatCd =  $iplCatCd;
        $purchase_import->vatCatCd =  $vatCatCd;
        $purchase_import->itemCd = $itemCd;
        $purchase_import->sageItemCd = $sageItemCd;
        $purchase_import->sageVendorCd = $sageVendorCd;
        $purchase_import->location = $location;
        $uniq = rand(1111111111,9999999999);;
        $uniq2 = rand(1111111111,9999999999);;
        $purchase_import->ItemSeqKey = $uniq;
        $purchase_import->PurchaseOrderLineSequence = $uniq2;
        $itemNm = $this->getItemName($sageItemCd);
        $purchase_import->itemNm = $itemNm;
        $purchase_import->update();

        $zra_to_sage_purchase_array = [
                "Vendor" => $sageVendorCd,
                "VendorAccountSet" => $request->vendorAccountSet,
                "TermsCode" => "30DAYS",
                "MultiplePurchaseOrders" => true,
                "PurchaseOrders" => 1,
                "Currency" => $currency,//this is a local purchase
                "TaxGroup" => "D",//get in ite,
                "TaxAuthority1" => "ZRA",
                "TaxAuthority2" => "",
                "TaxAuthority3" => "",
                "TaxAuthority4" => "",
                "TaxAuthority5" => "",
                "TaxClass1" => 6,//get in item
                "TaxClass2" => 0,
                "TaxClass3" => 0,
                "TaxClass4" => 0,
                "TaxClass5" => 0,
                "TaxBase1" => 0,//get in item
                "TaxBase2" => 0,
                "TaxBase3" => 0,
                "TaxBase4" => 0,
                "TaxBase5" => 0,
                "NumberOfOptionalFields" => 1,
            ];


            $zra_to_sage_purchase_array["ReceiptOptionalField2s"][] = [
                "ReceiptSequenceKey" => 0,
                "OptionalField" => "PAYMENTTYPE",
                "Value" => $pmtTyCd,
                "ReceiptOptionalField2Type" => "Text",
                "Length" => 0,
                "Decimals" => 0,
                "AllowBlank" => true,
                "Validate" => true,
                "UpdateOperation" => "Unspecified"
            ];


            $zra_to_sage_purchase_array["ReceiptPurchaseOrders"][] = [
                "PurchaseOrderSequenceKey" => (int)$uniq,
                "PurchaseOrderNumber" => $request->PurchaseOrderNumber,
                "StoredInDatabaseTable" => true
            ];

            $item_optional_fields[] =  [
                "ReceiptSequenceKey" => 0,
                "LineNumber" => 0,
                "OptionalField" => "PACKAGINGUNT",
                "Value" => $pkgUnitCd,
                "ReceiptDetailOptionalField2Type" => "Text",
                "Length" => 0,
                "Decimals" => 0,
                "AllowBlank" => true,
                "Validate" => true,
                "UpdateOperation" => "Unspecified"
            ];

            $zra_to_sage_purchase_array["ReceiptLine2s"][] = [
                "ItemNumber" => $sageItemCd,
                "Location" => $location,
                "QuantityReceived" => (int)$qty,
                "UnitOfMeasure" => $qtyUnitCd,
                "PurchaseOrderSequenceKey" => (int)$uniq,
                "PurchaseOrderLineSequence" => (int)$uniq2,
                "ItemExists" => true,
                "ItemDescription" => $itemNm,
                "UnitCost" => floatval($prc),
                "PurchaseOrderNumber" => $request->PurchaseOrderNumber,
                "StockItem" => true,
                "NumberOfOptionalFields" => 1,
                "ReceiptDetailOptionalField2s" => $item_optional_fields
            ];

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');
        Log::debug(json_encode($zra_to_sage_purchase_array));
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->post("$sage_base_url/PO/POReceipts", $zra_to_sage_purchase_array);
        $result = $response->json();

        Log::info($zra_to_sage_purchase_array);
        Log::debug($result);

        if(isset($result['error'])){
            return redirect()->back()->with('error',$result['error']['message']['value']);
        }
        $purchase_import->status="Interfaced";
        $purchase_import->update();
        return redirect()->back()->with('status','Successfully Imported Purchase!');
    }

    public function processOffline() 
    {
        $settings = Settings::find(1);
        $zra_base_url = $settings->baseUrl;

        //TODO Check if ZRA is up with Device initialisation!!!!
        if(!$this->checkZRAOnlineStatus())
        {
            return redirect()->back()->with('error','Connection to ZRA failed. Try later and contact support.');
        }

        $pending_transactions = OfflineTransaction::where('status','pending')->get();
        foreach($pending_transactions as $transaction)
        {
            $payload = json_decode($transaction->payload, true);
            if($transaction->endpoint == 'stock/saveStockItems')
            {
                StockItemController::interfaceStockItem($payload);
            }
            else
            {
                $response = Http::post($zra_base_url.$transaction->endpoint, $payload);
                $result = $response->json();
                Log::info($result);
                Log::info(json_encode($payload));
                ConfigController::LogDeviceData($transaction->endpoint,$payload,$result);
            }

            $update = OfflineTransaction::find($transaction->id);
            $update->status = 'interfaced';
            $update->save();
        }

        return redirect()->back()->with('status','Offline transaction processed!');
    }
    public function checkZRAOnlineStatus()
    {
        $settings = Settings::find(1);
        $baseUrl = $settings->baseUrl;;
        $zra_url = '/initializer/selectInitInfo';
        $request_data = [
            "tpin" => $settings->tinNumber,
            "bhfId" => $settings->branchCode,
            "dvcSrlNo" => $settings->dvcSrlNo,
        ];
        Log::debug($request_data);
        $response = Http::post($baseUrl.$zra_url, $request_data);
        if($response->failed()) return false;
        else return true;
    }

}
