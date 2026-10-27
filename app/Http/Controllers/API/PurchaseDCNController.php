<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\ConfigController;
use App\Http\Controllers\API\ItemController;
use App\OfflineTransaction;
use App\PurchaseCreditDebit;
use App\PurchaseCreditDebitNote;
use App\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateSalesInvoiceStocks;

class PurchaseDCNController extends Controller
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
    public function sendPurchaseDCN(Request $request)
    {
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $zra_base_url = $settings->baseUrl;
        $is_tax_exclusive = $settings->is_tax_exclusive;

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');
        $purchaseCDNID = $request->query("purchaseCDNID");
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/PO/POCreditDebitNotes($purchaseCDNID)");
        $result = $response->json();

        Log::debug($result);

        //create a new purchase in api DB
        $sagePurNum = $result['ReturnNumber'];
        $purchase  = PurchaseCreditDebit::where('invoice_id',$purchaseCDNID)->get();
        if(!count($purchase)){
            $purchase  = new PurchaseCreditDebit();
            $purchase->invoice_id = $purchaseCDNID;
            $purchase->sage_purchase_number = $sagePurNum;
            $purchase->save();
        }
        else{
            $purchase = $purchase[0];
            $purchase->sage_purchase_number = $sagePurNum;
        }

        $pmtTyCd = '';
        foreach($result['CreditDebitNoteOptionalFields'] as $optField)
        {
            if($optField['OptionalField'] == 'PAYMENTTYPE') $pmtTyCd = $optField['Value'];
        }

        $zra_purchase_array = [
                "tpin" => $tinNumber,
                "bhfId" => $branchCode,
                "invcNo" =>  $result['CreditDebitNoteSequenceKey'], // Invoice number as generated on the VSDC
                "orgInvcNo" =>  $result['ReceiptSequenceKey'], //TODO //The invoice number being referred to.
                // "spplrTpin" => "9999999990", // TODO Supplier TPIN
                // "spplrBhfId" => "000", Supplier Name
                "spplrNm" => $result['Name'], //Supplier Name
                "spplrInvcNo" => intval($result['ReceiptSequenceKey']),//Supplier Invoice Number
                "regTyCd" => "M", // M - Manual
                "pchsTyCd" => "N", // Transaction Type N- Normal
                "rcptTyCd" => "R", // Purchase Receipt Type P - Purchase / R - Refund after Purchase
                "pmtTyCd" => $pmtTyCd, // Optional Field required Payment Type Code
                "pchsSttsCd" => "02", //Purchase Status Code Always 02 because we post
                "cfmDt" => date('Ymdhis', strtotime($result['DateCompleted'])),
                "pchsDt" => date('Ymd', strtotime($result['DateCompleted'])),
                "cnclReqDt" => "", //Cancelation request date
                "cnclDt" => "", //Cancel date
                "totItemCnt" =>  count($result['CreditDebitNoteLines']),
                "remark" => $result['VendorAccountSetDescription'],
                "regrNm" => "ADMIN",
                "regrId" => "ADMIN",
                "modrNm" => "ADMIN",
                "modrId" => "ADMIN",
        ];

        $zra_stock_item_array = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            'sarNo' => $result['CreditDebitNoteSequenceKey'],
            'orgSarNo' => $result['ReceiptSequenceKey'],
            'regTyCd' => "M",
            'custTpin' => NULL,
            'custNm' => NULL,
            'custBhfId' => NULL,
            'sarTyCd' => '12',
            'ocrnDt' => date('Ymd', strtotime($result['DateCompleted'])),
            'regrId' => 'Admin',
            'regrNm' => 'Admin',
            'modrNm' => 'Admin',
            'modrId' => 'Admin',
            'remark' => 'Purchase Debit Note',
        ];

        $t_totTaxblAmt = 0;
        $t_totTaxAmt = 0;
        $t_totAmt = 0;

        $line_count = 0;
        $zra_purchase_array["itemList"] = [];
        foreach($result['CreditDebitNoteLines'] as $line)
        {
            $qtyUnitCd = $this->selectUnitOfmeasure($line['UnitOfMeasure']);


            $taxCategoryCd = explode('-', $line['TaxClass1Description']);
            $taxCategoryCd = strval(trim($taxCategoryCd[0]));

            $iplCatCd = null;
            $tlCatCd = null;
            $exciseCatCd = null;
            $vatCatCd = null;

            $taxblAmt = 0;
            $iplTaxblAmt = 0;
            $tlTaxblAmt = 0;
            $exciseTaxblAmt = 0;
            $vatTaxblAmt = 0;

            $taxAmt = 0;
            $iplAmt = 0;
            $tlAmt = 0;
            $exciseTxAmt = 0;

            $taxRate = $line['TaxRate1'];
            $item_price = $line['UnitCost'] * $line['StockingQuantityReturned'];
            $totAmt = $item_price;

            if($taxCategoryCd == 'ECM' OR $taxCategoryCd == 'EXEEG')
            {
                $exciseCatCd = $taxCategoryCd;
                $exciseTaxblAmt = $item_price * 100 / (100 + $taxRate);
                $taxAmt = $exciseTxAmt = $item_price -  $exciseTaxblAmt;
                $taxblAmt += $exciseTaxblAmt;
            }
            elseif($taxCategoryCd == 'TL')
            {
                $tlCatCd = $taxCategoryCd;
                $tlTaxblAmt = $item_price * 100 / (100 + $taxRate);
                $taxAmt = $tlAmt = $item_price -  $tlTaxblAmt;
                $taxblAmt += $tlTaxblAmt;
            }
            // elseif($taxCategoryCd == 'F')
            // {

            // }
            elseif($taxCategoryCd == 'IPL1' or $taxCategoryCd == 'IPL2')
            {
                $iplCatCd = $taxCategoryCd;
                $iplTaxblAmt = $item_price * 100 / (100 + $taxRate);
                $taxAmt = $iplAmt = $item_price -  $iplTaxblAmt;
                $taxblAmt += $iplTaxblAmt;
            }
            else
            {
                $vatCatCd = $taxCategoryCd;
                $vatTaxblAmt = $item_price * 100 / (100 + $taxRate);
                $taxAmt = $item_price -  $vatTaxblAmt;
                $taxblAmt += $vatTaxblAmt;
            }

            $t_totTaxblAmt += $taxblAmt;
            $t_totTaxAmt += $taxAmt;
            $t_totAmt += $totAmt;

            $pkgUnitCd = '';
            foreach($line['CRDRNoteLineOptionalFields'] as $optField)
            {
                if($optField['OptionalField'] == 'PACKAGINGUNT') $pkgUnitCd = $optField['Value'];
            }

            $itemClsCd = InvoiceController::getItemUNCode($line['ItemNumber']);

            $purchase_item = [
                "itemSeq" =>  ++$line_count,
                "itemCd" => $line['ItemNumber'], // This is the item code as defined on your system
                "itemClsCd" => $itemClsCd, // This is the UNSCP code assigned to the item
                "itemNm" => $line['ItemDescription'],
                "bcd" =>  null, // This is the barcode assigned to the item
                "pkgUnitCd" => $pkgUnitCd, // This is the packaging unit code assigned to the item as per standard codes
                "pkg" =>  floatval($line['StockingQuantityReturned']),
                "qtyUnitCd" => $qtyUnitCd,
                "qty" =>  $line['StockingQuantityReturned'],
                "prc" =>  $line['UnitCost'],
                "splyAmt" =>  round(($line['UnitCost'] * $line['StockingQuantityReturned']),2),
                "dcRt" =>  $line['DiscountPercentage'],
                "dcAmt" =>  $line['DiscountAmount'],
                "taxTyCd" => $taxCategoryCd,
                "iplCatCd" =>  $iplCatCd,
                "tlCatCd" =>  $tlCatCd,
                "exciseCatCd" =>  $exciseCatCd,
                "vatCatCd" => $vatCatCd,
                "taxblAmt" =>  round($taxblAmt,2),
                "iplTaxblAmt" =>  round($iplTaxblAmt,2),
                "tlTaxblAmt" =>  round($tlTaxblAmt,2),
                "exciseTaxblAmt" =>  round($exciseTaxblAmt,2),
                "taxAmt" =>  round($taxAmt,2),
                "iplAmt" =>  round($iplAmt,2),
                "tlAmt" =>  round($tlAmt,2),
                "exciseTxAmt" =>  round($exciseTxAmt,2),
                "totAmt" =>  round($totAmt,2),
            ];

            $zra_purchase_array["itemList"][] = $purchase_item;

            $stock_itemS = [
                "itemSeq"=> $line_count,
                "itemCd"=> $line['ItemNumber'],
                "itemClsCd"=> $itemClsCd,
                "itemNm"=> $line['ItemDescription'],
                "bcd"=> NULL,
                "pkg"=> floatval($line['StockingQuantityReturned']),
                "pkgUnitCd"=> $pkgUnitCd,
                "qtyUnitCd"=>$qtyUnitCd,
                "qty"=> $line['StockingQuantityReturned'],
                "itemExprDt"=> NULL,
                "totDcAmt"=> $line['DiscountAmount'],
                "iplCatCd"=> $iplCatCd,
                "tlCatCd"=> $tlCatCd,
                "exciseTxCatC"=> $exciseCatCd,
                "prc"=> round($line['UnitCost'],2),
                "splyAmt"=> round($tlTaxblAmt,2),
                "iplAmt"=> round($iplTaxblAmt,2),
                "tlAmt"=> round($tlAmt,2),
                "exciseTxAmt"=> round($exciseTaxblAmt,2),
                "taxblAmt"=> round($tlTaxblAmt,2),
                "vatCatCd"=> $vatCatCd,
                "taxAmt"=> round($taxAmt,2),
                "totAmt"=> round($totAmt,2)
            ];
            $zra_stock_item_array["itemList"][] = $stock_itemS;
        }

        $zra_purchase_array["totTaxblAmt"] = round($t_totTaxblAmt,2);
        $zra_purchase_array["totTaxAmt"] = round($t_totTaxAmt,2);
        $zra_purchase_array["totAmt"] = round($t_totAmt,2);

        $zra_stock_item_array["totTaxblAmt"] = round($t_totTaxblAmt,2);
        $zra_stock_item_array["totTaxAmt"] = round($t_totTaxAmt,2);
        $zra_stock_item_array["totAmt"] = round($t_totAmt,2);
        $zra_stock_item_array["totItemCnt"] =  count($result['CreditDebitNoteLines']);

        $response = Http::post($zra_base_url."trnsPurchase/savePurchase", $zra_purchase_array);
        $result = $response->json();
        if($response->failed()){
            Log::alert('***ZRA Call Failed to be processed offline****');
            $offline_trans = new OfflineTransaction();
            $offline_trans->endpoint = "/trnsPurchase/savePurchase";
            $offline_trans->payload = json_encode($zra_purchase_array,true);
            $offline_trans->save();

            $offline_trans = new OfflineTransaction();
            $offline_trans->endpoint = "stock/saveStockItems";
            $offline_trans->payload = json_encode($zra_stock_item_array,true);
            $offline_trans->save();

            return;
        }
        ConfigController::LogDeviceData("/trnsPurchase/savePurchase",$zra_purchase_array,$result);

        Log::info($result);
        Log::info(json_encode($zra_purchase_array));

        //update purchase tables
        $purchase->resultCd = $result['resultCd'];
        $purchase->resultMsg = $result['resultMsg'];
        if($result['resultCd'] == '000')
        {
            $purchase->status = 'Interfaced';
        }
        $purchase->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
        if(!empty($result['data']))
        {
            $purchase->rcptNo = $result['data']['rcptNo'];
            $purchase->intrlData = $result['data']['intrlData'];
            $purchase->rcptSign = $result['data']['rcptSign'];
            $purchase->vsdcRcptPbctDate = $result['data']['vsdcRcptPbctDate'];
            $purchase->sdcId = $result['data']['sdcId'];
            $purchase->mrcNo = $result['data']['mrcNo'];
            $purchase->qrCodeUrl = $result['data']['qrCodeUrl'];
        }

        $purchase->save();
        // StockItemController::interfaceStockItem($zra_stock_item_array);
        UpdateSalesInvoiceStocks::dispatch($zra_stock_item_array);
        return $result;

    }
}
