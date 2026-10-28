<?php

namespace App\Http\Controllers\API;

use App\CreditNote;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\ConfigController;
use App\OfflineTransaction;
use App\SalesInvoice;
use App\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateSalesInvoiceStocks;

class CreditNoteController extends Controller
{
    private function getRuntimeEnv($key)
    {
        $value = env($key);
        if ($value !== null && $value !== '') {
            return $value;
        }

        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (!empty($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        $envPath = base_path('.env');
        if (!is_readable($envPath)) {
            return null;
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            [$envKey, $envValue] = array_map('trim', explode('=', $line, 2));
            if ($envKey === $key) {
                return trim($envValue, "\"'");
            }
        }

        return null;
    }

    private function getSageConnection()
    {
        return [
            'base_url' => $this->getRuntimeEnv('SAGE_URL'),
            'user' => $this->getRuntimeEnv('SAGE_USER'),
            'password' => $this->getRuntimeEnv('SAGE_PASSWORD'),
        ];
    }

    private function getHardcodedSplitInvoiceReceipt($invoiceNumber)
    {
        $hardcodedSdcBatches = [
            'IN00070049-1' => [
                'invoice_number' => 'IN00070049-1',
                'invoice_id' => '3350529-1',
                'rcptNo' => '5730',
                'intrlData' => 'M4NPHD3VRA6A652W6IQQA43MF4',
                'rcptSign' => 'K5KOHTPQEKOCW2S2',
                'vsdcRcptPbctDate' => '20260413130104',
                'sdcId' => 'SDC0010017630',
                'mrcNo' => 'WIS00028812',
                'qrCodeUrl' => 'https://siportal.zra.org.zm/indexInvoiceData?Data=1001882723000K5KOHTPQEKOCW2S2',
            ],
            'IN00070049-2' => [
                'invoice_number' => 'IN00070049-2',
                'invoice_id' => '3350529-2',
                'rcptNo' => '5731',
                'intrlData' => '4LTPUFLV4E5DFWW6WRLC5FUW5M',
                'rcptSign' => 'ZB3NKXYKLN75CV5H',
                'vsdcRcptPbctDate' => '20260413130149',
                'sdcId' => 'SDC0010017630',
                'mrcNo' => 'WIS00028812',
                'qrCodeUrl' => 'https://siportal.zra.org.zm/indexInvoiceData?Data=1001882723000ZB3NKXYKLN75CV5H',
            ],
        ];

        return $hardcodedSdcBatches[$invoiceNumber] ?? null;
    }

    public function selectUnitOfmeasure($sageUOM){
        if (strtolower($sageUOM)=="meter"){
            return "2X";
        }
        else if (strtolower($sageUOM)=="hour"){
            return "hr";
        }
        else if (strtolower($sageUOM)=="packet"){
            return "PA";
        }
        else{
            return 'EACH';
            // return $sageUOM;
        }
    }
    public function index()
    {
        $data = CreditNote::orderByDesc('updated_at')->orderByDesc('id')->get();
        return view('pages.credit-notes.index',['data'=>$data]);
    }

    public function printCRDN($sageCRNNum)
    {
        $data = $this->getCRDNData($sageCRNNum);
        $data['isreprint'] = false;
        return view('pages.credit-notes.print',['data'=>$data]);
    }

    public function reprintCRDN($sageCRNNum)
    {
        $data = $this->getCRDNData($sageCRNNum);
        $data['isreprint'] = true;
        return view('pages.credit-notes.print',['data'=>$data]);
    }
    public function getCRDNData($sageCRNNum)
    {
        $crn  = CreditNote::where('sage_crn_number',$sageCRNNum)->get();
        if ( !count($crn) )
        {
            App::abort(404);
        }
        else
        {
            $crn = $crn[0];
        }

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');
        $creditNoteID = $crn->credit_note_id;
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/OE/OECreditDebitNotes($creditNoteID)");
        $result = $response->json();

        // Log::info($result);

        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $buyerTPin = $crn->customerTPIN;

        $VSDCDate = substr($crn->resultDt, 0, 8);
        $VSDCTime = substr($crn->resultDt, 8);

        $CreditDebitNoteType  = "Credit Note";
        if($result['CreditDebitNoteType'] == 'DebitNote') $CreditDebitNoteType  = "Debit Note";

        $data = [
            'VSDCDate' => $VSDCDate,
            'VSDCTime' => $VSDCTime,
            'VSDCID' => $crn->sdcId,
            'VSDCInvoiceNumber' => $crn->rcptNo,
            'VSDCInvoiceType' => 'Normal Sale',
            'VSDCInternalData' => $crn->intrlData,
            'VSDCSignature' => $crn->rcptSign,
            'VSDCURL' => $crn->qrCodeUrl,
            'tPin'=>$tinNumber,
            'buyerTPin'=>$buyerTPin,
            'InvoiceNumber'=>$result['InvoiceNumber'],
            'CreditDebitNoteNumber'=>$result['CreditDebitNoteNumber'],
            'OrderNumber'=>$result['OrderNumber'],
            "BillTo" => $result["BillTo"],
            "BillToAddress1" => $result["BillToAddress1"],
            "BillToAddress2" => $result["BillToAddress2"],
            "BillToCity" => $result["BillToCity"],
            "BillToCountry" => $result["BillToCountry"],
            "BillToPhone" => $result["BillToPhone"],
            'InvoiceDate' => date('d-M-Y', strtotime($result['InvoiceDate'])),
            'CRDNDate' => date('d-M-Y', strtotime($result['CreditDebitNoteDate'])),
            'CreditDebitNoteType' => $CreditDebitNoteType
        ];

        foreach($result['CreditDebitNoteOptionalFields'] as $optField)
        {
            if($optField['OptionalField'] == 'CRNOTEREASON') $data['CRNOTEREASON'] = $optField['ValueDescription'];
        }

        $data['items'] = [];
        $taxes = [];
        $TotalTaxAmount = 0;

        foreach($result['CreditDebitDetails'] as $detail)
        {
            $taxCategoryCd1 = explode('-', $detail['TaxClass1Description']);
            $taxCategoryCd1 = strval(trim($taxCategoryCd1[0]));
            $TaxCategory = $taxCategoryCd1;

            $taxCategoryCd2 = explode('-', $detail['TaxClass2Description']);
            $taxCategoryCd2 = trim($taxCategoryCd2[0]);
            if(strlen($taxCategoryCd2))
            {
                $TaxCategory = $TaxCategory.' and '.$taxCategoryCd2;
            }

            $data['items'][] = [
                'Description' => $detail['Description'],
                'TaxCategory' => $TaxCategory,
                'UnitPrice' => number_format($detail['UnitPrice'],2),
                'QuantityReturned' => $detail['QuantityReturned'],
                'Discount' => number_format($detail['PriceDiscountAmount'],2),
                'TotalAmount' => number_format($detail['DiscountedExtendedAmount'],2),
            ];
        }

        if($result['TotalTaxAmount1'] > 0)
        {
            $taxCategoryCd1 = explode('-', $result['TaxClass1Description']);
            $taxCategoryCd1 = strval(trim($taxCategoryCd1[0]));
             $taxes[] = [
                'amt' => number_format($result['TotalTaxAmount1'],2),
                'rate' => UtilsController::getTaxRate($taxCategoryCd1)."%",
                'cat' => $taxCategoryCd1
            ];
            $TotalTaxAmount += $result['TotalTaxAmount1'];
        }

        if($result['TotalTaxAmount2'] > 0)
        {
            $taxCategoryCd1 = explode('-', $result['TaxClass2Description']);
            $taxCategoryCd1 = strval(trim($taxCategoryCd1[0]));
             $taxes[] = [
                'amt' => number_format($result['TotalTaxAmount2'],2),
                'rate' => UtilsController::getTaxRate($taxCategoryCd1)."%",
                'cat' => $taxCategoryCd1
            ];
            $TotalTaxAmount += $result['TotalTaxAmount2'];
        }

        if($result['TotalTaxAmount3'] > 0)
        {
            $taxCategoryCd1 = explode('-', $result['TaxClass3Description']);
            $taxCategoryCd1 = strval(trim($taxCategoryCd1[0]));
             $taxes[] = [
                'amt' => number_format($result['TotalTaxAmount3'],2),
                'rate' => UtilsController::getTaxRate($taxCategoryCd1)."%",
                'cat' => $taxCategoryCd1
            ];
            $TotalTaxAmount += $result['TotalTaxAmount3'];
        }

        if($result['TotalTaxAmount4'] > 0)
        {
            $taxCategoryCd1 = explode('-', $result['TaxClass4Description']);
            $taxCategoryCd1 = strval(trim($taxCategoryCd1[0]));
             $taxes[] = [
                'amt' => number_format($result['TotalTaxAmount4'],2),
                'rate' => UtilsController::getTaxRate($taxCategoryCd1)."%",
                'cat' => $taxCategoryCd1
            ];
            $TotalTaxAmount += $result['TotalTaxAmount4'];
        }

        if($result['TotalTaxAmount5'] > 0)
        {
            $taxCategoryCd1 = explode('-', $result['TaxClass5Description']);
            $taxCategoryCd1 = strval(trim($taxCategoryCd1[0]));
             $taxes[] = [
                'amt' => number_format($result['TotalTaxAmount5'],2),
                'rate' => UtilsController::getTaxRate($taxCategoryCd1)."%",
                'cat' => $taxCategoryCd1
            ];
            $TotalTaxAmount += $result['TotalTaxAmount5'];
        }

        $data['taxes'] = $taxes;
        $data['TotalTaxAmount'] =  number_format($TotalTaxAmount,2);
        $data['CreditDebitNoteRunningTotal'] = number_format($result['CreditDebitNoteRunningTotal'],2);


        return $data;
    }

    public function reinterfaceCRDN(Request $request)
    {
        $crn  = CreditNote::find($request->id);
        $creditNoteID = $crn->credit_note_id;
        $customerTPIN = $crn->customerTPIN;

        $response = Http::get(env('APP_URL') . "/api/send-credit-note", [
            'creditNoteID' => $creditNoteID,
            'customerTPIN' => $customerTPIN
        ]);
  
        $result = $response->json();

        if ($response->successful()) {
            $truncated_msg = $this->truncate_text($result['resultMsg']);
            $crn->resultCd = $result['resultCd'];
            $crn->resultMsg = $truncated_msg;
            $crn->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
            $crn->save();
            
            $msg = $result['resultMsg'];
            return redirect()->back()->with('status', $msg);
        } else {
            $msg =  response()->json(['error' => 'Failed to send credit note'], $response->status());
            return redirect()->back()->with('error', $msg);
        }
    }

    function truncate_text($text, $length = 250) {
        return substr($text, 0, $length);
    }

    public function splitAndSendCreditNote(Request $request)
    {
        ini_set('max_execution_time', 3600);
        set_time_limit(3600);

        $creditNoteNumber = trim((string) (
            $request->query('CreditDebitNoteNumber')
            ?? $request->query('creditDebitNoteNumber')
            ?? $request->query('CreditNoteNumber')
            ?? $request->query('creditNoteNumber')
            ?? $request->query('sageCRNNumber')
            ?? $request->input('CreditDebitNoteNumber')
            ?? $request->input('creditDebitNoteNumber')
            ?? $request->input('CreditNoteNumber')
            ?? $request->input('creditNoteNumber')
            ?? $request->input('sageCRNNumber')
        ));

        $creditNoteID = trim((string) (
            $request->query('creditNoteID')
            ?? $request->query('CreditNoteID')
            ?? $request->input('creditNoteID')
            ?? $request->input('CreditNoteID')
        ));

        if ($creditNoteNumber === '' && $creditNoteID === '') {
            return response()->json([
                'success' => false,
                'message' => 'CreditDebitNoteNumber or creditNoteID is required.'
            ], 422);
        }

        $sageConnection = $this->getSageConnection();
        $sage_base_url = $sageConnection['base_url'];
        $sageUser = $sageConnection['user'];
        $sagePwd = $sageConnection['password'];

        if (!$sage_base_url || !$sageUser || !$sagePwd) {
            return response()->json([
                'success' => false,
                'message' => 'Sage connection is not configured. Check SAGE_URL, SAGE_USER, and SAGE_PASSWORD.'
            ], 500);
        }

        if ($creditNoteID === '') {
            $sageCreditNoteNumber = str_replace("'", "''", $creditNoteNumber);
            $response = Http::withBasicAuth($sageUser, $sagePwd)
                ->timeout(3600)
                ->get("$sage_base_url/OE/OECreditDebitNotes", [
                    '$filter' => "CreditDebitNoteNumber eq '$sageCreditNoteNumber'"
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'credit_note_number' => $creditNoteNumber,
                    'message' => 'Failed to fetch credit note from Sage. HTTP ' . $response->status()
                ], 500);
            }

            $creditNoteSearchResult = $response->json();
            $sageCreditNote = $creditNoteSearchResult['value'][0] ?? null;

            if (!$sageCreditNote) {
                return response()->json([
                    'success' => false,
                    'credit_note_number' => $creditNoteNumber,
                    'message' => 'Credit note was not found in Sage.'
                ], 404);
            }

            $creditNoteID = $sageCreditNote['CNUniquifier'];
        }

        $response = Http::withBasicAuth($sageUser, $sagePwd)
            ->timeout(3600)
            ->get("$sage_base_url/OE/OECreditDebitNotes($creditNoteID)");

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'credit_note_number' => $creditNoteNumber,
                'credit_note_id' => $creditNoteID,
                'message' => 'Failed to fetch credit note details from Sage. HTTP ' . $response->status()
            ], 500);
        }

        $creditNoteResult = $response->json();
        $creditDebitDetails = $creditNoteResult['CreditDebitDetails'] ?? [];
        $totalLineItems = count($creditDebitDetails);

        if ($totalLineItems < 1) {
            return response()->json([
                'success' => false,
                'credit_note_number' => $creditNoteNumber ?: ($creditNoteResult['CreditDebitNoteNumber'] ?? ''),
                'credit_note_id' => $creditNoteID,
                'message' => 'Credit note has no line items to process.'
            ], 422);
        }

        $customerTPIN = '';
        $CustomerNumber = $creditNoteResult['CustomerNumber'] ?? '';
        if ($CustomerNumber !== '') {
            $customerResponse = Http::withBasicAuth($sageUser, $sagePwd)
                ->timeout(3600)
                ->get("$sage_base_url/AR/ARCustomers('$CustomerNumber')");

            if ($customerResponse->successful()) {
                $customerResult = $customerResponse->json();
                $customerTPIN = $customerResult['BusinessRegistrationNumber'] ?? '';
            }
        }

        $batches = array_chunk($creditDebitDetails, 999);
        $results = [];
        $hasFailures = false;

        foreach ($batches as $batchIndex => $batch) {
            $counter = $batchIndex + 1;
            $offset = $batchIndex * 999;

            $splitRequest = Request::create('/api/send-credit-note', 'GET', [
                'creditNoteID' => $creditNoteID,
                'customerTPIN' => $customerTPIN,
                'splitCounter' => $counter,
                'detailOffset' => $offset,
                'detailLimit' => 999,
            ]);

            $splitResponse = $this->sendCreditNote($splitRequest);

            if ($splitResponse === null) {
                $statusCode = 500;
                $responseData = [
                    'resultCd' => '999',
                    'resultMsg' => 'VSDC call failed and was queued for offline processing.',
                ];
            }
            else {
                $statusCode = method_exists($splitResponse, 'getStatusCode') ? $splitResponse->getStatusCode() : 200;
                $responseData = method_exists($splitResponse, 'getData') ? $splitResponse->getData(true) : $splitResponse;
            }

            $isSuccessful = $statusCode >= 200 && $statusCode < 300 && (($responseData['resultCd'] ?? null) === '000');
            if (!$isSuccessful) {
                $hasFailures = true;
            }

            $baseCreditNoteNumber = $creditNoteResult['CreditDebitNoteNumber'] ?? $creditNoteNumber;
            $results[] = [
                'credit_note_number' => $baseCreditNoteNumber . '-' . $counter,
                'credit_note_id' => $creditNoteID . '-' . $counter,
                'line_item_count' => count($batch),
                'status_code' => $statusCode,
                'success' => $isSuccessful,
                'response' => $responseData,
            ];
        }

        return response()->json([
            'success' => !$hasFailures,
            'credit_note_number' => $creditNoteNumber ?: ($creditNoteResult['CreditDebitNoteNumber'] ?? ''),
            'credit_note_id' => $creditNoteID,
            'invoice_number' => $creditNoteResult['InvoiceNumber'] ?? '',
            'total_line_items' => $totalLineItems,
            'batch_count' => count($batches),
            'batches' => $results,
        ], $hasFailures ? 500 : 200);
    }

    public function sendCreditNote(Request $request)
    {
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $zra_base_url = $settings->baseUrl;
        $is_tax_exclusive = $settings->is_tax_exclusive;

        $sageConnection = $this->getSageConnection();
        $sage_base_url = $sageConnection['base_url'];
        $sageUser = $sageConnection['user'];
        $sagePwd = $sageConnection['password'];

        if (!$sage_base_url || !$sageUser || !$sagePwd) {
            return response()->json([
                'resultCd' => '999',
                'resultMsg' => 'Sage connection is not configured. Check SAGE_URL, SAGE_USER, and SAGE_PASSWORD.',
            ], 500);
        }
        $creditNoteID = $request->query("creditNoteID");
        $customerTPIN = $request->query("customerTPIN");
        // Log::info($request);

        $response = Http::withBasicAuth($sageUser, $sagePwd)->timeout(720)->get("$sage_base_url/OE/OECreditDebitNotes($creditNoteID)");
        $result = $response->json();

        // Log::debug($result);

        //create a new crn in api DB
        $sageCRNNum = $result['CreditDebitNoteNumber'];
        $splitCounter = $request->query("splitCounter");
        $isSplitCreditNote = $splitCounter !== null && $splitCounter !== '';
        if($result['CNUniquifier'] == '108097') $result['InvoiceNumber'] = $isSplitCreditNote ? 'IN00070049' : 'IN00070049-2';

        $zraCreditNoteNumber = $isSplitCreditNote ? $sageCRNNum . '-' . $splitCounter : $sageCRNNum;
        $creditNoteRecordID = $isSplitCreditNote ? $creditNoteID . '-' . $splitCounter : $creditNoteID;
        $originalInvoiceNumber = $isSplitCreditNote ? $result['InvoiceNumber'] . '-' . $splitCounter : $result['InvoiceNumber'];
        $creditDebitDetails = $result['CreditDebitDetails'] ?? [];

        if ($isSplitCreditNote) {
            $detailOffset = max(0, (int) $request->query("detailOffset", 0));
            $detailLimit = min(999, max(1, (int) $request->query("detailLimit", 999)));
            $creditDebitDetails = array_slice($creditDebitDetails, $detailOffset, $detailLimit);

            if (count($creditDebitDetails) < 1) {
                return response()->json([
                    'resultCd' => '999',
                    'resultMsg' => 'Split credit note batch has no line items.',
                ], 422);
            }
        }

        $lineItemCount = $isSplitCreditNote ? count($creditDebitDetails) : $result['NoOfLinesInCreditDebitNote'];
        $crn  = CreditNote::where('credit_note_id',$creditNoteRecordID)->get();
        if(!count($crn))
        {
            $crn  = new CreditNote();
            $crn->credit_note_id = $creditNoteRecordID;
            $crn->sage_crn_number = $zraCreditNoteNumber;

            if(is_numeric($customerTPIN) and
            strlen((string)$customerTPIN) and
            (strlen((string)$customerTPIN)>0 and strlen((string)$customerTPIN)<=10))
            {
                $crn->customerTPIN = $customerTPIN;
            }
            elseif(strlen($result['ShipmentTrackingNumber']))
            {
                $customerTPIN = $result['ShipmentTrackingNumber'];
                $crn->customerTPIN = $customerTPIN;
            }
            else
            {
                $customerTPIN = '1000000000';
                $crn->customerTPIN = $customerTPIN;
            }

            $crn->save();
        }
        else
        {
            $crn = $crn[0];
            $crn->sage_crn_number = $zraCreditNoteNumber;
        }

        $sales_invoice  = SalesInvoice::where('sage_invoice_number',$originalInvoiceNumber)->get();
        $hardcodedSplitInvoiceReceipt = null;

        if(count($sales_invoice) <= 0)
        {
            $hardcodedSplitInvoiceReceipt = $this->getHardcodedSplitInvoiceReceipt($originalInvoiceNumber);

            if (!$hardcodedSplitInvoiceReceipt) {
                Log::info('Sales Invoice '.$originalInvoiceNumber.' not found in stepanite!');
                Log::info($sales_invoice);
                $failed_msg['resultMsg'] = 'Sales Invoice '.$originalInvoiceNumber.' not found in stepanite!';
                $failed_msg['resultCd'] = 10000;
                return $failed_msg;
            }
        }

        Log::info($originalInvoiceNumber);

        $orgInvcNo = $hardcodedSplitInvoiceReceipt ? $hardcodedSplitInvoiceReceipt['rcptNo'] : $sales_invoice[0]['rcptNo'];
        $orgSdcId = $hardcodedSplitInvoiceReceipt ? $hardcodedSplitInvoiceReceipt['sdcId'] : $sales_invoice[0]['sdcId'];
        if(empty($orgInvcNo))
        {
            Log::alert($sales_invoice[0]);
            Log::info('Sales Invoice '.$originalInvoiceNumber.' orgInvcNo not found in stepanite!');
            Log::info($sales_invoice[0]);
            $failed_msg['resultMsg'] = 'Sales Invoice '.$originalInvoiceNumber.' orgInvcNo not found in stepanite!';
            $failed_msg['resultCd'] = 10000;
            return $failed_msg;
        }

        if(count($sales_invoice)) {
            $sales_invoice = $sales_invoice[0];
        }
        else {
            $sales_invoice = (object) array_merge($hardcodedSplitInvoiceReceipt, [
                'customerTPIN' => $crn->customerTPIN,
            ]);
        }
        Log::info(json_encode($sales_invoice));

        //pull invoice from ZRA
        $zra_invoice_check_array = [
            "tpin" => $tinNumber, 
            "bhfId" => $branchCode, 
            "invcSdcId" => $orgSdcId, 
            "cisInvcNo" => $zraCreditNoteNumber
        ];
        $inv_response = Http::timeout(360)->post($zra_base_url . "trnsSales/selectInvoice", $zra_invoice_check_array);
        $inv_result = $inv_response->json();

        Log::info("**********************************");
        Log::info($inv_result);   
        

        // we try with -R
        if($inv_result['resultCd'] != '000')
        {
            //pull invoice from ZRA
            $zra_invoice_check_array = [
                "tpin" => $tinNumber, 
                "bhfId" => $branchCode, 
                "invcSdcId" => $orgSdcId, 
                "cisInvcNo" => $zraCreditNoteNumber."-R",
            ];
            // $inv_response = Http::post($zra_base_url."trnsSales/selectInvoice", $zra_invoice_check_array);
            $inv_response = Http::timeout(360)->post($zra_base_url . "trnsSales/selectInvoice", $zra_invoice_check_array);
            $inv_result = $inv_response->json();

            Log::info("**********************************");
            Log::info($inv_result);
        }
        // CIS Invoice number already exists
        if($inv_result['resultCd'] == '000')
        {    
            Log::info('Already interfaced......'); 
            Log::info($crn);    
            $crn->status = 'Interfaced';
            $crn->resultCd = $inv_result['resultCd'];
            $crn->resultMsg = $inv_result['resultMsg'];
            $crn->rcptNo = $inv_result['data']['rcptNo'];
            $crn->intrlData = $inv_result['data']['intrlData'];
            $crn->rcptSign = $inv_result['data']['rcptSign'];
            $crn->vsdcRcptPbctDate = $inv_result['data']['vsdcRcptPbctDate'];
            $crn->sdcId = $inv_result['data']['sdcId'];
            $crn->mrcNo = $inv_result['data']['mrcNo'];
            $crn->qrCodeUrl = $inv_result['data']['qrCodeUrl'];
            $crn->resultDt = empty($inv_result['resultDt']) ? '' : $inv_result['resultDt'];
            $crn->save();
            return $inv_result;
        }


        $pmtTyCd = $saleCtyCd = $destnCountryCd = '';
        $dbtRsnCd = $invcAdjustReason = $rfdRsnCd = "";
        foreach($result['CreditDebitNoteOptionalFields'] as $optField)
        {
            if($optField['OptionalField'] == 'PAYMENTTYPE')
            {
                $pmtTyCd = $optField['Value'];
            }
            elseif($optField['OptionalField'] == 'SALECATEGORY')
            {
                $saleCtyCd = $optField['Value'];
            }
            elseif($optField['OptionalField'] == 'CRNOTEREASON')
            {
                $invcAdjustReason = $optField['ValueDescription'];
                if($result['CreditDebitNoteType'] == 'DebitNote')
                {
                    $dbtRsnCd = $optField['TextValue'];
                    $rfdRsnCd = null;
                }
                else
                {
                    $rfdRsnCd = $optField['TextValue'];
                    $dbtRsnCd = null;
                }
            }
            elseif($optField['OptionalField'] == 'DESTCOUNTRY')
            {
                $destnCountryCd = $optField['Value'];
            }
        }

        $salesSttsCd = '02';//sage only has approved.
        $rcptTyCd  = "R";
        if($result['CreditDebitNoteType'] == 'DebitNote') $rcptTyCd  = "D";

        // $exchangeRt = $result["CreditDebitNoteNumber"] == 'CN00002848' ? :
        $exchangeRt = round($result['CreditDebitNoteRate'],4);
        if($result["CreditDebitNoteNumber"] == 'CN00002864')
        {
            $exchangeRt = '28.8600';
        }

        //make zra array
        $zra_credit_note_array = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "orgSdcId" => $orgSdcId,
            "orgInvcNo" =>  $orgInvcNo,
            "cisInvcNo" => $zraCreditNoteNumber,
            // "custTpin" => "1000724543", TODO
            "custNm" => $result['BillTo'],
            "salesTyCd" => "N",
            "rcptTyCd" => $rcptTyCd,
            "pmtTyCd" => $pmtTyCd,
            "salesSttsCd" => $salesSttsCd,
            "cfmDt" => date('Ymdhis', strtotime($result['InvoiceRateDate'])),
            "salesDt" => date('Ymd', strtotime($result['InvoiceRateDate'])),
            "stockRlsDt" => date('Ymdhis', strtotime($result['CreditDebitNoteDate'])),
            "cnclReqDt" => date('Ymdhis', strtotime($result['CreditDebitNoteDate'])),
            "cnclDt" => date('Ymdhis', strtotime($result['CreditDebitNoteDate'])),
            "rfdDt" => null,
            "rfdRsnCd" => $rfdRsnCd,
            "totItemCnt" => $lineItemCount,
            "prchrAcptcYn" => "N",
            "remark" => "",
            "regrId" => "ADMIN",
            "regrNm" => "ADMIN",
            "modrId" => "ADMIN",
            "modrNm" => "ADMIN",
            "saleCtyCd" => "1",
            "currencyTyCd" => $result['InvoiceSourceCurrency'],
            "exchangeRt" => $exchangeRt,
            "destnCountryCd" => $destnCountryCd,
            "dbtRsnCd" => $dbtRsnCd,
            "invcAdjustReason" => $invcAdjustReason,
        ];

        Log::debug("CreditDebitNoteRate ================ ".$result['CreditDebitNoteRate']);
        Log::debug("InvoiceRate ================ ".$result['InvoiceRate']);

        $zra_stock_item_array = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            'sarNo' =>  $result["CNUniquifier"],
            'orgSarNo' => $orgInvcNo,
            'regTyCd' => "M",
            'custTpin' => NULL,
            'custNm' => $result['BillTo'],
            'custBhfId' => NULL,
            'ocrnDt' => date('Ymd', strtotime($result['InvoiceDate'])),
            'regrId' => 'Admin',
            'regrNm' => 'Admin',
            'modrNm' => 'Admin',
            'modrId' => 'Admin',
            'remark' => $rcptTyCd=="D"?'Debit Note':'Credit Note',
            'sarTyCd' => $rcptTyCd=="D"?'11':'12',
        ];

        $zra_credit_note_array["custTpin"] = $sales_invoice->customerTPIN;
        $zra_stock_item_array["custTpin"] = $sales_invoice->customerTPIN;

        $taxblAmtA = 0.0;
        $taxblAmtB = 0.0;
        $taxblAmtC = 0.0;
        $taxblAmtC1 = 0.0;
        $taxblAmtC2 = 0.0;
        $taxblAmtC3 = 0.0;
        $taxblAmtD =0.0;
        $taxblAmtRvat =0.0;
        $taxblAmtE = 0.0;
        $taxblAmtF =  0.0;
        $taxblAmtIpl1 =  0.0;
        $taxblAmtIpl2 = 0.0;
        $taxblAmtTl = 0.0;
        $taxblAmtEcm = 0.0;
        $taxblAmtExeeg = 0.0;
        $taxblAmtTot = 0.0;

        $taxRtA = 0.0;
        $taxRtB = 0.0;
        $taxRtC1 = 0.0;
        $taxRtC2 = 0.0;
        $taxRtC3 = 0.0;
        $taxRtD = 0.0;
        $taxRtRvat = 0.0;
        $taxRtE = 0.0;
        $taxRtF = 0.0;
        $taxRtIpl1 = 0.0;
        $taxRtIpl2 = 0.0;
        $taxRtTl = 0.0;
        $taxRtEcm = 0.0;
        $taxRtExeeg = 0.0;
        $taxRtTot = 0.0;

        $taxAmtA = 0.0;
        $taxAmtB = 0.0;
        $taxAmtC = 0.0;
        $taxAmtC1 = 0.0;
        $taxAmtC2 = 0.0;
        $taxAmtC3 = 0.0;
        $taxAmtD = 0.0;
        $taxAmtRvat = 0.0;
        $taxAmtE = 0.0;
        $taxAmtF = 0.0;
        $taxAmtIpl1 = 0.0;
        $taxAmtIpl2 = 0.0;
        $taxAmtTl = 0.0;
        $taxAmtEcm = 0.0;
        $taxAmtExeeg = 0.0;

        $TotalTaxAmount = 0.0;
        $TotalTaxBase = 0.0;

        $item_count = 0;
        foreach($creditDebitDetails as $detail)
        {
            $vatCatCd = $exciseTxCatCd = $tlCatCd = $iplCatCd = $fCatCd = null;
            $vatTaxblAmt = $exciseTaxblAmt = $tlTaxblAmt = $iplTaxblAmt = $iplAmt = $tlAmt = $vatAmt = $exciseTxAmt = $fTaxblAmt =  $fAmt = 0.0;

            $taxCategoryCd= explode('-', $detail['TaxClass1Description']);
            $taxCategoryCd = strtoupper(trim($taxCategoryCd[0]));

            $detail['TaxAmount1'] = round($detail['TaxAmount1'], 4);
            $quantity = $detail['QuantityReturned'];
            if($result['CreditDebitNoteType'] == 'DebitNote') $quantity = $detail['QuantityReturned'];

            $taxCategoryCdTL = explode('-', $detail['TaxClass2Description']);
            $taxCategoryCdTL = trim($taxCategoryCdTL[0]);

            $UnitPrice = $detail['UnitPrice'];
            $totAmt = $detail['ExtendedDiscountedPrice'];

            if(($taxCategoryCd == 'TL') and ($taxCategoryCdTL == ''))
            {
                $tlCatCd = $taxCategoryCd;
                $item_price = $detail['UnitPrice'] * $detail['QuantityReturned'];

                $taxRtTl = round($detail['TaxRate1']);

                // 100 * 100/(100+1.5)
                $taxableAmount = $item_price * 100 / (100 + $taxRtTl);
                $tlTaxblAmt = $taxableAmount;
                $taxblAmtTl += $tlTaxblAmt;

                // taxblAmtTl * 1.5/100
                $tlAmt =  $tlTaxblAmt * ($taxRtTl/100);
                $taxAmtTl += $tlAmt;
            }
            elseif(($taxCategoryCd == 'TL') and ($taxCategoryCdTL != ''))
            {
                $vatCatCd = $taxCategoryCdVat = $taxCategoryCdTL;

                $tlCatCd = $taxCategoryCd;
                $item_price = $detail['UnitPrice'] * $detail['QuantityReturned'];

                // 100 * 100 / 117.5

                $taxRtA = $detail['TaxRate2'];
                $taxRtTl = round($detail['TaxRate1']);

                $taxableAmount = $item_price * 100 / (100 + ($taxRtA+$taxRtTl));
                $vatTaxblAmt = $taxableAmount;
                $taxblAmtA += $vatTaxblAmt;


                $tlTaxblAmt = $taxableAmount;
                $taxblAmtTl += $tlTaxblAmt;

                // taxable amount  * (1.5/100);
                $tlAmt =  $tlTaxblAmt * ($taxRtTl/100);
                $taxAmtTl += $tlAmt;

                // taxable amount  * (16/100);
                $vatAmt =  $vatTaxblAmt * ($taxRtA/100);;
                $taxAmtA += $vatAmt;
            }
            elseif(($taxCategoryCdTL == 'TL') and ($taxCategoryCd != ''))
            {
                $vatCatCd = $taxCategoryCdVat = $taxCategoryCd;

                $tlCatCd = $taxCategoryCdTL;
                $item_price = $detail['UnitPrice'] * $detail['QuantityReturned'];

                // 100 * 100 / 117.5

                $taxRtA = round($detail['TaxRate1']);
                $taxRtTl = $detail['TaxRate2'];

                $taxableAmount = $item_price * 100 / (100 + ($taxRtA+$taxRtTl));
                $vatTaxblAmt = $taxableAmount;
                $taxblAmtA += $vatTaxblAmt;


                $tlTaxblAmt = $taxableAmount;
                $taxblAmtTl += $tlTaxblAmt;

                // taxable amount  * (1.5/100);
                $tlAmt =  $tlTaxblAmt * ($taxRtTl/100);
                $taxAmtTl += $tlAmt;

                // taxable amount  * (16/100);
                $vatAmt =  $vatTaxblAmt * ($taxRtA/100);;
                $taxAmtA += $vatAmt;
            }
            elseif($taxCategoryCd == 'ECM' OR $taxCategoryCd == 'EXEEG')
            {
                $taxCategoryCdVat = explode('-', $detail['TaxClass2Description']);
                $taxCategoryCdVat = trim($taxCategoryCdVat[0]);

                $exciseTxCatCd = $taxCategoryCd;
                $item_price = $detail['UnitPrice'] * $detail['QuantityReturned'];

                $vatCatCd = $taxCategoryCdVat;
                if($taxCategoryCd == 'ECM')
                {
                    $taxRtEcm = round($detail['TaxRate1']);
                    // TaxBase2 * 100/105
                    $taxRtA = $detail['TaxRate2'];
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
                    $taxblAmtA += $vatTaxblAmt;
                    $exciseTaxblAmt = $vatTaxblAmt * 100 / (100 + $taxRtEcm);
                    $taxblAmtEcm += $exciseTaxblAmt;
                    // TaxBase2 - TaxBase2 * 100/105
                    $exciseTxAmt =  $vatTaxblAmt -  $taxblAmtEcm;
                    $taxAmtEcm += $exciseTxAmt;

                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    $taxAmtA += $vatAmt;
                }
                else
                {
                    $taxRtExeeg = round($detail['TaxRate1']);
                    // TaxBase2 * 100/105
                    $taxRtA = $detail['TaxRate2'];
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
                    $taxblAmtA += $vatTaxblAmt;
                    $exciseTaxblAmt = $vatTaxblAmt * 100 / (100 + $taxRtExeeg);
                    $taxblAmtExeeg += $exciseTaxblAmt;
                    // TaxBase2 - TaxBase2 * 100/105
                    $exciseTxAmt =  $vatTaxblAmt -  $taxblAmtExeeg;
                    $taxAmtExeeg  += $exciseTxAmt;

                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    $taxAmtA += $vatAmt;
                }
            }


            elseif($taxCategoryCd == 'F')
            {
                $fCatCd = $taxCategoryCd;
                $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

                $taxRtF = round($detail['TaxRate1']);
                $fTaxblAmt = $item_price * 100 / (100 + $taxRtF);
                $fAmt =  $item_price -  $fTaxblAmt;
                if($is_tax_exclusive)
                {
                    $fAmt = $item_price*($taxRtF / 100);
                    $taxblAmtF = $item_price;
                    $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtF / 100));
                    $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtF / 100));
                }
                $taxblAmtF += $fTaxblAmt;
                $taxAmtF += $fAmt;
            }
            elseif($taxCategoryCd == 'IPL1' or $taxCategoryCd == 'IPL2')
            {

                $iplCatCd = $taxCategoryCd;
                $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

                if($taxCategoryCd == 'IPL1')
                {
                    $taxRtIpl1 = round($detail['TaxRate1']);
                    $iplTaxblAmt = $item_price * 100 / (100 + $taxRtIpl1);
                    $iplAmt = $item_price -  $iplTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $iplAmt = $item_price*($taxRtIpl1 / 100);
                        $iplTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtIpl1 / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtIpl1 / 100));
                    }
                    $taxblAmtIpl1 += $iplTaxblAmt;
                    $taxAmtIpl1 += $iplAmt;
                }
                else
                {
                    $taxRtIpl2 =   round($detail['TaxRate1']);
                    $iplTaxblAmt = $item_price * 100 / (100 + $taxRtIpl2);
                    $iplAmt = $item_price -  $iplTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $iplAmt = $item_price*($taxRtIpl2 / 100);
                        $iplTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtIpl2 / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtIpl2 / 100));
                    }
                    $taxblAmtIpl2 += $iplTaxblAmt;
                    $taxAmtIpl2 += $iplAmt;
                }
            }
            elseif($taxCategoryCd == 'TOT')
            {
                $vatCatCd = $exciseTxCatCd = $tlCatCd = $iplCatCd = $fCatCd = null;
            }
            else
            {
                $vatCatCd = $taxCategoryCd;
                // $vatTaxblAmt =  $detail['TaxBase1'];
                // $vatAmt = $item_price*($taxRtA / 100);
                $item_price = $detail['UnitPrice'] * $quantity;

               
                if($taxCategoryCd == 'A')
                {
                    $taxRtA = round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
                    $vatAmt =  $item_price -  $vatTaxblAmt;

                    if($is_tax_exclusive)
                    {
                        $vatAmt = $item_price*($taxRtA / 100);
                        $vatTaxblAmt = $item_price;
                        // $totAmt = $detail['ExtendedDiscountedPrice'] * (1 + ($taxRtA / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtA / 100));
                        $totAmt = $UnitPrice * $quantity;
                    }
                    $taxblAmtA += $vatTaxblAmt;
                    $taxAmtA += $vatAmt;
                }
                elseif($taxCategoryCd == 'B')
                {
                    $taxRtB = round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtB);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $vatAmt = $item_price*($taxRtB / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtB / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtB / 100));
                        $totAmt = round($totAmt,2);
                        $UnitPrice = round($UnitPrice,2);
                    }
                    $taxblAmtB += $vatTaxblAmt;
                    $taxAmtB += $vatAmt;
                }
                elseif($taxCategoryCd == 'C1')
                {
                    $taxRtC1 =round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC1);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $vatAmt = $item_price*($taxRtC1 / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtC1 / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtC1 / 100));
                    }
                    $taxblAmtC1 += $vatTaxblAmt;
                    $taxAmtC1 += $vatAmt;
                }
                elseif($taxCategoryCd == 'C2')
                {
                    if(strlen($result['PurchaseOrderNumber']) > 1)
                    {
                        $zra_credit_note_array["lpoNumber"] = $result['PurchaseOrderNumber'];
                    }

                    $taxRtC2 =round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC2);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $vatAmt = $item_price*($taxRtC2 / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtC2 / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtC2 / 100));
                    }
                    $taxblAmtC2 += $vatTaxblAmt;
                    $taxAmtC2 += $vatAmt;
                }
                elseif($taxCategoryCd == 'C3')
                {
                    $taxRtC3 =round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC3);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $vatAmt = $item_price*($taxRtC3 / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtC3 / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtC3 / 100));
                    }
                    $taxblAmtC3 += $vatTaxblAmt;
                    $taxAmtC3 += $vatAmt;
                }
                elseif($taxCategoryCd == 'D')
                {
                    $taxRtD =round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtD);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $vatAmt = $item_price*($taxRtD / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtD / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtD / 100));
                    }
                    $taxblAmtD += $vatTaxblAmt;
                    $taxAmtD += $vatAmt;
                }
                elseif($taxCategoryCd == 'RVAT')
                {
                    $taxRtRvat =round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtRvat);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $vatAmt = $item_price*($taxRtRvat / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtRvat / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtRvat / 100));
                    }
                    $taxblAmtRvat += $vatTaxblAmt;
                    $taxAmtRvat += $vatAmt;
                }
                elseif($taxCategoryCd == 'E')
                {
                    $taxRtE =round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtE);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        $vatAmt = $item_price*($taxRtE / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $detail['ExtendedDiscountedPrice']* (1 + ($taxRtE / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtE / 100));
                    }
                    $taxblAmtE += $vatTaxblAmt;
                    $taxAmtE += $vatAmt;
                }
            }

            $pkgUnitCd = '';
            foreach($detail['CreditDebitDetailOptionalFields'] as $optField)
            {
                if($optField['OptionalField'] == 'PACKAGINGUNT') $pkgUnitCd = $optField['Value'];
            }
            $item_count += 1;
            $qtyUnitCd = $this->selectUnitOfmeasure($detail['InvoiceUnitOfMeasure']);

            $itemClsCd = InvoiceController::getItemUNCode($detail['Item']);

            $crnoteItem = [
                    "itemSeq" => $item_count,
                    "itemCd" => $detail['Item'],
                    "itemClsCd" => $itemClsCd,
                    "itemNm" => $detail['Description'],
                    "bcd" => "",
                    "pkgUnitCd" => $pkgUnitCd,
                    "pkg" => floatval($quantity),
                    "qtyUnitCd" => $qtyUnitCd,
                    "qty" => $quantity,
                    "prc" => round($UnitPrice, 4),
                    "splyAmt" => round(($UnitPrice * $quantity),4),
                    "dcRt" => $detail['PriceDiscountPercentage'],
                    "dcAmt" => $detail['PriceDiscountAmount'],
                    "isrccCd" => "",
                    "isrccNm" => "",
                    "isrcRt" => 0,
                    "isrcAmt" => 0.0,

                    "vatCatCd" => $vatCatCd,
                    "vatTaxblAmt" => round($vatTaxblAmt,4),
                    "vatAmt" => round($vatAmt,4),

                    "exciseTxCatCd" => $exciseTxCatCd,
                    "exciseTxAmt" => round($exciseTxAmt,4),
                    "exciseTaxblAmt" => round($exciseTaxblAmt,4),

                    "tlCatCd" => $tlCatCd,
                    "tlTaxblAmt" => round($tlTaxblAmt,4),
                    "tlAmt" => round($tlAmt,4),

                    "fCatCd" => $fCatCd,
                    "fTaxblAmt" => round($fTaxblAmt,4),
                    "fAmt" => round($fAmt,4),

                    "iplCatCd" => $iplCatCd,
                    "iplTaxblAmt" => round($iplTaxblAmt,4),
                    "iplAmt" => round($iplAmt,4),

                    "totAmt" => round($totAmt,4),
                ];

            $zra_credit_note_array['itemList'][] = $crnoteItem;

            $stock_itemS = [
                "itemSeq" => $item_count,
                "itemCd"=> $detail['Item'],
                "itemClsCd"=> $itemClsCd,
                "itemNm"=> $detail['Description'],
                "bcd"=> NULL,
                "pkg" => floatval($quantity),
                "pkgUnitCd"=> $pkgUnitCd,
                "qtyUnitCd" => $qtyUnitCd,
                "qty"=> $quantity,
                "itemExprDt"=> NULL,
                "totDcAmt"=> $detail['PriceDiscountAmount'],
                "iplCatCd"=> $iplCatCd,
                "tlCatCd"=> $tlCatCd,
                "exciseTxCatC"=> $exciseTxCatCd,
                "prc"=> round($UnitPrice,2),
                "splyAmt" => round(($UnitPrice * $quantity),2),
                "iplAmt"=> round($iplAmt,2),
                "tlAmt"=> round($tlAmt,2),
                "exciseTxAmt"=> round($exciseTaxblAmt,2),
                "taxblAmt"=> round($tlTaxblAmt,2),
                "vatCatCd"=> $vatCatCd,
                "taxAmt"=> round(($vatAmt+$iplAmt+$tlAmt+$exciseTxAmt+$fAmt),2),
                "totAmt"=> round($totAmt,2)
            ];
            $zra_stock_item_array["itemList"][] = $stock_itemS;
        }

        $zra_credit_note_array['taxblAmtA'] = round($taxblAmtA,4);
        $zra_credit_note_array['taxblAmtB'] = round($taxblAmtB,4);
        $zra_credit_note_array['taxblAmtC'] = round($taxblAmtC,4);
        $zra_credit_note_array['taxblAmtC1'] = round($taxblAmtC1,4);
        $zra_credit_note_array['taxblAmtC2'] = round($taxblAmtC2,4);
        $zra_credit_note_array['taxblAmtC3'] = round($taxblAmtC3,4);
        $zra_credit_note_array['taxblAmtD'] = round($taxblAmtD,4);
        $zra_credit_note_array['taxblAmtRvat'] = round($taxblAmtRvat,4);
        $zra_credit_note_array['taxblAmtE'] = round($taxblAmtE,4);
        $zra_credit_note_array['taxblAmtF'] = round($taxblAmtF,4);
        $zra_credit_note_array['taxblAmtIpl1'] =round($taxblAmtIpl1,4);
        $zra_credit_note_array['taxblAmtIpl2'] = round($taxblAmtIpl2,4);
        $zra_credit_note_array['taxblAmtTl'] = round($taxblAmtTl,4);
        $zra_credit_note_array['taxblAmtEcm'] = round($taxblAmtEcm,4);
        $zra_credit_note_array['taxblAmtExeeg'] = round($taxblAmtExeeg,4);
        $zra_credit_note_array['taxblAmtTot'] = round($taxblAmtTot,4);

        //TODO we got to dynamically get these
        $zra_credit_note_array['taxRtA'] = 16;//$taxRtA;
        $zra_credit_note_array['taxRtB'] = 16;//$taxRtB;
        $zra_credit_note_array['taxRtC1'] = $taxRtC1;
        $zra_credit_note_array['taxRtC2'] = $taxRtC2;
        $zra_credit_note_array['taxRtC3'] = $taxRtC3;
        $zra_credit_note_array['taxRtD'] = $taxRtD;
        $zra_credit_note_array['taxRtRvat'] = 16;//$taxRtRvat;
        $zra_credit_note_array['taxRtE'] = $taxRtE;
        $zra_credit_note_array['taxRtF'] = 10;//$taxRtF;
        $zra_credit_note_array['taxRtIpl1'] = 5;//$taxRtIpl1;
        $zra_credit_note_array['taxRtIpl2'] = $taxRtIpl2;
        $zra_credit_note_array['taxRtTl'] = 1.5;//$taxRtTl;
        $zra_credit_note_array['taxRtEcm'] = 5;//$taxRtEcm;
        $zra_credit_note_array['taxRtExeeg'] = 3;//$taxRtExeeg;
        $zra_credit_note_array['taxRtTot'] = $taxRtTot;

        $zra_credit_note_array['taxAmtA'] =  round($taxAmtA,4);
        $zra_credit_note_array['taxAmtB'] =  round($taxAmtB,4);
        $zra_credit_note_array['taxAmtC'] =  round($taxAmtC,4);
        $zra_credit_note_array['taxAmtC1'] =  round($taxAmtC1,4);
        $zra_credit_note_array['taxAmtC2'] =  round($taxAmtC2,4);
        $zra_credit_note_array['taxAmtC3'] =  round($taxAmtC3,4);
        $zra_credit_note_array['taxAmtD'] =  round($taxAmtD,4);
        $zra_credit_note_array['taxAmtRvat'] =  round($taxAmtRvat,4);
        $zra_credit_note_array['taxAmtE'] =  round($taxAmtE,4);
        $zra_credit_note_array['taxAmtF'] =  round($taxAmtF,4);
        $zra_credit_note_array['taxAmtIpl1'] =  round($taxAmtIpl1,4);
        $zra_credit_note_array['taxAmtIpl2'] =  round($taxAmtIpl2,4);
        $zra_credit_note_array['taxAmtTl'] =  round($taxAmtTl,4);
        $zra_credit_note_array['taxAmtEcm'] =  round($taxAmtEcm,4);
        $zra_credit_note_array['taxAmtExeeg'] =  round($taxAmtExeeg,4);
        $zra_credit_note_array['taxAmtTot'] =  0.0;


        $TotalTaxAmount =  $taxAmtA + $taxAmtB +  $taxAmtC + $taxAmtC1 + $taxAmtC2 + $taxAmtC3 + $taxAmtD + $taxAmtRvat + $taxAmtE + $taxAmtF + $taxAmtIpl1 + $taxAmtIpl2 + $taxAmtTl + $taxAmtEcm + $taxAmtExeeg;
        $TotalTaxBase =  $taxblAmtA + $taxblAmtB + $taxblAmtC + $taxblAmtC1 + $taxblAmtC2+ $taxblAmtC3+ $taxblAmtD + $taxblAmtRvat + $taxblAmtE+ $taxblAmtF +$taxblAmtIpl1 +$taxblAmtIpl2+$taxblAmtTl+$taxblAmtEcm+$taxblAmtExeeg;
        $TotAmount  = $TotalTaxAmount + $TotalTaxBase;

        $zra_credit_note_array["totTaxAmt"] = round($TotalTaxAmount,4);
        $zra_credit_note_array["totTaxblAmt"] = round($TotalTaxBase,4);
        //$zra_credit_note_array["totTaxblAmt"] = round(($result['CreditDebitNoteAmountDue']-$TotalTaxAmount),4);
        // $zra_credit_note_array["totAmt"]  = round($result['CreditDebitNoteAmountDue'],4);
        $zra_credit_note_array["totAmt"]  = round($TotAmount,4);

        //$zra_stock_item_array["totTaxblAmt"] = round(($result['CreditDebitNoteAmountDue']-$TotalTaxAmount),2);
        $zra_stock_item_array["totTaxblAmt"] = round($TotalTaxBase,2);
        $zra_stock_item_array["totTaxAmt"] = round($TotalTaxAmount,2);
        // $zra_stock_item_array["totAmt"] = round(($result['CreditDebitNoteAmountDue']),2);
        $zra_stock_item_array["totAmt"]  = round($TotAmount,2);

        $zra_stock_item_array["totItemCnt"] = $lineItemCount;

        // $response = Http::post($zra_base_url."trnsSales/saveSales", $zra_credit_note_array);
        $response = Http::timeout(720)->post($zra_base_url . "trnsSales/saveSales", $zra_credit_note_array);
        $result = $response->json();

        if($response->failed()){
            Log::alert('***ZRA Call Failed to be processed offline****');
            $offline_trans = new OfflineTransaction();
            $offline_trans->endpoint = "trnsSales/saveSales";
            $offline_trans->payload = json_encode($zra_credit_note_array,true);
            $offline_trans->save();

            $offline_trans = new OfflineTransaction();
            $offline_trans->endpoint = "stock/saveStockItems";
            $offline_trans->payload = json_encode($zra_stock_item_array,true);
            $offline_trans->save();

            return;
        }
        ConfigController::LogDeviceData("/trnsSales/saveSales", $zra_credit_note_array, $result);

        Log::info($result);
        Log::info(json_encode($zra_credit_note_array));

        //update sales_invoice tables
        $crn->resultCd = $result['resultCd'];
        $crn->resultMsg = $result['resultMsg'];
        // CIS Invoice number already exists
        if($result['resultCd'] == '000')
        {
            $crn->status = 'Interfaced';
            // StockItemController::interfaceStockItem($zra_stock_item_array);
            UpdateSalesInvoiceStocks::dispatch($zra_stock_item_array);
        }
        elseif($result['resultCd'] == '910')
        {
            if((int)strpos($result['resultMsg'],"custTpin")>0)
            {
                $this-> retryInterfacingCRN($zra_credit_note_array,$zra_stock_item_array, $zra_base_url, $crn);
            }
        }
        $crn->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
        if(!empty($result['data']))
        {
            $crn->rcptNo = $result['data']['rcptNo'];
            $crn->intrlData = $result['data']['intrlData'];
            $crn->rcptSign = $result['data']['rcptSign'];
            $crn->vsdcRcptPbctDate = $result['data']['vsdcRcptPbctDate'];
            $crn->sdcId = $result['data']['sdcId'];
            $crn->mrcNo = $result['data']['mrcNo'];
            $crn->qrCodeUrl = $result['data']['qrCodeUrl'];
        }
        $crn->save();

        return response()->json($result);
    }

    public static function retryInterfacingCRN($zra_credit_note_array,$zra_stock_item_array, $zra_base_url, $crn)
    {
        $zra_credit_note_array["custTpin"] = null;
        $zra_stock_item_array["custTpin"] = null;

        $response = Http::post($zra_base_url."trnsSales/saveSales", $zra_credit_note_array);
        $result = $response->json();
        ConfigController::LogDeviceData("/trnsSales/saveSales", $zra_credit_note_array, $result);
        Log::info($result);
        Log::info(json_encode($zra_credit_note_array));


        if($result['resultCd'] == '000')
        {
            $crn->status = 'Interfaced';
            $crn->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
            if(!empty($result['data']))
            {
                $crn->rcptNo = $result['data']['rcptNo'];
                $crn->intrlData = $result['data']['intrlData'];
                $crn->rcptSign = $result['data']['rcptSign'];
                $crn->vsdcRcptPbctDate = $result['data']['vsdcRcptPbctDate'];
                $crn->sdcId = $result['data']['sdcId'];
                $crn->mrcNo = $result['data']['mrcNo'];
                $crn->qrCodeUrl = $result['data']['qrCodeUrl'];
            }
            $crn->save();
            // StockItemController::interfaceStockItem($zra_stock_item_array);
            UpdateSalesInvoiceStocks::dispatch($zra_stock_item_array);
        }
    }

    public function checkSageUninterfacedCreditNotes(Request $request)
    {
        try {
            $sage_base_url = env('SAGE_URL');
            $sageUser = env('SAGE_USER');
            $sagePwd = env('SAGE_PASSWORD');

            $limit = $request->query('limit', 100);
            $filter = '$top=' . $limit . '&$orderby=PostingDate desc&$select=CNUniquifier';

            $response = Http::timeout(720)->withBasicAuth($sageUser, $sagePwd)
                ->get("$sage_base_url/OE/OECreditDebitNotes?$filter");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch credit notes from Sage 300. HTTP ' . $response->status()
                ], 500);
            }

            $result = $response->json();
            $creditNotes = $result['value'] ?? [];

            $total = count($creditNotes);
            $alreadyInterfaced = 0;
            $newlyInterfaced = 0;
            $failed = 0;
            $results = [];

            Log::info("Starting Sage uninterfaced check for {$total} credit notes");

            foreach ($creditNotes as $count => $row) {
                $creditDebitNoteID = $row['CNUniquifier'];
                $creditNote = CreditNote::where('credit_note_id', $creditDebitNoteID)->first();

                if ($creditNote) {
                    $alreadyInterfaced++;
                    $results[] = [
                        'credit_note_id' => $creditDebitNoteID,
                        'status' => 'Already Interfaced',
                        'message' => 'Credit note already exists in system'
                    ];
                    Log::info(($count + 1) . ' - Credit note already interfaced ' . $creditDebitNoteID);
                    continue;
                }

                Log::info(($count + 1) . ' - Credit note not interfaced ' . $creditDebitNoteID);

                try {
                    $creditNoteResponse = Http::timeout(30)->withBasicAuth($sageUser, $sagePwd)
                        ->get("$sage_base_url/OE/OECreditDebitNotes($creditDebitNoteID)");
                    $uninterfacedResult = $creditNoteResponse->json();
                    $customerNumber = $uninterfacedResult['CustomerNumber'] ?? '';

                    if (empty($customerNumber)) {
                        $failed++;
                        $results[] = [
                            'credit_note_id' => $creditDebitNoteID,
                            'status' => 'Failed',
                            'message' => 'Customer number not found'
                        ];
                        continue;
                    }

                    $customerResponse = Http::timeout(30)->withBasicAuth($sageUser, $sagePwd)
                        ->get("$sage_base_url/AR/ARCustomers('$customerNumber')");
                    $customerResult = $customerResponse->json();
                    $customerTPIN = $customerResult['BusinessRegistrationNumber'] ?? '';

                    Log::info("Processing credit note {$creditDebitNoteID} with TPIN: {$customerTPIN}");

                    $sendResponse = Http::timeout(120)->get(env('APP_URL') . "/api/send-credit-note", [
                        'creditNoteID' => $creditDebitNoteID,
                        'customerTPIN' => $customerTPIN
                    ]);

                    if ($sendResponse->successful()) {
                        $sendResult = $sendResponse->json();
                        $resultMsg = $sendResult['resultMsg'] ?? 'No message';

                        if (isset($sendResult['resultCd']) && $sendResult['resultCd'] == '000') {
                            $newlyInterfaced++;
                            $results[] = [
                                'credit_note_id' => $creditDebitNoteID,
                                'status' => 'Interfaced',
                                'message' => $resultMsg
                            ];
                        } else {
                            $failed++;
                            $results[] = [
                                'credit_note_id' => $creditDebitNoteID,
                                'status' => 'Failed',
                                'message' => $resultMsg
                            ];
                        }
                    } else {
                        $failed++;
                        $results[] = [
                            'credit_note_id' => $creditDebitNoteID,
                            'status' => 'Failed',
                            'message' => 'HTTP ' . $sendResponse->status()
                        ];
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $results[] = [
                        'credit_note_id' => $creditDebitNoteID,
                        'status' => 'Failed',
                        'message' => $e->getMessage()
                    ];
                    Log::error("Error processing credit note {$creditDebitNoteID}: " . $e->getMessage());
                }
            }

            Log::info("Sage uninterfaced credit note check completed. Total: {$total}, Already: {$alreadyInterfaced}, New: {$newlyInterfaced}, Failed: {$failed}");

            return response()->json([
                'success' => true,
                'total' => $total,
                'processed' => $total,
                'already_interfaced' => $alreadyInterfaced,
                'newly_interfaced' => $newlyInterfaced,
                'failed' => $failed,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Error in checkSageUninterfacedCreditNotes: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchPendingCreditNotes(Request $request)
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $exclude_array = CreditNote::where('sage_crn_number', 'LIKE', '%-CRN%')->pluck('id')->toArray();

            $query = CreditNote::whereNotIn('id', $exclude_array)
                ->where('status', 'Pending');

            if ($startDate) {
                $query->whereDate('updated_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('updated_at', '<=', $endDate);
            }

            $creditNotes = $query->orderBy('updated_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $creditNotes
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching pending credit notes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending credit notes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkReinterfaceCRDN(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No credit note IDs provided.'
                ], 400);
            }

            Log::info('Starting bulk reinterface for ' . count($ids) . ' credit notes');

            $processed = 0;
            $failed = 0;

            foreach ($ids as $id) {
                try {
                    $crn = CreditNote::find($id);

                    if (!$crn) {
                        Log::warning("Credit note with ID {$id} not found");
                        $failed++;
                        continue;
                    }

                    if ($crn->status !== 'Pending') {
                        Log::info("Credit note {$crn->sage_crn_number} is not pending, skipping");
                        continue;
                    }

                    Log::info("Processing credit note: {$crn->sage_crn_number}");

                    $response = Http::timeout(120)->get(env('APP_URL') . "/api/send-credit-note", [
                        'creditNoteID' => $crn->credit_note_id,
                        'customerTPIN' => $crn->customerTPIN
                    ]);

                    if ($response->successful()) {
                        $result = $response->json();
                        Log::info("Credit note {$crn->sage_crn_number} processed: " . ($result['resultMsg'] ?? 'No message'));
                        $processed++;
                    } else {
                        Log::error("Failed to process credit note {$crn->sage_crn_number}: HTTP " . $response->status());
                        $failed++;
                    }

                } catch (\Exception $e) {
                    Log::error("Error processing credit note ID {$id}: " . $e->getMessage());
                    $failed++;
                    continue;
                }
            }

            Log::info("Bulk reinterface completed. Processed: {$processed}, Failed: {$failed}");

            return response()->json([
                'success' => true,
                'message' => "Bulk reinterface completed. Processed: {$processed}, Failed: {$failed}"
            ]);
        } catch (\Exception $e) {
            Log::error('Error in bulk reinterface: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete bulk reinterface: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pullSageUninterfacedCreditNote(Request $request)
    {
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $lookupType = $request->input('lookup_type', 'CNUniquifier');
        $lookupValue = trim((string) $request->input('lookup_value', $request->input('CNUniquifier', '')));

        if ($lookupValue === '') {
            return redirect()->back()->with('error', 'Please provide a credit note value to search.');
        }

        $CreditDebitNoteID = $lookupValue;

        if ($lookupType === 'CreditDebitNoteNumber') {
            $escapedLookupValue = str_replace("'", "''", $lookupValue);
            $lookupResponse = Http::withBasicAuth($sageUser, $sagePwd)->get(
                "$sage_base_url/OE/OECreditDebitNotes",
                [
                    '$filter' => "CreditDebitNoteNumber eq '{$escapedLookupValue}'",
                    '$select' => 'CNUniquifier,CreditDebitNoteNumber',
                    '$top' => 1,
                ]
            );

            $lookupResult = $lookupResponse->json();
            $matchedCreditNote = $lookupResult['value'][0] ?? null;

            if (!$lookupResponse->successful() || !$matchedCreditNote || empty($matchedCreditNote['CNUniquifier'])) {
                return redirect()->back()->with('error', 'Credit note number not found in Sage 300.');
            }

            $CreditDebitNoteID = $matchedCreditNote['CNUniquifier'];
        }

        Log::info("**********÷**CREDIT NOTE NUMBER: ".$CreditDebitNoteID);

        //get the credit note to get the customer bpn
        $response = Http::withBasicAuth($sageUser, $sagePwd)->get("$sage_base_url/OE/OECreditDebitNotes($CreditDebitNoteID)");
        $uniterfaced_result = $response->json();

        if(isset($uniterfaced_result['error']))
        {
            return redirect()->back()->with('error', $uniterfaced_result['error']['code']);
        }

        $CustomerNumber = $uniterfaced_result['CustomerNumber'];
        Log::info('CustomerNumber - '.$CustomerNumber);

        //get the tpin
        $customerTPIN = '';
        $response = Http::withBasicAuth($sageUser, $sagePwd)->get("$sage_base_url/AR/ARCustomers('$CustomerNumber')");
        $customer_result = $response->json();
        $customerTPIN = $customer_result['BusinessRegistrationNumber'];
        Log::info('Customer TPIN - '.$customerTPIN);

        try
        {
            $response = Http::get(env('APP_URL') . "/api/send-credit-note", [
                'creditNoteID' => $CreditDebitNoteID,
                'customerTPIN' => $customerTPIN
            ]);

            return redirect()->back()->with('success', $response['resultMsg'] ?? 'Credit Note sent for interfacing');
        }
        catch(Exception $e)
        {
            Log::error($e);
        }           
        
        return "Credit Note not sent for interfacing";
    }
}
