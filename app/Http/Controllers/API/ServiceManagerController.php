<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\ConfigController;
use App\SalesInvoice;
use App\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceManagerController extends Controller
{
    public function serviceManagerInvoice(Request  $request){
        Log::info($request);
        $InvoiceNumber = $request->query('InvoiceID')?$request->query('InvoiceID'):'INV000000001';
        $customerTPIN = $request->query('customerTPIN');
        $response = Http::get(env('SERVICE_MANAGER_URL').'/api/invoice-data?invoiceNumber='.$InvoiceNumber);
        $result = $response->json();
        $invoiceHeader = $result['data']['VMPTSV'][0];
        $invoiceStock = $result['data']['VMPTIT'][0];
        $invoiceServices = $result['data']['VMPTSV'][0];
        Log::info($result);

        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $zra_base_url = $settings->baseUrl;


        //create a new sales_invoice in api DB
        // $sageInvNum = $InvoiceNumber;
        // $sales_invoice  = SalesInvoice::where('invoice_id',$InvoiceNumber)->get();
        // if(!count($sales_invoice)){
        //     $sales_invoice  = new SalesInvoice();
        //     $sales_invoice->invoice_id = $InvoiceNumber;

        //     if(is_numeric($customerTPIN) and
        //         strlen((string)$customerTPIN) and
        //         (strlen((string)$customerTPIN)>0 and strlen((string)$customerTPIN)<=10))
        //     {
        //         $sales_invoice->customerTPIN = $customerTPIN;
        //     }

        //     $sales_invoice->sage_invoice_number = $sageInvNum;
        //     $sales_invoice->save();
        // }
        // else{
        //     $sales_invoice = $sales_invoice[0];
        //     $sales_invoice->sage_invoice_number = $sageInvNum;
        // }

        // $pmtTyCd = $saleCtyCd = $destnCountryCd = '';
        // foreach($result['InvoiceOptionalFields'] as $optField)
        // {
        //     if($optField['OptionalField'] == 'PAYMENTTYPE') $pmtTyCd = $optField['Value'];
        //     elseif($optField['OptionalField'] == 'SALECATEGORY') $saleCtyCd = $optField['Value'];
        //     elseif($optField['OptionalField'] == 'DESTCOUNTRY') $destnCountryCd = $optField['Value'];
        // }
        // $salesSttsCd = '02';//sage only has approved.

        // //make zra array
        // $zra_invoice_array = array(
        //     "tpin" => $tinNumber,
        //     "bhfId" => $branchCode,
        //     "orgInvcNo" => 0,
        //     "cisInvcNo" => $sageInvNum,
        //     "custNm" => $result['BillTo'],
        //     "salesTyCd" => 'N',
        //     "rcptTyCd" => 'S',
        //     "pmtTyCd" => $pmtTyCd,
        //     "salesSttsCd" => $salesSttsCd,
        //     "cfmDt" => date('Ymdhis', strtotime($result['InvoiceDate'])),
        //     "salesDt" => date('Ymd', strtotime($result['InvoiceDate'])),
        //     "stockRlsDt" => date('Ymdhis', strtotime($result['ShipmentDate'])),
        //     "cnclReqDt" => null,
        //     "cnclDt" => null,
        //     "rfdDt" => null,
        //     "rfdRsnCd" => '',
        //     "totItemCnt" => $result['NumberOfLinesInInvoice'],
        //     "taxAmtTot" => 0.0, //this is turnobver tax
        //     // "totTaxblAmt" => $result['TaxBase1'],
        //     // "totTaxAmt" => $result['TotalTaxAmount1'],
        //     "totAmt" => $result['InvoiceAmountDue'],
        //     "prchrAcptcYn" => 'N',
        //     // "remark" => $result['Description'],
        //     "regrId" => 'ADMIN',
        //     "regrNm" => 'ADMIN',
        //     "modrId" => 'ADMIN',
        //     "modrNm" => 'ADMIN',
        //     "saleCtyCd" => $saleCtyCd,
        //     "currencyTyCd" => $result['InvoiceSourceCurrency'],
        //     "exchangeRt" => round($result['InvoiceRate'],4),
        //     "destnCountryCd" => $destnCountryCd,
        //     "dbtRsnCd" => '',
        //     "invcAdjustReason" => '',
        // );

        // //stockItem
        // $InvoiceUniquifier = $result['InvoiceUniquifier'];
        // $zra_stock_item_array = [
        //     "tpin" => $tinNumber,
        //     "bhfId" => $branchCode,
        //     'sarNo' => $InvoiceUniquifier,
        //     'orgSarNo' => $InvoiceUniquifier,
        //     'regTyCd' => "M",
        //     'custTpin' => NULL,
        //     'custNm' => $result['BillTo'],
        //     'custBhfId' => NULL,
        //     'sarTyCd' => '11',
        //     'ocrnDt' => date('Ymd', strtotime($result['InvoiceDate'])),
        //     'regrId' => 'Admin',
        //     'regrNm' => 'Admin',
        //     'modrNm' => 'Admin',
        //     'modrId' => 'Admin',
        //     'remark' => 'Sales Invoice',
        // ];

        // // "custTpin" => "",
        // if(is_numeric($customerTPIN) and
        //     strlen((string)$customerTPIN) and
        //     (strlen((string)$customerTPIN)>0 and strlen((string)$customerTPIN)<=10))
        // {
        //     $tpin = $this->verifyTpin($customerTPIN);
        //     $zra_invoice_array["custTpin"] = $tpin;
        //     $zra_stock_item_array["custTpin"] = $tpin;
        // }

        // if(strlen($result['PurchaseOrderNumber']) > 1)
        // {
        //     $zra_invoice_array["lpoNumber"] = $result['PurchaseOrderNumber'];
        // }

        // $taxblAmtA = 0.0;
        // $taxblAmtB = 0.0;
        // $taxblAmtC = 0.0;
        // $taxblAmtC1 = 0.0;
        // $taxblAmtC2 = 0.0;
        // $taxblAmtC3 = 0.0;
        // $taxblAmtD =0.0;
        // $taxblAmtRvat =0.0;
        // $taxblAmtE = 0.0;
        // $taxblAmtF =  0.0;
        // $taxblAmtIpl1 =  0.0;
        // $taxblAmtIpl2 = 0.0;
        // $taxblAmtTl = 0.0;
        // $taxblAmtEcm = 0.0;
        // $taxblAmtExeeg = 0.0;
        // $taxblAmtTot = 0.0;

        // $taxRtA = 0.0;
        // $taxRtB = 0.0;
        // $taxRtC1 = 0.0;
        // $taxRtC2 = 0.0;
        // $taxRtC3 = 0.0;
        // $taxRtD = 0.0;
        // $taxRtRvat = 0.0;
        // $taxRtE = 0.0;
        // $taxRtF = 0.0;
        // $taxRtIpl1 = 0.0;
        // $taxRtIpl2 = 0.0;
        // $taxRtTl = 0.0;
        // $taxRtEcm = 0.0;
        // $taxRtExeeg = 0.0;
        // $taxRtTot = 0.0;

        // $taxAmtA = 0.0;
        // $taxAmtB = 0.0;
        // $taxAmtC = 0.0;
        // $taxAmtC1 = 0.0;
        // $taxAmtC2 = 0.0;
        // $taxAmtC3 = 0.0;
        // $taxAmtD = 0.0;
        // $taxAmtRvat = 0.0;
        // $taxAmtE = 0.0;
        // $taxAmtF = 0.0;
        // $taxAmtIpl1 = 0.0;
        // $taxAmtIpl2 = 0.0;
        // $taxAmtTl = 0.0;
        // $taxAmtEcm = 0.0;
        // $taxAmtExeeg = 0.0;

        // $TotalTaxAmount = 0.0;
        // $TotalTaxBase = 0.0;

        // $itemNumber = 0;
        // foreach($result['InvoiceDetails'] as $detail)
        // {
        //     $pkgUnitCd = '';
        //     foreach($detail['InvoiceDetailOptionalFields'] as $optField)
        //     {
        //         if($optField['OptionalField'] == 'PACKAGINGUNT') $pkgUnitCd = $optField['Value'];
        //     }

        //     $vatCatCd = $exciseTxCatCd = $tlCatCd = $iplCatCd = $fCatCd = null;
        //     $vatTaxblAmt = $exciseTaxblAmt = $tlTaxblAmt = $iplTaxblAmt = $iplAmt = $tlAmt = $vatAmt = $exciseTxAmt = $fTaxblAmt =  $fAmt = 0.0;

        //     $taxCategoryCd = explode('-', $detail['TaxClass1Description']);
        //     $taxCategoryCd = strtoupper(trim($taxCategoryCd[0]));

        //     $detail['TaxAmount1'] = round($detail['TaxAmount1'], 4);

        //     $taxCategoryCdTL = explode('-', $detail['TaxClass2Description']);
        //     $taxCategoryCdTL = trim($taxCategoryCdTL[0]);

        //     if(($taxCategoryCd == 'TL') and ($taxCategoryCdTL == ''))
        //     {
        //         $tlCatCd = $taxCategoryCd;
        //         $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

        //         $taxRtTl = $detail['TaxRate1'];

        //         // 100 * 100/(100+1.5)
        //         $taxableAmount = $item_price * 100 / (100 + $taxRtTl);
        //         $tlTaxblAmt = $taxableAmount;
        //         $taxblAmtTl += $tlTaxblAmt;

        //         // taxblAmtTl * 1.5/100
        //         $tlAmt =  $tlTaxblAmt * ($taxRtTl/100);
        //         $taxAmtTl += $tlAmt;
        //     }
        //     elseif(($taxCategoryCd == 'TL') and ($taxCategoryCdTL != ''))
        //     {
        //         $vatCatCd = $taxCategoryCdVat = $taxCategoryCdTL;

        //         $tlCatCd = $taxCategoryCd;
        //         $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

        //         // 100 * 100 / 117.5

        //         $taxRtA = $detail['TaxRate2'];
        //         $taxRtTl = $detail['TaxRate1'];

        //         $taxableAmount = $item_price * 100 / (100 + ($taxRtA+$taxRtTl));
        //         $vatTaxblAmt = $taxableAmount;
        //         $taxblAmtA += $vatTaxblAmt;


        //         $tlTaxblAmt = $taxableAmount;
        //         $taxblAmtTl += $tlTaxblAmt;

        //         // taxable amount  * (1.5/100);
        //         $tlAmt =  $tlTaxblAmt * ($taxRtTl/100);
        //         $taxAmtTl += $tlAmt;

        //         // taxable amount  * (16/100);
        //         $vatAmt =  $vatTaxblAmt * ($taxRtA/100);;
        //         $taxAmtA += $vatAmt;
        //     }
        //     elseif(($taxCategoryCdTL == 'TL') and ($taxCategoryCd != ''))
        //     {
        //         $vatCatCd = $taxCategoryCdVat = $taxCategoryCd;

        //         $tlCatCd = $taxCategoryCdTL;
        //         $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

        //         // 100 * 100 / 117.5

        //         $taxRtA = $detail['TaxRate1'];
        //         $taxRtTl = $detail['TaxRate2'];

        //         $taxableAmount = $item_price * 100 / (100 + ($taxRtA+$taxRtTl));
        //         $vatTaxblAmt = $taxableAmount;
        //         $taxblAmtA += $vatTaxblAmt;


        //         $tlTaxblAmt = $taxableAmount;
        //         $taxblAmtTl += $tlTaxblAmt;

        //         // taxable amount  * (1.5/100);
        //         $tlAmt =  $tlTaxblAmt * ($taxRtTl/100);
        //         $taxAmtTl += $tlAmt;

        //         // taxable amount  * (16/100);
        //         $vatAmt =  $vatTaxblAmt * ($taxRtA/100);;
        //         $taxAmtA += $vatAmt;
        //     }
        //     elseif($taxCategoryCd == 'ECM' OR $taxCategoryCd == 'EXEEG')
        //     {
        //         $taxCategoryCdVat = explode('-', $detail['TaxClass2Description']);
        //         $taxCategoryCdVat = trim($taxCategoryCdVat[0]);

        //         $exciseTxCatCd = $taxCategoryCd;
        //         $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];
        //         // $exciseTaxblAmt =  $detail['TaxBase1'];
        //         // $exciseTxAmt = $detail['TaxAmount1'];
        //         $vatCatCd = $taxCategoryCdVat;
        //         if($taxCategoryCd == 'ECM')
        //         {
        //             $taxRtEcm = $detail['TaxRate1'];
        //             // TaxBase2 * 100/105
        //             $taxRtA = $detail['TaxRate2'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
        //             $taxblAmtA += $vatTaxblAmt;
        //             $exciseTaxblAmt = $vatTaxblAmt * 100 / (100 + $taxRtEcm);
        //             $taxblAmtEcm += $exciseTaxblAmt;
        //             // TaxBase2 - TaxBase2 * 100/105
        //             $exciseTxAmt =  $vatTaxblAmt -  $taxblAmtEcm;
        //             $taxAmtEcm += $exciseTxAmt;

        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtA += $vatAmt;
        //         }
        //         else
        //         {
        //             $taxRtExeeg = $detail['TaxRate1'];
        //             // TaxBase2 * 100/105
        //             $taxRtA = $detail['TaxRate2'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
        //             $taxblAmtA += $vatTaxblAmt;
        //             $exciseTaxblAmt = $vatTaxblAmt * 100 / (100 + $taxRtExeeg);
        //             $taxblAmtExeeg += $exciseTaxblAmt;
        //             // TaxBase2 - TaxBase2 * 100/105
        //             $exciseTxAmt =  $vatTaxblAmt -  $taxblAmtExeeg;
        //             $taxAmtExeeg  += $exciseTxAmt;

        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtA += $vatAmt;
        //         }

        //     }
        //     elseif($taxCategoryCd == 'F')
        //     {
        //         $fCatCd = $taxCategoryCd;
        //         $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

        //         $taxRtF = $detail['TaxRate1'];
        //         $fTaxblAmt = $item_price * 100 / (100 + $taxRtF);
        //         $taxblAmtF += $fTaxblAmt;
        //         $fAmt =  $item_price -  $fTaxblAmt;
        //         $taxAmtF += $fAmt;
        //     }
        //     elseif($taxCategoryCd == 'IPL1' or $taxCategoryCd == 'IPL2')
        //     {

        //         $iplCatCd = $taxCategoryCd;
        //         $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

        //         if($taxCategoryCd == 'IPL1')
        //         {
        //             $taxRtIpl1 = $detail['TaxRate1'];
        //             $iplTaxblAmt = $item_price * 100 / (100 + $taxRtIpl1);
        //             $taxblAmtIpl1 += $iplTaxblAmt;
        //             $iplAmt = $item_price -  $iplTaxblAmt;
        //             $taxAmtIpl1 += $iplAmt;
        //         }
        //         else
        //         {
        //             $taxRtIpl2 =   $detail['TaxRate1'];
        //             $iplTaxblAmt = $item_price * 100 / (100 + $taxRtIpl2);
        //             $taxblAmtIpl2 += $iplTaxblAmt;
        //             $iplAmt = $item_price -  $iplTaxblAmt;
        //             $taxAmtIpl2 += $iplAmt;
        //         }
        //     }
        //     else
        //     {
        //         $vatCatCd = $taxCategoryCd;
        //         $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

        //         if($taxCategoryCd == 'A')
        //         {
        //             $taxRtA = $detail['TaxRate1'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
        //             $taxblAmtA += $vatTaxblAmt;
        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtA += $vatAmt;
        //         }
        //         elseif($taxCategoryCd == 'B')
        //         {
        //             // ZRA hasn't set up MTV yet
        //             // $currenyCode = $result['TaxReportingTRCurrency'];
        //             // $itemCode = $detail['Item'];
        //             // $PriceList = $detail['PriceList'];
        //             // $mtv_response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/IC/ICItemPricing(CurrencyCode='$currenyCode',UnformattedItemNumber='$itemCode',PriceListCode='$PriceList')");
        //             // $mtv_result = $mtv_response->json();
        //             // $item_price = $mtv_result['SalePrice'] * $detail['QuantityShipped'];

        //             $taxRtB = $detail['TaxRate1'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtB);
        //             $taxblAmtB += $vatTaxblAmt;
        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtB += $vatAmt;
        //         }
        //         elseif($taxCategoryCd == 'C1')
        //         {
        //             $taxRtC1 =$detail['TaxRate1'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC1);
        //             $taxblAmtC1 += $vatTaxblAmt;
        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtC1 += $vatAmt;
        //         }
        //         elseif($taxCategoryCd == 'C2')
        //         {
        //             $taxRtC2 =$detail['TaxRate1'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC2);
        //             $taxblAmtC2 += $vatTaxblAmt;
        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtC2 += $vatAmt;
        //         }
        //         elseif($taxCategoryCd == 'C3')
        //         {
        //             $taxRtC3 =$detail['TaxRate1'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC3);
        //             $taxblAmtC3 += $vatTaxblAmt;
        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtC3 += $vatAmt;
        //         }
        //         elseif($taxCategoryCd == 'D')
        //         {
        //             $taxRtD =$detail['TaxRate1'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtD);
        //             $taxblAmtD += $vatTaxblAmt;
        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtD += $vatAmt;
        //         }
        //         elseif($taxCategoryCd == 'RVAT')
        //         {
        //             $taxRtRvat =$detail['TaxRate1'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtRvat);
        //             $taxblAmtRvat += $vatTaxblAmt;
        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtRvat += $vatAmt;
        //         }
        //         elseif($taxCategoryCd == 'E')
        //         {
        //             $taxRtE =$detail['TaxRate1'];
        //             $vatTaxblAmt = $item_price * 100 / (100 + $taxRtE);
        //             $taxblAmtE += $vatTaxblAmt;
        //             $vatAmt =  $item_price -  $vatTaxblAmt;
        //             $taxAmtE += $vatAmt;
        //         }
        //     }

        //     $qtyUnitCd = explode('-',$detail['InvoiceUnitOfMeasure']);
        //     $itemClsCd = $this->getItemUNCode($detail['Item']);
        //     ++$itemNumber;
        //     $invoiceItem = [
        //         "itemSeq" => $itemNumber,
        //         "itemCd" => $detail['Item'],
        //         "itemClsCd" => $itemClsCd,
        //         "itemNm" => $detail['Description'],
        //         "bcd" => '',
        //         "pkgUnitCd" => $pkgUnitCd,
        //         "pkg" => floatval($detail['QuantityShipped']),
        //         "qtyUnitCd" => $qtyUnitCd[0],
        //         "qty" => $detail['QuantityShipped'],
        //         "prc" => $detail['UnitPrice'],
        //         "splyAmt" => round(($detail['UnitPrice'] * $detail['QuantityShipped']),4),
        //         "dcRt" => $detail['PriceDiscountPercentage'],
        //         "dcAmt" => $detail['PriceDiscountAmount'],
        //         "isrccCd" => '',
        //         "isrccNm" => '',
        //         "isrcAmt" => 0.0,

        //         "vatCatCd" => $vatCatCd,
        //         "vatTaxblAmt" => round($vatTaxblAmt,4),
        //         "vatAmt" => round($vatAmt,4),

        //         "exciseTxCatCd" => $exciseTxCatCd,
        //         "exciseTxAmt" => round($exciseTxAmt,4),
        //         "exciseTaxblAmt" => round($exciseTaxblAmt,4),

        //         "tlCatCd" => $tlCatCd,
        //         "tlTaxblAmt" => round($tlTaxblAmt,4),
        //         "tlAmt" => round($tlAmt,4),

        //         "fCatCd" => $fCatCd,
        //         "fTaxblAmt" => round($fTaxblAmt,4),
        //         "fAmt" => round($fAmt,4),

        //         "iplCatCd" => $iplCatCd,
        //         "iplTaxblAmt" => round($iplTaxblAmt,4),
        //         "iplAmt" => round($iplAmt,4),

        //         "totAmt" => round($detail['DetailAmountDue'],4),
        //     ];
        //     $zra_invoice_array['itemList'][] = $invoiceItem;

        //     $stock_itemS = [
        //         "itemSeq"=> $itemNumber,
        //         "itemCd"=> $detail['Item'],
        //         "itemClsCd"=> $itemClsCd,
        //         "itemNm"=> $detail['Description'],
        //         "bcd"=> NULL,
        //         "pkg"=> floatval($detail['QuantityShipped']),
        //         "pkgUnitCd"=> $pkgUnitCd,
        //         "qtyUnitCd" => $qtyUnitCd[0],
        //         "qty"=> $detail['QuantityShipped'],
        //         "itemExprDt"=> NULL,
        //         "totDcAmt"=> $detail['PriceDiscountAmount'],
        //         "iplCatCd"=> $iplCatCd,
        //         "tlCatCd"=> $tlCatCd,
        //         "exciseTxCatC"=> $exciseTxCatCd,
        //         "prc"=> round($detail['UnitPrice'],2),
        //         "splyAmt" => round(($detail['UnitPrice'] * $detail['QuantityShipped']),2),
        //         "iplAmt"=> round($iplAmt,2),
        //         "tlAmt"=> round($tlAmt,2),
        //         "exciseTxAmt"=> round($exciseTaxblAmt,2),
        //         "taxblAmt"=> round($tlTaxblAmt,2),
        //         "vatCatCd"=> $vatCatCd,
        //         "taxAmt"=> round(($vatAmt+$iplAmt+$tlAmt+$exciseTxAmt+$fAmt),2),
        //         "totAmt"=> round($detail['DetailAmountDue'],2)
        //     ];
        //     $zra_stock_item_array["itemList"][] = $stock_itemS;
        // }

        // $zra_invoice_array['taxblAmtA'] = round($taxblAmtA,4);
        // $zra_invoice_array['taxblAmtB'] = round($taxblAmtB,4);
        // $zra_invoice_array['taxblAmtC'] = round($taxblAmtC,4);
        // $zra_invoice_array['taxblAmtC1'] = round($taxblAmtC1,4);
        // $zra_invoice_array['taxblAmtC2'] = round($taxblAmtC2,4);
        // $zra_invoice_array['taxblAmtC3'] = round($taxblAmtC3,4);
        // $zra_invoice_array['taxblAmtD'] = round($taxblAmtD,4);
        // $zra_invoice_array['taxblAmtRvat'] = round($taxblAmtRvat,4);
        // $zra_invoice_array['taxblAmtE'] = round($taxblAmtE,4);
        // $zra_invoice_array['taxblAmtF'] = round($taxblAmtF,4);
        // $zra_invoice_array['taxblAmtIpl1'] =round($taxblAmtIpl1,4);
        // $zra_invoice_array['taxblAmtIpl2'] = round($taxblAmtIpl2,4);
        // $zra_invoice_array['taxblAmtTl'] = round($taxblAmtTl,4);
        // $zra_invoice_array['taxblAmtEcm'] = round($taxblAmtEcm,4);
        // $zra_invoice_array['taxblAmtExeeg'] = round($taxblAmtExeeg,4);
        // $zra_invoice_array['taxblAmtTot'] = round($taxblAmtTot,4);

        // //TODO we got to dynamically get these
        // $zra_invoice_array['taxRtA'] = 16;//$taxRtA;
        // $zra_invoice_array['taxRtB'] = 16;//$taxRtB;
        // $zra_invoice_array['taxRtC1'] = 0;
        // $zra_invoice_array['taxRtC2'] = 0;
        // $zra_invoice_array['taxRtC3'] = 0;
        // $zra_invoice_array['taxRtD'] = 0;
        // $zra_invoice_array['taxRtRvat'] = 16;//$taxRtRvat;
        // $zra_invoice_array['taxRtE'] = 0;
        // $zra_invoice_array['taxRtF'] = 10;//$taxRtF;
        // $zra_invoice_array['taxRtIpl1'] = 5;//$taxRtIpl1;
        // $zra_invoice_array['taxRtIpl2'] = 0;
        // $zra_invoice_array['taxRtTl'] = 1.5;//$taxRtTl;
        // $zra_invoice_array['taxRtEcm'] = 5;//$taxRtEcm;
        // $zra_invoice_array['taxRtExeeg'] = 3;//$taxRtExeeg;
        // $zra_invoice_array['taxRtTot'] = $taxRtTot;

        // $zra_invoice_array['taxAmtA'] =  round($taxAmtA,4);
        // $zra_invoice_array['taxAmtB'] =  round($taxAmtB,4);
        // $zra_invoice_array['taxAmtC'] =  round($taxAmtC,4);
        // $zra_invoice_array['taxAmtC1'] =  round($taxAmtC1,4);
        // $zra_invoice_array['taxAmtC2'] =  round($taxAmtC2,4);
        // $zra_invoice_array['taxAmtC3'] =  round($taxAmtC3,4);
        // $zra_invoice_array['taxAmtD'] =  round($taxAmtD,4);
        // $zra_invoice_array['taxAmtRvat'] =  round($taxAmtRvat,4);
        // $zra_invoice_array['taxAmtE'] =  round($taxAmtE,4);
        // $zra_invoice_array['taxAmtF'] =  round($taxAmtF,4);
        // $zra_invoice_array['taxAmtIpl1'] =  round($taxAmtIpl1,4);
        // $zra_invoice_array['taxAmtIpl2'] =  round($taxAmtIpl2,4);
        // $zra_invoice_array['taxAmtTl'] =  round($taxAmtTl,4);
        // $zra_invoice_array['taxAmtEcm'] =  round($taxAmtEcm,4);
        // $zra_invoice_array['taxAmtExeeg'] =  round($taxAmtExeeg,4);

        // $TotalTaxAmount =  $taxAmtA + $taxAmtB +  $taxAmtC + $taxAmtC1 + $taxAmtC2 + $taxAmtC3 + $taxAmtD + $taxAmtRvat + $taxAmtE + $taxAmtF + $taxAmtIpl1 + $taxAmtIpl2 + $taxAmtTl + $taxAmtEcm + $taxAmtExeeg;
        // $TotalTaxBase =  $taxblAmtA + $taxblAmtB + $taxblAmtC + $taxblAmtC1 + $taxblAmtC2+ $taxblAmtC3+ $taxblAmtD + $taxblAmtRvat + $taxblAmtE+ $taxblAmtF +$taxblAmtIpl1 +$taxblAmtIpl2+$taxblAmtTl+$taxblAmtEcm+$taxblAmtExeeg;

        // $zra_invoice_array["totTaxAmt"] = round($TotalTaxAmount,4);
        // // $zra_invoice_array["totTaxblAmt"] = round($TotalTaxBase,4);
        // $zra_invoice_array["totTaxblAmt"] = round(($result['InvoiceAmountDue'] - $TotalTaxAmount),4);
        // // $zra_stock_item_array["totTaxblAmt"] = round($TotalTaxBase,2);
        // $zra_stock_item_array["totTaxblAmt"] = round(($result['InvoiceAmountDue'] - $TotalTaxAmount),2);

        // $zra_stock_item_array["totTaxAmt"] = round($TotalTaxAmount,2);
        // $zra_stock_item_array["totAmt"] = round($result['InvoiceAmountDue'],2);
        // $zra_stock_item_array["totItemCnt"] = $result['NumberOfLinesInInvoice'];

        // $response = Http::post($zra_base_url."trnsSales/saveSales", $zra_invoice_array);
        // $result = $response->json();
        // Log::info($result);
        // if($response->failed()){
        //     Log::alert('***ZRA Call Failed to be processed offline****');
        //     $offline_trans = new OfflineTransaction();
        //     $offline_trans->endpoint = "trnsSales/saveSales";
        //     $offline_trans->payload = json_encode($zra_invoice_array,true);
        //     $offline_trans->save();

        //     $offline_trans = new OfflineTransaction();
        //     $offline_trans->endpoint = "stock/saveStockItems";
        //     $offline_trans->payload = json_encode($zra_stock_item_array,true);
        //     $offline_trans->save();

        //     return;
        // }

        // Log::info(json_encode($zra_invoice_array));
        // ConfigController::LogDeviceData("trnsSales/saveSales",$zra_invoice_array,$result);

        // //update sales_invoice tables
        // $sales_invoice->resultCd = $result['resultCd'];
        // $sales_invoice->resultMsg = $result['resultMsg'];
        // $sales_invoice->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
        // if(!empty($result['data']))
        // {
        //     $sales_invoice->rcptNo = $result['data']['rcptNo'];
        //     $sales_invoice->intrlData = $result['data']['intrlData'];
        //     $sales_invoice->rcptSign = $result['data']['rcptSign'];
        //     $sales_invoice->vsdcRcptPbctDate = $result['data']['vsdcRcptPbctDate'];
        //     $sales_invoice->sdcId = $result['data']['sdcId'];
        //     $sales_invoice->mrcNo = $result['data']['mrcNo'];
        //     $sales_invoice->qrCodeUrl = $result['data']['qrCodeUrl'];
        // }

        // if($result['resultCd'] == '000')
        // {
        //     $sales_invoice->status = 'Interfaced';
        //     $sales_invoice->save();
        //     StockItemController::interfaceStockItem($zra_stock_item_array);
        // }
        // elseif($result['resultCd'] == '910')
        // {
        //     if((int)strpos($result['resultMsg'],"custTpin")>0)
        //     {
        //         $this-> retryInterfacingSale($zra_invoice_array,$zra_stock_item_array, $zra_base_url, $sales_invoice);
        //     }
        // }
        // else
        // {
        //     $sales_invoice->save();
        // }


        // return response()->json($result);
    }
    public function serviceManagerCNote(Request  $request){
        return;
    }
}
