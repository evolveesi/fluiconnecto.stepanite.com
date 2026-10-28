<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Core\ConfigController;
use App\Invoice;
use App\Settings;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\OfflineTransaction;
use App\SalesInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use TCPDF;
use App\Jobs\UpdateSalesInvoiceStocks;
use Carbon\Carbon;

class InvoiceController extends Controller
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

    public function index()
    {
        // $data = SalesInvoice::orderBy('updated_at','desc')->limit(500)->get();
        $data = SalesInvoice::where('created_at', '>=', Carbon::now()->subMonths(3))
                            ->orderBy('created_at', 'desc')
                            ->get();
        return view('pages.invoices.index',['data'=>$data]);
    }

    public function reinterfaceInvoice(Request $request)
    {
        $sales_invoice  = SalesInvoice::find($request->id);
        $buyerTPin = $sales_invoice->customerTPIN;
        $InvoiceID = $sales_invoice->invoice_id;

        $response = Http::get(env('APP_URL') . "/api/send-invoice", [
            'InvoiceID' => $InvoiceID,
            'customerTPIN' => $buyerTPin
        ]);
  
        $result = $response->json();

        if ($response->successful()) {
            $sales_invoice  = SalesInvoice::where('invoice_id',$InvoiceID)->get();
            if((count($sales_invoice)) and ($result['resultCd'] != '000'))
            {
                $truncated_msg = $this->truncate_text($result['resultMsg']);
                $sales_invoice = $sales_invoice[0];
                $sales_invoice->resultCd = $result['resultCd'];
                $sales_invoice->resultMsg = $truncated_msg;
                $sales_invoice->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
                $sales_invoice->save();
            }
            $msg = $result['resultMsg'];
            return redirect()->back()->with('status', $msg);
        } else {
            $msg =  response()->json(['error' => 'Failed to send invoice'], $response->status());
            return redirect()->back()->with('error', $msg);
        }

    }

    function truncate_text($text, $length = 250) {
        return substr($text, 0, $length);
    }

    public function checkSageUninterfacedInvoices()
    {
        $sage_base_url = rtrim((string) config('services.sage.url'), '/');
        $sageUser = config('services.sage.user');
        $sagePwd = config('services.sage.password');
        $timeout = max(1, (int) config('services.sage.timeout', 300));

        if ($sage_base_url === '' || !$sageUser || !$sagePwd) {
            return response()->json(['success' => false, 'message' => 'Sage API is not configured.'], 503);
        }

        $sage = Http::withBasicAuth($sageUser, $sagePwd)->timeout($timeout);

        $filter = '$top=100&$orderby=PostingDate desc&$select=InvoiceUniquifier';
        $processed = 0;
        $stage = 'Sage';

        try {
            $response = $sage->get("$sage_base_url/OE/OEInvoices?$filter");
            if (!$response->successful() || !is_array($response->json('value'))) {
                Log::warning('Sage invoice list request failed', ['status' => $response->status()]);
                return response()->json(['success' => false, 'message' => 'Could not fetch the invoice list from Sage.'], 502);
            }

            foreach ($response->json('value') as $count => $row) {
                $InvoiceID = $row['InvoiceUniquifier'] ?? null;
                if (!$InvoiceID) {
                    continue;
                }

                if (SalesInvoice::where('invoice_id', $InvoiceID)->exists()) {
                    Log::info(($count + 1).' - Invoice already interfaced '.$InvoiceID);
                    continue;
                }

                Log::info(($count + 1).' - Invoice not interfaced '.$InvoiceID);
                $invoiceResponse = $sage->get("$sage_base_url/OE/OEInvoices($InvoiceID)");
                $CustomerNumber = $invoiceResponse->json('CustomerNumber');
                if (!$invoiceResponse->successful() || !$CustomerNumber) {
                    Log::warning('Sage invoice detail request failed', ['invoice_id' => $InvoiceID, 'status' => $invoiceResponse->status()]);
                    return response()->json(['success' => false, 'message' => 'Could not fetch invoice details from Sage.', 'processed' => $processed], 502);
                }

                $customerResponse = $sage->get("$sage_base_url/AR/ARCustomers('$CustomerNumber')");
                $customerTPIN = $customerResponse->json('BusinessRegistrationNumber');
                if (!$customerResponse->successful() || !$customerTPIN) {
                    Log::warning('Sage customer request failed', ['invoice_id' => $InvoiceID, 'status' => $customerResponse->status()]);
                    return response()->json(['success' => false, 'message' => 'Could not fetch customer details from Sage.', 'processed' => $processed], 502);
                }

                $stage = 'invoice service';
                $sendResponse = Http::timeout($timeout)->get(config('app.url') . '/api/send-invoice', [
                    'InvoiceID' => $InvoiceID,
                    'customerTPIN' => $customerTPIN,
                ]);
                if (!$sendResponse->successful()) {
                    Log::warning('Invoice interfacing request failed', ['invoice_id' => $InvoiceID, 'status' => $sendResponse->status()]);
                    return response()->json(['success' => false, 'message' => 'Could not interface an invoice.', 'processed' => $processed], 502);
                }
                $processed++;
                $stage = 'Sage';
            }

            return response()->json(['success' => true, 'message' => 'Sage invoice check completed.', 'processed' => $processed]);
        } catch (ConnectionException $e) {
            Log::warning('Invoice fetch timed out or could not connect', ['stage' => $stage, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $stage === 'Sage'
                    ? 'Sage did not respond in time. Please try again later.'
                    : 'The invoice service did not respond in time. Please try again later.',
                'processed' => $processed,
            ], 503);
        }
    }
    public function checkSageUninterfacedInvoiceUniquifier($invoiceUniquifier)
    {
        $InvoiceID = trim((string) $invoiceUniquifier);

        if ($InvoiceID === '') {
            return response()->json([
                'success' => false,
                'message' => 'InvoiceUniquifier is required.'
            ], 422);
        }

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $sales_invoice  = SalesInvoice::where('invoice_id',$InvoiceID)->get();

        if (count($sales_invoice))
        {
            Log::info('1 - '.'Invoice already interfaced '.$InvoiceID);

            return response()->json([
                'success' => true,
                'invoice_id' => $InvoiceID,
                'status' => 'Already Interfaced',
                'message' => 'Invoice already exists in system'
            ]);
        }
        else
        {
            Log::info('1 - '.'Invoice not interfaced '.$InvoiceID);

            $response = Http::withBasicAuth($sageUser, $sagePwd)->get("$sage_base_url/OE/OEInvoices($InvoiceID)");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'invoice_id' => $InvoiceID,
                    'status' => 'Failed',
                    'message' => 'Failed to fetch invoice from Sage. HTTP ' . $response->status()
                ], 500);
            }

            $uniterfaced_result = $response->json();

            if (isset($uniterfaced_result['error'])) {
                return response()->json([
                    'success' => false,
                    'invoice_id' => $InvoiceID,
                    'status' => 'Failed',
                    'message' => $uniterfaced_result['error']['code'] ?? 'Invoice lookup failed in Sage.'
                ], 500);
            }

            $CustomerNumber = $uniterfaced_result['CustomerNumber'] ?? '';
            Log::info('CustomerNumber - '.$CustomerNumber);

            Log::info('***********sage invoice result*******');
            Log::info(json_encode($uniterfaced_result,true));

            $customerTPIN = '';
            $response = Http::withBasicAuth($sageUser, $sagePwd)->get("$sage_base_url/AR/ARCustomers('$CustomerNumber')");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'invoice_id' => $InvoiceID,
                    'status' => 'Failed',
                    'message' => 'Failed to fetch customer from Sage. HTTP ' . $response->status()
                ], 500);
            }

            $customer_result = $response->json();
            Log::info('***********customer result*******');
            Log::info(json_encode($customer_result,true));

            $customerTPIN = $customer_result['BusinessRegistrationNumber'] ?? '';
            Log::info('Customer TPIN - '.$customerTPIN);

            try
            {
                $response = Http::get(env('APP_URL') . "/api/send-invoice", [
                    'InvoiceID' => $InvoiceID,
                    'customerTPIN' => $customerTPIN
                ]);

                return response()->json([
                    'success' => $response->successful(),
                    'invoice_id' => $InvoiceID,
                    'status' => $response->successful() ? 'Processed' : 'Failed',
                    'message' => $response->json()['resultMsg'] ?? 'Invoice processed',
                    'data' => $response->json()
                ], $response->successful() ? 200 : 500);
            }
            catch(Exception $e)
            {
                Log::error($e);

                return response()->json([
                    'success' => false,
                    'invoice_id' => $InvoiceID,
                    'status' => 'Failed',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
    }
    public function checkSageUninterfacedInvoice($InvoiceNumberAPI)
    {
        // GET /sage300webapi/v1.0/OEINV/Invoices?$filter=DocDate eq {today}
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $filter = '?$filter = InvoiceNumber eq '.$InvoiceNumberAPI;

        $response = Http::withBasicAuth($sageUser, $sagePwd)->get("$sage_base_url/OE/OEInvoices?$filter");
        Log::info($response->json());
        $result = $response->json();
        $row = $result['value'][0];
        
      
            $InvoiceID = $row['InvoiceUniquifier'];
            $sales_invoice  = SalesInvoice::where('invoice_id',$InvoiceID)->get();

            if (count($sales_invoice))
            {
                Log::info($InvoiceNumberAPI.' - '.'Invoice already interfaced '.$InvoiceID);
            }
            else
            {
                Log::info($InvoiceNumberAPI.' - '.'Invoice not interfaced '.$InvoiceID);

                //get the invoice to get the customer bpn
                $response = Http::withBasicAuth($sageUser, $sagePwd)->get("$sage_base_url/OE/OEInvoices($InvoiceID)");
                $uniterfaced_result = $response->json();
                $CustomerNumber = $uniterfaced_result['CustomerNumber'];
                Log::info('CustomerNumber - '.$CustomerNumber);

                //get the tpin
                // $customerTPIN = '1001862964';
                $response = Http::withBasicAuth($sageUser, $sagePwd)->get("$sage_base_url/AR/ARCustomers('$CustomerNumber')");
                $customer_result = $response->json();
                $customerTPIN = $customer_result['BusinessRegistrationNumber'];
                Log::info('Customer TPIN - '.$customerTPIN);

                try
                {
                    $response = Http::get(env('APP_URL') . "/api/send-invoice", [
                        'InvoiceID' => $InvoiceID,
                        'customerTPIN' => $customerTPIN
                    ]);
                }
                catch(Exception $e)
                {
                    Log::error($e);
                }
            }
        

        return 1;
    }
    public function getPrintData($sage_invoice_number)
    {
        $sales_invoice  = SalesInvoice::where('sage_invoice_number',$sage_invoice_number)->get();

        if ( !count($sales_invoice) )
        {
            App::abort(404);
        }
        else
        {
            $sales_invoice = $sales_invoice[0];
        }
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');
        $InvoiceID = $sales_invoice->invoice_id;
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/OE/OEInvoices($InvoiceID)");
        $result = $response->json();
        // Log::info($result);

        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $buyerTPin = $sales_invoice->customerTPIN;

        $VSDCDate = substr($sales_invoice->resultDt, 0, 8);
        $VSDCTime = substr($sales_invoice->resultDt, 8);

        $data = [
            'VSDCDate' => $VSDCDate,
            'VSDCTime' => $VSDCTime,
            'VSDCID' => $sales_invoice->sdcId,
            'VSDCInvoiceNumber' => $sales_invoice->rcptNo,
            'VSDCInvoiceType' => 'Normal Sale',
            'VSDCInternalData' => $sales_invoice->intrlData,
            'VSDCSignature' => $sales_invoice->rcptSign,
            'VSDCURL' => $sales_invoice->qrCodeUrl,
            'tPin'=>$tinNumber,
            'PONumber'=>$result['PurchaseOrderNumber'],
            'buyerTPin'=>$buyerTPin,
            'invoice_id'=>$sage_invoice_number,
            "BillTo" => $result["BillTo"],
            "BillToAddress1" => $result["BillToAddress1"],
            "BillToAddress2" => $result["BillToAddress2"],
            "BillToAddress3" => $result["BillToAddress3"],
            "BillToAddress4" => $result["BillToAddress4"],
            "BillToCity" => $result["BillToCity"],
            "BillToCountry" => $result["BillToCountry"],
            "BillToPhone" => $result["BillToPhone"],
            'OrderNumber' => $result['OrderNumber'],
            'InvoiceDate' => date('d-M-Y', strtotime($result['InvoiceDate'])),
            'Comment' => $result['Comment'],
            'InvoiceSourceCurrency' => $result['InvoiceSourceCurrency'],
            'InvoiceRate' => $result['InvoiceRate'],
            'Reference' => $result['Reference'] ?? '',
        ];

        $data['items'] = [];
        $taxes = [];
        $TotalTaxAmount = 0;

        foreach($result['InvoiceDetails'] as $detail)
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
                'QuantityShipped' => $detail['QuantityShipped'],
                'Discount' => number_format($detail['PriceDiscountAmount'],2),
                'TotalAmount' => number_format($detail['DetailAmountDue'],2),
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
        $data['InvoiceRunningTotal'] = number_format($result['InvoiceTotalWithTax'],2);

        return $data;

    }

    public function printInvoice($sage_invoice_number)
    {
        $data = $this->getPrintData($sage_invoice_number);
        $data['isreprint'] = false;

        return view('pages.invoices.print',['data'=>$data]);
    }

    public function printInvoiceMultiSDC($sage_invoice_number)
    {
        $data = $this->getPrintData($sage_invoice_number);
        $data['isreprint'] = false;

        return view('pages.invoices.print-multi-sdc', ['data' => $data]);
    }

    public function reprintInvoice($sage_invoice_number)
    {
        $data = $this->getPrintData($sage_invoice_number);
        $data['isreprint'] = true;

        return view('pages.invoices.print',['data'=>$data]);
    }

    public function splitAndSendInvoice(Request $request)
    {
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);

        $invoiceNumber = trim((string) ($request->query('InvoiceNumber') ?? $request->query('invoiceNumber') ?? $request->input('InvoiceNumber') ?? $request->input('invoiceNumber')));

        if ($invoiceNumber === '') {
            return response()->json([
                'success' => false,
                'message' => 'InvoiceNumber is required.'
            ], 422);
        }

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $sageInvoiceNumber = str_replace("'", "''", $invoiceNumber);

        $response = Http::withBasicAuth($sageUser, $sagePwd)
            ->timeout(3600)
            ->get("$sage_base_url/OE/OEInvoices", [
                '$filter' => "InvoiceNumber eq '$sageInvoiceNumber'"
            ]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'invoice_number' => $invoiceNumber,
                'message' => 'Failed to fetch invoice from Sage. HTTP ' . $response->status()
            ], 500);
        }

        $invoiceSearchResult = $response->json();
        $sageInvoice = $invoiceSearchResult['value'][0] ?? null;

        if (!$sageInvoice) {
            return response()->json([
                'success' => false,
                'invoice_number' => $invoiceNumber,
                'message' => 'Invoice was not found in Sage.'
            ], 404);
        }

        $InvoiceID = $sageInvoice['InvoiceUniquifier'];

        $response = Http::withBasicAuth($sageUser, $sagePwd)
            ->timeout(3600)
            ->get("$sage_base_url/OE/OEInvoices($InvoiceID)");

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'invoice_number' => $invoiceNumber,
                'invoice_id' => $InvoiceID,
                'message' => 'Failed to fetch invoice details from Sage. HTTP ' . $response->status()
            ], 500);
        }

        $invoiceResult = $response->json();
        $invoiceDetails = $invoiceResult['InvoiceDetails'] ?? [];
        $totalLineItems = count($invoiceDetails);

        if ($totalLineItems < 1) {
            return response()->json([
                'success' => false,
                'invoice_number' => $invoiceNumber,
                'invoice_id' => $InvoiceID,
                'message' => 'Invoice has no line items to process.'
            ], 422);
        }

        $CustomerNumber = $invoiceResult['CustomerNumber'] ?? '';
        $customerTPIN = '';

        if ($CustomerNumber !== '') {
            $customerResponse = Http::withBasicAuth($sageUser, $sagePwd)
                ->timeout(3600)
                ->get("$sage_base_url/AR/ARCustomers('$CustomerNumber')");

            if ($customerResponse->successful()) {
                $customerResult = $customerResponse->json();
                $customerTPIN = $customerResult['BusinessRegistrationNumber'] ?? '';
            }
        }

        $batches = array_chunk($invoiceDetails, 999);
        $results = [];
        $hasFailures = false;

        foreach ($batches as $batchIndex => $batch) {
            $counter = $batchIndex + 1;
            $offset = $batchIndex * 999;

            $splitRequest = Request::create('/api/send-invoice', 'GET', [
                'InvoiceID' => $InvoiceID,
                'customerTPIN' => $customerTPIN,
                'splitCounter' => $counter,
                'detailOffset' => $offset,
                'detailLimit' => 999,
            ]);

            $splitResponse = $this->sendInvoice($splitRequest);

            if ($splitResponse === null) {
                $statusCode = 500;
                $responseData = [
                    'resultCd' => '999',
                    'resultMsg' => 'VSDC call failed and was queued for offline processing.',
                ];
            }
            else {
                $statusCode = method_exists($splitResponse, 'getStatusCode') ? $splitResponse->getStatusCode() : 200;
                $responseData = method_exists($splitResponse, 'getData') ? $splitResponse->getData(true) : null;
            }

            $isSuccessful = $statusCode >= 200 && $statusCode < 300 && (($responseData['resultCd'] ?? null) === '000');

            if (!$isSuccessful) {
                $hasFailures = true;
            }

            $results[] = [
                'invoice_number' => $invoiceNumber . '-' . $counter,
                'invoice_id' => $InvoiceID . '-' . $counter,
                'line_item_count' => count($batch),
                'status_code' => $statusCode,
                'success' => $isSuccessful,
                'response' => $responseData,
            ];
        }

        return response()->json([
            'success' => !$hasFailures,
            'invoice_number' => $invoiceNumber,
            'invoice_id' => $InvoiceID,
            'total_line_items' => $totalLineItems,
            'batch_count' => count($batches),
            'batches' => $results,
        ], $hasFailures ? 500 : 200);
    }

    public function sendInvoice(Request $request)
 {
 ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
 set_time_limit(3600);

 $settings = Settings::find(1);
 $tinNumber = $settings->tinNumber;
 $branchCode= $settings->branchCode;
 $zra_base_url = $settings->baseUrl;
 $is_tax_exclusive = $settings->is_tax_exclusive;

 $sage_base_url = env('SAGE_URL');
 $sageUser = env('SAGE_USER');
 $sagePwd = env('SAGE_PASSWORD');
 $InvoiceID = $request->query("InvoiceID");
 $customerTPIN = $request->query("customerTPIN");
 Log::info($request);
 $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/OE/OEInvoices($InvoiceID)");
 $result = $response->json();

 // Log::info($result);

 //create a new sales_invoice in api DB
 $sageInvNum = $result['InvoiceNumber'];
 $splitCounter = $request->query("splitCounter");
 $isSplitInvoice = $splitCounter !== null && $splitCounter !== '';
 $zraInvoiceNumber = $isSplitInvoice ? $sageInvNum . '-' . $splitCounter : $sageInvNum;
 $salesInvoiceID = $isSplitInvoice ? $InvoiceID . '-' . $splitCounter : $InvoiceID;
 $invoiceDetails = $result['InvoiceDetails'] ?? [];

 if ($isSplitInvoice) {
 $detailOffset = max(0, (int) $request->query("detailOffset", 0));
 $detailLimit = min(999, max(1, (int) $request->query("detailLimit", 999)));
 $invoiceDetails = array_slice($invoiceDetails, $detailOffset, $detailLimit);

 if (count($invoiceDetails) < 1) {
 return response()->json([
 'resultCd' => '999',
 'resultMsg' => 'Split invoice batch has no line items.',
 ], 422);
 }
 }

 $lineItemCount = $isSplitInvoice ? count($invoiceDetails) : $result['NumberOfLinesInInvoice'];
 $sales_invoice = SalesInvoice::where('invoice_id',$salesInvoiceID)->get();
 
 if(!count($sales_invoice)){
 $sales_invoice = new SalesInvoice();
 $sales_invoice->invoice_id = $salesInvoiceID;

 if(is_numeric($customerTPIN) and
 strlen((string)$customerTPIN) and
 (strlen((string)$customerTPIN)>0 and strlen((string)$customerTPIN)<=10))
 {
 $sales_invoice->customerTPIN = $customerTPIN;
 }
 elseif(strlen($result['ShipmentTrackingNumber']))
 {
 $customerTPIN = $result['ShipmentTrackingNumber'];
 $sales_invoice->customerTPIN = $customerTPIN;
 }
 else
 {
 $customerTPIN = '1000000000';
 $sales_invoice->customerTPIN = $customerTPIN;
 }

 $sales_invoice->sage_invoice_number = $zraInvoiceNumber;
 $sales_invoice->save();
 }
 else{
 $sales_invoice = $sales_invoice[0];
 $sales_invoice->sage_invoice_number = $zraInvoiceNumber;
 }

 $pmtTyCd = $saleCtyCd = $destnCountryCd = '';
 foreach($result['InvoiceOptionalFields'] as $optField)
 {
 if($optField['OptionalField'] == 'PAYMENTTYPE') $pmtTyCd = $optField['Value'];
 elseif($optField['OptionalField'] == 'SALECATEGORY') $saleCtyCd = $optField['Value'];
 elseif($optField['OptionalField'] == 'DESTCOUNTRY') $destnCountryCd = $optField['Value'];
 }
 $salesSttsCd = '02';//sage only has approved.

 if($sageInvNum == 'IN00067336') $destnCountryCd = 'GH';

 //make zra array
 $zra_invoice_array = array(
 "tpin" => $tinNumber,
 "bhfId" => $branchCode,
 "orgInvcNo" => 0,
 "cisInvcNo" => $zraInvoiceNumber,
 "custNm" => $result['BillTo'],
 "salesTyCd" => 'N',
 "rcptTyCd" => 'S',
 "pmtTyCd" => $pmtTyCd,
 "salesSttsCd" => $salesSttsCd,
 "cfmDt" => date('Ymdhis', strtotime($result['InvoiceDate'])),
 "salesDt" => date('Ymd', strtotime($result['InvoiceDate'])),
 "stockRlsDt" => date('Ymdhis', strtotime($result['ShipmentDate'])),
 "cnclReqDt" => null,
 "cnclDt" => null,
 "rfdDt" => null,
 "rfdRsnCd" => '',
 "totItemCnt" => $lineItemCount,
 "taxAmtTot" => 0.0, //this is turnobver tax
 // "totTaxblAmt" => $result['TaxBase1'],
 // "totTaxAmt" => $result['TotalTaxAmount1'],
 // "totAmt" => $result['InvoiceAmountDue'],
 "prchrAcptcYn" => 'N',
 // "remark" => $result['Description'],
 "regrId" => 'ADMIN',
 "regrNm" => 'ADMIN',
 "modrId" => 'ADMIN',
 "modrNm" => 'ADMIN',
 "saleCtyCd" => $saleCtyCd,
 "currencyTyCd" => $result['InvoiceSourceCurrency'],
 "exchangeRt" => round($result['InvoiceRate'],4),
 "destnCountryCd" => $destnCountryCd,
 "dbtRsnCd" => '',
 "invcAdjustReason" => '',
 );

 //stockItem
 $InvoiceUniquifier = $result['InvoiceUniquifier'];
 $zra_stock_item_array = [
 "tpin" => $tinNumber,
 "bhfId" => $branchCode,
 'sarNo' => $InvoiceUniquifier,
 'orgSarNo' => $InvoiceUniquifier,
 'regTyCd' => "M",
 'custTpin' => NULL,
 'custNm' => $result['BillTo'],
 'custBhfId' => NULL,
 'sarTyCd' => '11',
 'ocrnDt' => date('Ymd', strtotime($result['InvoiceDate'])),
 'regrId' => 'Admin',
 'regrNm' => 'Admin',
 'modrNm' => 'Admin',
 'modrId' => 'Admin',
 'remark' => 'Sales Invoice',
 ];

 // "custTpin" => "",
 if(is_numeric($customerTPIN) and
 strlen((string)$customerTPIN) and
 (strlen((string)$customerTPIN)>0 and strlen((string)$customerTPIN)<=10))
 {
 $tpin = $this->verifyTpin($customerTPIN);
 $zra_invoice_array["custTpin"] = $tpin;
 $zra_stock_item_array["custTpin"] = $tpin;
 }

 $taxblAmtA = 0.0;
 $taxblAmtB = 0.0;
 $taxblAmtC = 0.0;
 $taxblAmtC1 = 0.0;
 $taxblAmtC2 = 0.0;
 $taxblAmtC3 = 0.0;
 $taxblAmtD =0.0;
 $taxblAmtRvat =0.0;
 $taxblAmtE = 0.0;
 $taxblAmtF = 0.0;
 $taxblAmtIpl1 = 0.0;
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

 $itemNumber = 0;
 foreach($invoiceDetails as $detail)
 {
 $pkgUnitCd = '';
 foreach($detail['InvoiceDetailOptionalFields'] as $optField)
 {
 if($optField['OptionalField'] == 'PACKAGINGUNT') $pkgUnitCd = $optField['Value'];
 }

 $vatCatCd = $exciseTxCatCd = $tlCatCd = $iplCatCd = $fCatCd = null;
 $vatTaxblAmt = $exciseTaxblAmt = $tlTaxblAmt = $iplTaxblAmt = $iplAmt = $tlAmt = $vatAmt = $exciseTxAmt = $fTaxblAmt = $fAmt = 0.0;

 $taxCategoryCd = explode('-', $detail['TaxClass1Description']);
 $taxCategoryCd = strtoupper(trim($taxCategoryCd[0]));

 $detail['TaxAmount1'] = round($detail['TaxAmount1'], 4);

 $taxCategoryCdTL = explode('-', $detail['TaxClass2Description']);
 $taxCategoryCdTL = trim($taxCategoryCdTL[0]);

 // determine unit price
 $UnitPrice = $detail['UnitPrice'];
 $totAmt = $detail['DetailAmountDue'];

 


 if(($taxCategoryCd == 'TL') and ($taxCategoryCdTL == ''))
 {
 $tlCatCd = $taxCategoryCd;
 $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

 $taxRtTl = round($detail['TaxRate1']);

 // 100 * 100/(100+1.5)
 $taxableAmount = $item_price * 100 / (100 + $taxRtTl);
 $tlTaxblAmt = $taxableAmount;
 $taxblAmtTl += $tlTaxblAmt;

 // taxblAmtTl * 1.5/100
 $tlAmt = $tlTaxblAmt * ($taxRtTl/100);
 $taxAmtTl += $tlAmt;
 }
 elseif(($taxCategoryCd == 'TL') and ($taxCategoryCdTL != ''))
 {
 $vatCatCd = $taxCategoryCdVat = $taxCategoryCdTL;

 $tlCatCd = $taxCategoryCd;
 $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

 // 100 * 100 / 117.5

 $taxRtA = $detail['TaxRate2'];
 $taxRtTl = round($detail['TaxRate1']);

 $taxableAmount = $item_price * 100 / (100 + ($taxRtA+$taxRtTl));
 $vatTaxblAmt = $taxableAmount;
 $taxblAmtA += $vatTaxblAmt;


 $tlTaxblAmt = $taxableAmount;
 $taxblAmtTl += $tlTaxblAmt;

 // taxable amount * (1.5/100);
 $tlAmt = $tlTaxblAmt * ($taxRtTl/100);
 $taxAmtTl += $tlAmt;

 // taxable amount * (16/100);
 $vatAmt = $vatTaxblAmt * ($taxRtA/100);;
 $taxAmtA += $vatAmt;
 }
 elseif(($taxCategoryCdTL == 'TL') and ($taxCategoryCd != ''))
 {
 $vatCatCd = $taxCategoryCdVat = $taxCategoryCd;

 $tlCatCd = $taxCategoryCdTL;
 $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

 // 100 * 100 / 117.5

 $taxRtA = round($detail['TaxRate1']);
 $taxRtTl = $detail['TaxRate2'];

 $taxableAmount = $item_price * 100 / (100 + ($taxRtA+$taxRtTl));
 $vatTaxblAmt = $taxableAmount;
 $taxblAmtA += $vatTaxblAmt;


 $tlTaxblAmt = $taxableAmount;
 $taxblAmtTl += $tlTaxblAmt;

 // taxable amount * (1.5/100);
 $tlAmt = $tlTaxblAmt * ($taxRtTl/100);
 $taxAmtTl += $tlAmt;

 // taxable amount * (16/100);
 $vatAmt = $vatTaxblAmt * ($taxRtA/100);;
 $taxAmtA += $vatAmt;
 }
 elseif($taxCategoryCd == 'ECM' OR $taxCategoryCd == 'EXEEG')
 {
 $taxCategoryCdVat = explode('-', $detail['TaxClass2Description']);
 $taxCategoryCdVat = trim($taxCategoryCdVat[0]);

 $exciseTxCatCd = $taxCategoryCd;
 $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];
 // $exciseTaxblAmt = $detail['TaxBase1'];
 // $exciseTxAmt = $detail['TaxAmount1'];
 $vatCatCd = $taxCategoryCdVat;
 if($taxCategoryCd == 'ECM')
 {
 $taxRtEcm = round($detail['TaxRate1']);
 // TaxBase2 * 100/105
 $taxRtA = $detail['TaxRate2'];
 $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
 // if($is_tax_exclusive)
 // {
 // $vatTaxblAmt = $item_price;
 // }
 $taxblAmtA += $vatTaxblAmt;

 $exciseTaxblAmt = $vatTaxblAmt * 100 / (100 + $taxRtEcm);
 // if($is_tax_exclusive)
 // {
 // $exciseTaxblAmt = $vatTaxblAmt;
 // }
 $taxblAmtEcm += $exciseTaxblAmt;
 // TaxBase2 - TaxBase2 * 100/105
 $exciseTxAmt = $vatTaxblAmt - $taxblAmtEcm;
 $vatAmt = $item_price - $vatTaxblAmt;

 // if($is_tax_exclusive)
 // {
 // $vatAmt = $item_price * ($taxRtA / 100);
 // $exciseTxAmt = $item_price * ($taxRtEcm / 100);
 // }

 $taxAmtEcm += $exciseTxAmt;
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
 $exciseTxAmt = $vatTaxblAmt - $taxblAmtExeeg;
 $taxAmtExeeg += $exciseTxAmt;

 $vatAmt = $item_price - $vatTaxblAmt;
 $taxAmtA += $vatAmt;
 }

 }
 elseif($taxCategoryCd == 'F')
 {
 $fCatCd = $taxCategoryCd;
 $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

 $taxRtF = round($detail['TaxRate1']);
 $fTaxblAmt = $item_price * 100 / (100 + $taxRtF);
 $fAmt = $item_price - $fTaxblAmt;
 if($is_tax_exclusive)
 {
 $fAmt = $item_price*($taxRtF / 100);
 $taxblAmtF = $item_price;
 $totAmt = $detail['DetailAmountDue']* (1 + ($taxRtF / 100));
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
 $iplAmt = $item_price - $iplTaxblAmt;
 if($is_tax_exclusive)
 {
 $iplAmt = $item_price*($taxRtIpl1 / 100);
 $iplTaxblAmt = $item_price;
 $totAmt = $detail['DetailAmountDue']* (1 + ($taxRtIpl1 / 100));
 $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtIpl1 / 100));
 }
 $taxblAmtIpl1 += $iplTaxblAmt;
 $taxAmtIpl1 += $iplAmt;
 }
 else
 {
 $taxRtIpl2 = round($detail['TaxRate1']);
 $iplTaxblAmt = $item_price * 100 / (100 + $taxRtIpl2);
 $iplAmt = $item_price - $iplTaxblAmt;
 if($is_tax_exclusive)
 {
 $iplAmt = $item_price*($taxRtIpl2 / 100);
 $iplTaxblAmt = $item_price;
 $totAmt = $detail['DetailAmountDue']* (1 + ($taxRtIpl2 / 100));
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
 $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

 if($taxCategoryCd == 'A')
 {
 $taxRtA = round($detail['TaxRate1']);
 $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
 $vatAmt = $item_price - $vatTaxblAmt;

 if($is_tax_exclusive)
 {
 //$vatAmt = $detail['TaxAmount1'];
 $vatAmt = $item_price*($taxRtA / 100);
 $vatTaxblAmt = $item_price;
 $totAmt = $vatTaxblAmt * (1 + ($taxRtA / 100));
 $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtA / 100));
 }
 $taxblAmtA += $vatTaxblAmt;
 $taxAmtA += $vatAmt;
 }
 elseif($taxCategoryCd == 'B')
 {
 // ZRA hasn't set up MTV yet
 // $currenyCode = $result['TaxReportingTRCurrency'];
 // $itemCode = $detail['Item'];
 // $PriceList = $detail['PriceList'];
 // $mtv_response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/IC/ICItemPricing(CurrencyCode='$currenyCode',UnformattedItemNumber='$itemCode',PriceListCode='$PriceList')");
 // $mtv_result = $mtv_response->json();
 // $item_price = $mtv_result['SalePrice'] * $detail['QuantityShipped'];

 $taxRtB = round($detail['TaxRate1']);
 $vatTaxblAmt = $item_price * 100 / (100 + $taxRtB);
 $vatAmt = $item_price - $vatTaxblAmt;
 if($is_tax_exclusive)
 {
 //$vatAmt = $detail['TaxAmount1'];
 $vatAmt = $item_price*($taxRtB / 100);
 $vatTaxblAmt = $item_price;
 $totAmt = $detail['DetailAmountDue']* (1 + ($taxRtB / 100));
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
 $vatAmt = $item_price - $vatTaxblAmt;
 if($is_tax_exclusive)
 {
 //$vatAmt = $detail['TaxAmount1'];
 $vatAmt = $item_price*($taxRtC1 / 100);
 $vatTaxblAmt = $item_price;
 $totAmt = $vatTaxblAmt* (1 + ($taxRtC1 / 100));
 $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtC1 / 100));
 }
 $taxblAmtC1 += $vatTaxblAmt;
 $taxAmtC1 += $vatAmt;
 }
 elseif($taxCategoryCd == 'C2')
 {
 if(strlen($result['PurchaseOrderNumber']) > 1)
 {
 $zra_invoice_array["lpoNumber"] = $result['PurchaseOrderNumber'];
 }

 $taxRtC2 =round($detail['TaxRate1']);
 $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC2);
 $vatAmt = $item_price - $vatTaxblAmt;
 if($is_tax_exclusive)
 {
 //$vatAmt = $detail['TaxAmount1'];
 $vatAmt = $item_price*($taxRtC2 / 100);
 $vatTaxblAmt = $item_price;
 $totAmt = $vatTaxblAmt * (1 + ($taxRtC2 / 100));
 $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtC2 / 100));
 }
 $taxblAmtC2 += $vatTaxblAmt;
 $taxAmtC2 += $vatAmt;
 }
 elseif($taxCategoryCd == 'C3')
 {
 $taxRtC3 =round($detail['TaxRate1']);
 $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC3);
 $vatAmt = $item_price - $vatTaxblAmt;
 if($is_tax_exclusive)
 {
 //$vatAmt = $detail['TaxAmount1'];
 $vatAmt = $item_price*($taxRtC3 / 100);
 $vatTaxblAmt = $item_price;
 $totAmt = $vatTaxblAmt * (1 + ($taxRtC3 / 100));
 $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtC3 / 100));
 }
 $taxblAmtC3 += $vatTaxblAmt;
 $taxAmtC3 += $vatAmt;
 }
 elseif($taxCategoryCd == 'D')
 {
 $taxRtD =round($detail['TaxRate1']);
 $vatTaxblAmt = $item_price * 100 / (100 + $taxRtD);
 $vatAmt = $item_price - $vatTaxblAmt;
 if($is_tax_exclusive)
 {
 //$vatAmt = $detail['TaxAmount1'];
 $vatAmt = $item_price*($taxRtD / 100);
 $vatTaxblAmt = $item_price;
 $totAmt = $vatTaxblAmt * (1 + ($taxRtD / 100));
 $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtD / 100));
 }
 $taxblAmtD += $vatTaxblAmt;
 $taxAmtD += $vatAmt;
 }
 elseif($taxCategoryCd == 'RVAT')
 {
 $taxRtRvat =round($detail['TaxRate1']);
 $vatTaxblAmt = $item_price * 100 / (100 + $taxRtRvat);
 $vatAmt = $item_price - $vatTaxblAmt;
 if($is_tax_exclusive)
 {
 // $vatAmt = $detail['TaxAmount1'];
 $vatAmt = $item_price*($taxRtRvat / 100);
 $vatTaxblAmt = $item_price;
 $totAmt = $vatTaxblAmt * (1 + ($taxRtRvat / 100));
 $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtRvat / 100));
 }
 $taxblAmtRvat += $vatTaxblAmt;
 $taxAmtRvat += $vatAmt;
 }
 elseif($taxCategoryCd == 'E')
 {
 $taxRtE =round($detail['TaxRate1']);
 $vatTaxblAmt = $item_price * 100 / (100 + $taxRtE);
 $vatAmt = $item_price - $vatTaxblAmt;
 if($is_tax_exclusive)
 {
 //$vatAmt = $detail['TaxAmount1'];
 $vatAmt = $item_price*($taxRtE / 100);
 $vatTaxblAmt = $item_price;
 $totAmt = $vatTaxblAmt * (1 + ($taxRtE / 100));
 $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtE / 100));
 }
 $taxblAmtE += $vatTaxblAmt;
 $taxAmtE += $vatAmt;
 }
 }
 $qtyUnitCd = $this->selectUnitOfmeasure($detail['InvoiceUnitOfMeasure']);

 $itemClsCd = $this->getItemUNCode($detail['Item']);

 // $stock_item_data = $this->getItemUNCodeAndQuantity($detail['Item']);
 // Log::info('******************');
 // Log::info($stock_item_data);
 // $quantityOnhand = $stock_item_data['quantityOnhand'];
 // $itemClsCd = $stock_item_data['itemCode'];
 $quantityOnhand = 0;

 ++$itemNumber;

 if($sageInvNum == 'IN00067336') $vatCatCd = 'C1';
 $Description = preg_replace('/[^a-zA-Z0-9 ]/', '', $detail['Description']);

 $invoiceItem = [
 "itemSeq" => $itemNumber,
 "itemCd" => $detail['Item'],
 "itemClsCd" => $itemClsCd,
 "itemNm" => $Description,
 "bcd" => '',
 "pkgUnitCd" => $pkgUnitCd,
 "pkg" => floatval($detail['QuantityShipped']),
 "qtyUnitCd" => $qtyUnitCd,
 "qty" => $detail['QuantityShipped'],
 "prc" => round($UnitPrice,4),
 "splyAmt" => round(($UnitPrice * $detail['QuantityShipped']),4),
 "dcRt" => $detail['PriceDiscountPercentage'],
 "dcAmt" => $detail['PriceDiscountAmount'],
 "isrccCd" => '',
 "isrccNm" => '',
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
 $zra_invoice_array['itemList'][] = $invoiceItem;

 $Description = preg_replace('/[^a-zA-Z0-9 ]/', '', $detail['Description']);

 $stock_itemS = [
 "itemSeq"=> $itemNumber,
 "itemCd"=> $detail['Item'],
 "itemClsCd"=> $itemClsCd,
 "itemNm"=> $Description,
 "bcd"=> NULL,
 "pkg"=> floatval($detail['QuantityShipped']),
 "pkgUnitCd"=> $pkgUnitCd,
 "qtyUnitCd" => $qtyUnitCd,
 "qty"=> $detail['QuantityShipped'],
 "itemExprDt"=> NULL,
 "totDcAmt"=> $detail['PriceDiscountAmount'],
 "iplCatCd"=> $iplCatCd,
 "tlCatCd"=> $tlCatCd,
 "exciseTxCatC"=> $exciseTxCatCd,
 "prc"=> round($UnitPrice,2),
 "splyAmt" => round(($detail['UnitPrice'] * $detail['QuantityShipped']),2),
 "iplAmt"=> round($iplAmt,2),
 "tlAmt"=> round($tlAmt,2),
 "exciseTxAmt"=> round($exciseTaxblAmt,2),
 "taxblAmt"=> round($tlTaxblAmt,2),
 "vatCatCd"=> $vatCatCd,
 "taxAmt"=> round(($vatAmt+$iplAmt+$tlAmt+$exciseTxAmt+$fAmt),2),
 "totAmt"=> round($totAmt,2),
 // "quantityOnhand"=>$quantityOnhand,
 ];
 $zra_stock_item_array["itemList"][] = $stock_itemS;
 }

 if($sageInvNum == 'IN00067336')
 {
 $taxblAmtC1 = $taxblAmtA;
 $taxblAmtA = 0;
 }

 $zra_invoice_array['taxblAmtA'] = round($taxblAmtA,4);
 $zra_invoice_array['taxblAmtB'] = round($taxblAmtB,4);
 $zra_invoice_array['taxblAmtC'] = round($taxblAmtC,4);
 $zra_invoice_array['taxblAmtC1'] = round($taxblAmtC1,4);
 $zra_invoice_array['taxblAmtC2'] = round($taxblAmtC2,4);
 $zra_invoice_array['taxblAmtC3'] = round($taxblAmtC3,4);
 $zra_invoice_array['taxblAmtD'] = round($taxblAmtD,4);
 $zra_invoice_array['taxblAmtRvat'] = round($taxblAmtRvat,4);
 $zra_invoice_array['taxblAmtE'] = round($taxblAmtE,4);
 $zra_invoice_array['taxblAmtF'] = round($taxblAmtF,4);
 $zra_invoice_array['taxblAmtIpl1'] =round($taxblAmtIpl1,4);
 $zra_invoice_array['taxblAmtIpl2'] = round($taxblAmtIpl2,4);
 $zra_invoice_array['taxblAmtTl'] = round($taxblAmtTl,4);
 $zra_invoice_array['taxblAmtEcm'] = round($taxblAmtEcm,4);
 $zra_invoice_array['taxblAmtExeeg'] = round($taxblAmtExeeg,4);
 $zra_invoice_array['taxblAmtTot'] = round($taxblAmtTot,4);

 //TODO we got to dynamically get these
 $zra_invoice_array['taxRtA'] = 16;//$taxRtA;
 $zra_invoice_array['taxRtB'] = 16;//$taxRtB;
 $zra_invoice_array['taxRtC1'] = 0;
 $zra_invoice_array['taxRtC2'] = 0;
 $zra_invoice_array['taxRtC3'] = 0;
 $zra_invoice_array['taxRtD'] = 0;
 $zra_invoice_array['taxRtRvat'] = 16;//$taxRtRvat;
 $zra_invoice_array['taxRtE'] = 0;
 $zra_invoice_array['taxRtF'] = 10;//$taxRtF;
 $zra_invoice_array['taxRtIpl1'] = 5;//$taxRtIpl1;
 $zra_invoice_array['taxRtIpl2'] = 0;
 $zra_invoice_array['taxRtTl'] = 1.5;//$taxRtTl;
 $zra_invoice_array['taxRtEcm'] = 5;//$taxRtEcm;
 $zra_invoice_array['taxRtExeeg'] = 3;//$taxRtExeeg;
 $zra_invoice_array['taxRtTot'] = $taxRtTot;

 $zra_invoice_array['taxAmtA'] = round($taxAmtA,4);
 $zra_invoice_array['taxAmtB'] = round($taxAmtB,4);
 $zra_invoice_array['taxAmtC'] = round($taxAmtC,4);
 $zra_invoice_array['taxAmtC1'] = round($taxAmtC1,4);
 $zra_invoice_array['taxAmtC2'] = round($taxAmtC2,4);
 $zra_invoice_array['taxAmtC3'] = round($taxAmtC3,4);
 $zra_invoice_array['taxAmtD'] = round($taxAmtD,4);
 $zra_invoice_array['taxAmtRvat'] = round($taxAmtRvat,4);
 $zra_invoice_array['taxAmtE'] = round($taxAmtE,4);
 $zra_invoice_array['taxAmtF'] = round($taxAmtF,4);
 $zra_invoice_array['taxAmtIpl1'] = round($taxAmtIpl1,4);
 $zra_invoice_array['taxAmtIpl2'] = round($taxAmtIpl2,4);
 $zra_invoice_array['taxAmtTl'] = round($taxAmtTl,4);
 $zra_invoice_array['taxAmtEcm'] = round($taxAmtEcm,4);
 $zra_invoice_array['taxAmtExeeg'] = round($taxAmtExeeg,4);


 $TotalTaxAmount = $taxAmtA + $taxAmtB + $taxAmtC + $taxAmtC1 + $taxAmtC2 + $taxAmtC3 + $taxAmtD + $taxAmtRvat + $taxAmtE + $taxAmtF + $taxAmtIpl1 + $taxAmtIpl2 + $taxAmtTl + $taxAmtEcm + $taxAmtExeeg;
 $TotalTaxBase = $taxblAmtA + $taxblAmtB + $taxblAmtC + $taxblAmtC1 + $taxblAmtC2+ $taxblAmtC3+ $taxblAmtD + $taxblAmtRvat + $taxblAmtE+ $taxblAmtF +$taxblAmtIpl1 +$taxblAmtIpl2+$taxblAmtTl+$taxblAmtEcm+$taxblAmtExeeg;

 $TotAmount = $TotalTaxAmount + $TotalTaxBase;

 $zra_invoice_array["totTaxAmt"] = round($TotalTaxAmount,4);
 $zra_invoice_array["totTaxblAmt"] = round($TotalTaxBase,4);
 //$zra_invoice_array["totTaxblAmt"] = round(($result['InvoiceAmountDue'] - $TotalTaxAmount),4);
 $zra_invoice_array["totAmt"] = round($TotAmount,4);

 $zra_stock_item_array["totTaxblAmt"] = round($TotalTaxBase,2);
 //$zra_stock_item_array["totTaxblAmt"] = round(($result['InvoiceAmountDue'] - $TotalTaxAmount),2);
 $zra_stock_item_array["totTaxAmt"] = round($TotalTaxAmount,2);
 // $zra_stock_item_array["totAmt"] = round($result['InvoiceAmountDue'],2);
 $zra_stock_item_array["totAmt"] = round($TotAmount,4);
 $zra_stock_item_array["totItemCnt"] = $lineItemCount;

 $response = Http::post($zra_base_url."trnsSales/saveSales", $zra_invoice_array);
 $result = $response->json();

 Log::alert('#########################################################');
 Log::info($result);
 Log::alert('#########################################################');

 if($response->failed()){
 Log::alert('***ZRA Call Failed to be processed offline****');
 $offline_trans = new OfflineTransaction();
 $offline_trans->endpoint = "trnsSales/saveSales";
 $offline_trans->payload = json_encode($zra_invoice_array,true);
 $offline_trans->save();

 $offline_trans = new OfflineTransaction();
 $offline_trans->endpoint = "stock/saveStockItems";
 $offline_trans->payload = json_encode($zra_stock_item_array,true);
 $offline_trans->save();

 return;
 }

 Log::info($zra_invoice_array);
 ConfigController::LogDeviceData("trnsSales/saveSales",$zra_invoice_array,$result);

 //update sales_invoice tables
 $sales_invoice->resultCd = $result['resultCd'];
 $sales_invoice->resultMsg = $result['resultMsg'];
 $sales_invoice->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
 if(!empty($result['data']))
 {
 $sales_invoice->rcptNo = $result['data']['rcptNo'];
 $sales_invoice->intrlData = $result['data']['intrlData'];
 $sales_invoice->rcptSign = $result['data']['rcptSign'];
 $sales_invoice->vsdcRcptPbctDate = $result['data']['vsdcRcptPbctDate'];
 $sales_invoice->sdcId = $result['data']['sdcId'];
 $sales_invoice->mrcNo = $result['data']['mrcNo'];
 $sales_invoice->qrCodeUrl = $result['data']['qrCodeUrl'];
 }

 if($result['resultCd'] == '000')
 {
 $sales_invoice->status = 'Interfaced';
 $sales_invoice->save();
 // StockItemController::interfaceStockItem($zra_stock_item_array); 
 // StockItemController::interfaceStockItemNew($zra_stock_item_array);
 UpdateSalesInvoiceStocks::dispatch($zra_stock_item_array);
 }
 elseif($result['resultCd'] == '910')
 {
 $sales_invoice->save();

 if((int)strpos($result['resultMsg'],"custTpin")>0)
 {
 $this-> retryInterfacingSale($zra_invoice_array,$zra_stock_item_array, $zra_base_url, $sales_invoice);
 }

 }
 else
 {
 $sales_invoice->save();
 }


 return response()->json($result);
 }

    public function sendInvoice_OG(Request $request)
    {
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);

        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $zra_base_url = $settings->baseUrl;
        $is_tax_exclusive = $settings->is_tax_exclusive;

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');
        $InvoiceID = $request->query("InvoiceID");
        $customerTPIN = $request->query("customerTPIN");
        Log::info($request);
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/OE/OEInvoices($InvoiceID)");
        $result = $response->json();

        // Log::info($result);

        //create a new sales_invoice in api DB
        $sageInvNum = $result['InvoiceNumber'];
        $sales_invoice  = SalesInvoice::where('invoice_id',$InvoiceID)->get();
       
        if(!count($sales_invoice)){
            $sales_invoice  = new SalesInvoice();
            $sales_invoice->invoice_id = $InvoiceID;

            if(is_numeric($customerTPIN) and
            strlen((string)$customerTPIN) and
            (strlen((string)$customerTPIN)>0 and strlen((string)$customerTPIN)<=10))
            {
                $sales_invoice->customerTPIN = $customerTPIN;
            }
            elseif(strlen($result['ShipmentTrackingNumber']))
            {
                $customerTPIN = $result['ShipmentTrackingNumber'];
                $sales_invoice->customerTPIN = $customerTPIN;
            }
            else
            {
                $customerTPIN = '1000000000';
                $sales_invoice->customerTPIN = $customerTPIN;
            }

            $sales_invoice->sage_invoice_number = $sageInvNum;
            $sales_invoice->save();
        }
        else{
            $sales_invoice = $sales_invoice[0];
            $sales_invoice->sage_invoice_number = $sageInvNum;
        }

        $pmtTyCd = $saleCtyCd = $destnCountryCd = '';
        foreach($result['InvoiceOptionalFields'] as $optField)
        {
            if($optField['OptionalField'] == 'PAYMENTTYPE') $pmtTyCd = $optField['Value'];
            elseif($optField['OptionalField'] == 'SALECATEGORY') $saleCtyCd = $optField['Value'];
            elseif($optField['OptionalField'] == 'DESTCOUNTRY') $destnCountryCd = $optField['Value'];
        }
        $salesSttsCd = '02';//sage only has approved.

        if($sageInvNum == 'IN00067336') $destnCountryCd = 'GH';

        //make zra array
        $zra_invoice_array = array(
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "orgInvcNo" => 0,
            "cisInvcNo" => $sageInvNum,
            "custNm" => $result['BillTo'],
            "salesTyCd" => 'N',
            "rcptTyCd" => 'S',
            "pmtTyCd" => $pmtTyCd,
            "salesSttsCd" => $salesSttsCd,
            "cfmDt" => date('Ymdhis', strtotime($result['InvoiceDate'])),
            "salesDt" => date('Ymd', strtotime($result['InvoiceDate'])),
            "stockRlsDt" => date('Ymdhis', strtotime($result['ShipmentDate'])),
            "cnclReqDt" => null,
            "cnclDt" => null,
            "rfdDt" => null,
            "rfdRsnCd" => '',
            "totItemCnt" => $result['NumberOfLinesInInvoice'],
            "taxAmtTot" => 0.0, //this is turnobver tax
            // "totTaxblAmt" => $result['TaxBase1'],
            // "totTaxAmt" => $result['TotalTaxAmount1'],
            // "totAmt" => $result['InvoiceAmountDue'],
            "prchrAcptcYn" => 'N',
            // "remark" => $result['Description'],
            "regrId" => 'ADMIN',
            "regrNm" => 'ADMIN',
            "modrId" => 'ADMIN',
            "modrNm" => 'ADMIN',
            "saleCtyCd" => $saleCtyCd,
            "currencyTyCd" => $result['InvoiceSourceCurrency'],
            "exchangeRt" => round($result['InvoiceRate'],4),
            "destnCountryCd" => $destnCountryCd,
            "dbtRsnCd" => '',
            "invcAdjustReason" => '',
        );

        //stockItem
        $InvoiceUniquifier = $result['InvoiceUniquifier'];
        $zra_stock_item_array = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            'sarNo' => $InvoiceUniquifier,
            'orgSarNo' => $InvoiceUniquifier,
            'regTyCd' => "M",
            'custTpin' => NULL,
            'custNm' => $result['BillTo'],
            'custBhfId' => NULL,
            'sarTyCd' => '11',
            'ocrnDt' => date('Ymd', strtotime($result['InvoiceDate'])),
            'regrId' => 'Admin',
            'regrNm' => 'Admin',
            'modrNm' => 'Admin',
            'modrId' => 'Admin',
            'remark' => 'Sales Invoice',
        ];

         // "custTpin" => "",
        if(is_numeric($customerTPIN) and
            strlen((string)$customerTPIN) and
            (strlen((string)$customerTPIN)>0 and strlen((string)$customerTPIN)<=10))
        {
            $tpin = $this->verifyTpin($customerTPIN);
            $zra_invoice_array["custTpin"] = $tpin;
            $zra_stock_item_array["custTpin"] = $tpin;
        }

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

        $itemNumber = 0;
        foreach($result['InvoiceDetails'] as $detail)
        {
            $pkgUnitCd = '';
            foreach($detail['InvoiceDetailOptionalFields'] as $optField)
            {
                if($optField['OptionalField'] == 'PACKAGINGUNT') $pkgUnitCd = $optField['Value'];
            }

            $vatCatCd = $exciseTxCatCd = $tlCatCd = $iplCatCd = $fCatCd = null;
            $vatTaxblAmt = $exciseTaxblAmt = $tlTaxblAmt = $iplTaxblAmt = $iplAmt = $tlAmt = $vatAmt = $exciseTxAmt = $fTaxblAmt =  $fAmt = 0.0;

            $taxCategoryCd = explode('-', $detail['TaxClass1Description']);
            $taxCategoryCd = strtoupper(trim($taxCategoryCd[0]));

            $detail['TaxAmount1'] = round($detail['TaxAmount1'], 4);

            $taxCategoryCdTL = explode('-', $detail['TaxClass2Description']);
            $taxCategoryCdTL = trim($taxCategoryCdTL[0]);

            // determine unit price
            $UnitPrice = $detail['UnitPrice'];
            $totAmt = $detail['DetailAmountDue'];

            


            if(($taxCategoryCd == 'TL') and ($taxCategoryCdTL == ''))
            {
                $tlCatCd = $taxCategoryCd;
                $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

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
                $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

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
                $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

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
                $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];
                // $exciseTaxblAmt =  $detail['TaxBase1'];
                // $exciseTxAmt = $detail['TaxAmount1'];
                $vatCatCd = $taxCategoryCdVat;
                if($taxCategoryCd == 'ECM')
                {
                    $taxRtEcm = round($detail['TaxRate1']);
                    // TaxBase2 * 100/105
                    $taxRtA = $detail['TaxRate2'];
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
                    // if($is_tax_exclusive)
                    // {
                    //     $vatTaxblAmt = $item_price;
                    // }
                    $taxblAmtA += $vatTaxblAmt;

                    $exciseTaxblAmt = $vatTaxblAmt * 100 / (100 + $taxRtEcm);
                    // if($is_tax_exclusive)
                    // {
                    //     $exciseTaxblAmt = $vatTaxblAmt;
                    // }
                    $taxblAmtEcm += $exciseTaxblAmt;
                    // TaxBase2 - TaxBase2 * 100/105
                    $exciseTxAmt =  $vatTaxblAmt -  $taxblAmtEcm;
                    $vatAmt =  $item_price -  $vatTaxblAmt;

                    // if($is_tax_exclusive)
                    // {
                    //     $vatAmt =  $item_price * ($taxRtA / 100);
                    //     $exciseTxAmt =  $item_price * ($taxRtEcm / 100);
                    // }

                    $taxAmtEcm += $exciseTxAmt;
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
                    $totAmt = $detail['DetailAmountDue']* (1 + ($taxRtF / 100));
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
                        $totAmt = $detail['DetailAmountDue']* (1 + ($taxRtIpl1 / 100));
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
                        $totAmt = $detail['DetailAmountDue']* (1 + ($taxRtIpl2 / 100));
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
                $item_price = $detail['UnitPrice'] * $detail['QuantityShipped'];

                if($taxCategoryCd == 'A')
                {
                    $taxRtA = round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtA);
                    $vatAmt =  $item_price -  $vatTaxblAmt;

                    if($is_tax_exclusive)
                    {
                        //$vatAmt = $detail['TaxAmount1'];
                        $vatAmt = $item_price*($taxRtA / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $vatTaxblAmt * (1 + ($taxRtA / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtA / 100));
                    }
                    $taxblAmtA += $vatTaxblAmt;
                    $taxAmtA += $vatAmt;
                }
                elseif($taxCategoryCd == 'B')
                {
                    // ZRA hasn't set up MTV yet
                    // $currenyCode = $result['TaxReportingTRCurrency'];
                    // $itemCode = $detail['Item'];
                    // $PriceList = $detail['PriceList'];
                    // $mtv_response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/IC/ICItemPricing(CurrencyCode='$currenyCode',UnformattedItemNumber='$itemCode',PriceListCode='$PriceList')");
                    // $mtv_result = $mtv_response->json();
                    // $item_price = $mtv_result['SalePrice'] * $detail['QuantityShipped'];

                    $taxRtB = round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtB);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        //$vatAmt = $detail['TaxAmount1'];
                          $vatAmt = $item_price*($taxRtB / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $detail['DetailAmountDue']* (1 + ($taxRtB / 100));
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
                        //$vatAmt = $detail['TaxAmount1'];
                        $vatAmt = $item_price*($taxRtC1 / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $vatTaxblAmt* (1 + ($taxRtC1 / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtC1 / 100));
                    }
                    $taxblAmtC1 += $vatTaxblAmt;
                    $taxAmtC1 += $vatAmt;
                }
                elseif($taxCategoryCd == 'C2')
                {
                    if(strlen($result['PurchaseOrderNumber']) > 1)
                    {
                        $zra_invoice_array["lpoNumber"] = $result['PurchaseOrderNumber'];
                    }

                    $taxRtC2 =round($detail['TaxRate1']);
                    $vatTaxblAmt = $item_price * 100 / (100 + $taxRtC2);
                    $vatAmt =  $item_price -  $vatTaxblAmt;
                    if($is_tax_exclusive)
                    {
                        //$vatAmt = $detail['TaxAmount1'];
                        $vatAmt = $item_price*($taxRtC2 / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $vatTaxblAmt * (1 + ($taxRtC2 / 100));
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
                        //$vatAmt = $detail['TaxAmount1'];
                        $vatAmt = $item_price*($taxRtC3 / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $vatTaxblAmt * (1 + ($taxRtC3 / 100));
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
                        //$vatAmt = $detail['TaxAmount1'];
                        $vatAmt = $item_price*($taxRtD / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $vatTaxblAmt * (1 + ($taxRtD / 100));
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
                       // $vatAmt = $detail['TaxAmount1'];
                        $vatAmt = $item_price*($taxRtRvat / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $vatTaxblAmt * (1 + ($taxRtRvat / 100));
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
                        //$vatAmt = $detail['TaxAmount1'];
                        $vatAmt = $item_price*($taxRtE / 100);
                        $vatTaxblAmt = $item_price;
                        $totAmt = $vatTaxblAmt * (1 + ($taxRtE / 100));
                        $UnitPrice = $detail['UnitPrice'] * (1 + ($taxRtE / 100));
                    }
                    $taxblAmtE += $vatTaxblAmt;
                    $taxAmtE += $vatAmt;
                }
            }
            $qtyUnitCd = $this->selectUnitOfmeasure($detail['InvoiceUnitOfMeasure']);

            $itemClsCd = $this->getItemUNCode($detail['Item']);

            // $stock_item_data = $this->getItemUNCodeAndQuantity($detail['Item']);
            // Log::info('******************');
            // Log::info($stock_item_data);
            // $quantityOnhand = $stock_item_data['quantityOnhand'];
            // $itemClsCd = $stock_item_data['itemCode'];
            $quantityOnhand = 0;

            ++$itemNumber;

            if($sageInvNum == 'IN00067336') $vatCatCd = 'C1';
            $Description = preg_replace('/[^a-zA-Z0-9 ]/', '', $detail['Description']);

            $invoiceItem = [
                    "itemSeq" => $itemNumber,
                    "itemCd" => $detail['Item'],
                    "itemClsCd" => $itemClsCd,
                    "itemNm" => $Description,
                    "bcd" => '',
                    "pkgUnitCd" => $pkgUnitCd,
                    "pkg" => floatval($detail['QuantityShipped']),
                    "qtyUnitCd" => $qtyUnitCd,
                    "qty" => $detail['QuantityShipped'],
                    "prc" => round($UnitPrice,4),
                    "splyAmt" => round(($UnitPrice * $detail['QuantityShipped']),4),
                    "dcRt" => $detail['PriceDiscountPercentage'],
                    "dcAmt" => $detail['PriceDiscountAmount'],
                    "isrccCd" => '',
                    "isrccNm" => '',
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
            $zra_invoice_array['itemList'][] = $invoiceItem;

            $Description = preg_replace('/[^a-zA-Z0-9 ]/', '', $detail['Description']);

            $stock_itemS = [
                "itemSeq"=> $itemNumber,
                "itemCd"=> $detail['Item'],
                "itemClsCd"=> $itemClsCd,
                "itemNm"=> $Description,
                "bcd"=> NULL,
                "pkg"=> floatval($detail['QuantityShipped']),
                "pkgUnitCd"=> $pkgUnitCd,
                "qtyUnitCd" => $qtyUnitCd,
                "qty"=> $detail['QuantityShipped'],
                "itemExprDt"=> NULL,
                "totDcAmt"=> $detail['PriceDiscountAmount'],
                "iplCatCd"=> $iplCatCd,
                "tlCatCd"=> $tlCatCd,
                "exciseTxCatC"=> $exciseTxCatCd,
                "prc"=> round($UnitPrice,2),
                "splyAmt" => round(($detail['UnitPrice'] * $detail['QuantityShipped']),2),
                "iplAmt"=> round($iplAmt,2),
                "tlAmt"=> round($tlAmt,2),
                "exciseTxAmt"=> round($exciseTaxblAmt,2),
                "taxblAmt"=> round($tlTaxblAmt,2),
                "vatCatCd"=> $vatCatCd,
                "taxAmt"=> round(($vatAmt+$iplAmt+$tlAmt+$exciseTxAmt+$fAmt),2),
                "totAmt"=> round($totAmt,2),
                // "quantityOnhand"=>$quantityOnhand,
            ];
            $zra_stock_item_array["itemList"][] = $stock_itemS;
        }

        if($sageInvNum == 'IN00067336')
        {
            $taxblAmtC1 = $taxblAmtA;
            $taxblAmtA = 0;
        }

        $zra_invoice_array['taxblAmtA'] = round($taxblAmtA,4);
        $zra_invoice_array['taxblAmtB'] = round($taxblAmtB,4);
        $zra_invoice_array['taxblAmtC'] = round($taxblAmtC,4);
        $zra_invoice_array['taxblAmtC1'] = round($taxblAmtC1,4);
        $zra_invoice_array['taxblAmtC2'] = round($taxblAmtC2,4);
        $zra_invoice_array['taxblAmtC3'] = round($taxblAmtC3,4);
        $zra_invoice_array['taxblAmtD'] = round($taxblAmtD,4);
        $zra_invoice_array['taxblAmtRvat'] = round($taxblAmtRvat,4);
        $zra_invoice_array['taxblAmtE'] = round($taxblAmtE,4);
        $zra_invoice_array['taxblAmtF'] = round($taxblAmtF,4);
        $zra_invoice_array['taxblAmtIpl1'] =round($taxblAmtIpl1,4);
        $zra_invoice_array['taxblAmtIpl2'] = round($taxblAmtIpl2,4);
        $zra_invoice_array['taxblAmtTl'] = round($taxblAmtTl,4);
        $zra_invoice_array['taxblAmtEcm'] = round($taxblAmtEcm,4);
        $zra_invoice_array['taxblAmtExeeg'] = round($taxblAmtExeeg,4);
        $zra_invoice_array['taxblAmtTot'] = round($taxblAmtTot,4);

        //TODO we got to dynamically get these
        $zra_invoice_array['taxRtA'] = 16;//$taxRtA;
        $zra_invoice_array['taxRtB'] = 16;//$taxRtB;
        $zra_invoice_array['taxRtC1'] = 0;
        $zra_invoice_array['taxRtC2'] = 0;
        $zra_invoice_array['taxRtC3'] = 0;
        $zra_invoice_array['taxRtD'] = 0;
        $zra_invoice_array['taxRtRvat'] = 16;//$taxRtRvat;
        $zra_invoice_array['taxRtE'] = 0;
        $zra_invoice_array['taxRtF'] = 10;//$taxRtF;
        $zra_invoice_array['taxRtIpl1'] = 5;//$taxRtIpl1;
        $zra_invoice_array['taxRtIpl2'] = 0;
        $zra_invoice_array['taxRtTl'] = 1.5;//$taxRtTl;
        $zra_invoice_array['taxRtEcm'] = 5;//$taxRtEcm;
        $zra_invoice_array['taxRtExeeg'] = 3;//$taxRtExeeg;
        $zra_invoice_array['taxRtTot'] = $taxRtTot;

        $zra_invoice_array['taxAmtA'] =  round($taxAmtA,4);
        $zra_invoice_array['taxAmtB'] =  round($taxAmtB,4);
        $zra_invoice_array['taxAmtC'] =  round($taxAmtC,4);
        $zra_invoice_array['taxAmtC1'] =  round($taxAmtC1,4);
        $zra_invoice_array['taxAmtC2'] =  round($taxAmtC2,4);
        $zra_invoice_array['taxAmtC3'] =  round($taxAmtC3,4);
        $zra_invoice_array['taxAmtD'] =  round($taxAmtD,4);
        $zra_invoice_array['taxAmtRvat'] =  round($taxAmtRvat,4);
        $zra_invoice_array['taxAmtE'] =  round($taxAmtE,4);
        $zra_invoice_array['taxAmtF'] =  round($taxAmtF,4);
        $zra_invoice_array['taxAmtIpl1'] =  round($taxAmtIpl1,4);
        $zra_invoice_array['taxAmtIpl2'] =  round($taxAmtIpl2,4);
        $zra_invoice_array['taxAmtTl'] =  round($taxAmtTl,4);
        $zra_invoice_array['taxAmtEcm'] =  round($taxAmtEcm,4);
        $zra_invoice_array['taxAmtExeeg'] =  round($taxAmtExeeg,4);


        $TotalTaxAmount =  $taxAmtA + $taxAmtB +  $taxAmtC + $taxAmtC1 + $taxAmtC2 + $taxAmtC3 + $taxAmtD + $taxAmtRvat + $taxAmtE + $taxAmtF + $taxAmtIpl1 + $taxAmtIpl2 + $taxAmtTl + $taxAmtEcm + $taxAmtExeeg;
        $TotalTaxBase =  $taxblAmtA + $taxblAmtB + $taxblAmtC + $taxblAmtC1 + $taxblAmtC2+ $taxblAmtC3+ $taxblAmtD + $taxblAmtRvat + $taxblAmtE+ $taxblAmtF +$taxblAmtIpl1 +$taxblAmtIpl2+$taxblAmtTl+$taxblAmtEcm+$taxblAmtExeeg;

        $TotAmount  = $TotalTaxAmount + $TotalTaxBase;

        $zra_invoice_array["totTaxAmt"] = round($TotalTaxAmount,4);
        $zra_invoice_array["totTaxblAmt"] = round($TotalTaxBase,4);
        //$zra_invoice_array["totTaxblAmt"] = round(($result['InvoiceAmountDue'] - $TotalTaxAmount),4);
        $zra_invoice_array["totAmt"] = round($TotAmount,4);

        $zra_stock_item_array["totTaxblAmt"] = round($TotalTaxBase,2);
        //$zra_stock_item_array["totTaxblAmt"] = round(($result['InvoiceAmountDue'] - $TotalTaxAmount),2);
        $zra_stock_item_array["totTaxAmt"] = round($TotalTaxAmount,2);
        // $zra_stock_item_array["totAmt"] = round($result['InvoiceAmountDue'],2);
        $zra_stock_item_array["totAmt"] = round($TotAmount,4);
        $zra_stock_item_array["totItemCnt"] = $result['NumberOfLinesInInvoice'];

        $response = Http::post($zra_base_url."trnsSales/saveSales", $zra_invoice_array);
        $result = $response->json();
        Log::info($result);
        if($response->failed()){
            Log::alert('***ZRA Call Failed to be processed offline****');
            $offline_trans = new OfflineTransaction();
            $offline_trans->endpoint = "trnsSales/saveSales";
            $offline_trans->payload = json_encode($zra_invoice_array,true);
            $offline_trans->save();

            $offline_trans = new OfflineTransaction();
            $offline_trans->endpoint = "stock/saveStockItems";
            $offline_trans->payload = json_encode($zra_stock_item_array,true);
            $offline_trans->save();

            return;
        }

        Log::info($zra_invoice_array);
        ConfigController::LogDeviceData("trnsSales/saveSales",$zra_invoice_array,$result);

        //update sales_invoice tables
        $sales_invoice->resultCd = $result['resultCd'];
        $sales_invoice->resultMsg = $result['resultMsg'];
        $sales_invoice->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
        if(!empty($result['data']))
        {
            $sales_invoice->rcptNo = $result['data']['rcptNo'];
            $sales_invoice->intrlData = $result['data']['intrlData'];
            $sales_invoice->rcptSign = $result['data']['rcptSign'];
            $sales_invoice->vsdcRcptPbctDate = $result['data']['vsdcRcptPbctDate'];
            $sales_invoice->sdcId = $result['data']['sdcId'];
            $sales_invoice->mrcNo = $result['data']['mrcNo'];
            $sales_invoice->qrCodeUrl = $result['data']['qrCodeUrl'];
        }

        if($result['resultCd'] == '000')
        {
            $sales_invoice->status = 'Interfaced';
            $sales_invoice->save();
            // StockItemController::interfaceStockItem($zra_stock_item_array);            
            // StockItemController::interfaceStockItemNew($zra_stock_item_array);
            UpdateSalesInvoiceStocks::dispatch($zra_stock_item_array);
        }
        elseif($result['resultCd'] == '910')
        {
            $sales_invoice->save();

            if((int)strpos($result['resultMsg'],"custTpin")>0)
            {
                $this-> retryInterfacingSale($zra_invoice_array,$zra_stock_item_array, $zra_base_url, $sales_invoice);
            }

        }
        else
        {
            $sales_invoice->save();
        }


        return response()->json($result);
    }
    
    public static function retryInterfacingSale($zra_invoice_array,$zra_stock_item_array, $zra_base_url, $sales_invoice)
    {
        $zra_invoice_array["custTpin"] = null;
        $zra_stock_item_array["custTpin"] = null;

        // $sales_invoice  = SalesInvoice::find($sales_invoice->id);

        $response = Http::post($zra_base_url."trnsSales/saveSales", $zra_invoice_array);
        $result = $response->json();
        ConfigController::LogDeviceData("trnsSales/saveSales",$zra_invoice_array,$result);

        $result = $response->json();
        Log::info($result);
        Log::info(json_encode($zra_invoice_array));

        if($result['resultCd'] == '000')
        {
            $sales_invoice->status = 'Interfaced';
            $sales_invoice->resultDt = empty($result['resultDt']) ? '' : $result['resultDt'];
            if(!empty($result['data']))
            {
                $sales_invoice->rcptNo = $result['data']['rcptNo'];
                $sales_invoice->intrlData = $result['data']['intrlData'];
                $sales_invoice->rcptSign = $result['data']['rcptSign'];
                $sales_invoice->vsdcRcptPbctDate = $result['data']['vsdcRcptPbctDate'];
                $sales_invoice->sdcId = $result['data']['sdcId'];
                $sales_invoice->mrcNo = $result['data']['mrcNo'];
                $sales_invoice->qrCodeUrl = $result['data']['qrCodeUrl'];
            }
            $sales_invoice->save();
            StockItemController::interfaceStockItem($zra_stock_item_array);
        }

    }

    public static function getItemUNCode($itemCode)
    {
        return '42331402';

        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);

        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        // $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/IC/ICItems('$itemCode')");
        $filter = '$filter';

        // $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/IC/ICItems?$filter=ItemNumber eq '$itemCode'");
        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/IC/ICItems?$filter=ItemNumber eq '$itemCode'");

        $result = $response->json();
        $result = $result['value'][0];
        Log::info(">>>>>>>>>>>>>>>>>>>>>>>>>>>INVOICE RECEIVED NOTE>>>>>>>>>>>>>>>>");
        Log::info('Item code before - '.$itemCode);
        // Log::info($result);

        foreach($result['ItemOptionalFields'] as $row)
        {
            if($row["OptionalField"]== "UNSPSC")
            {
                Log::info('Item code after - '.$row['Value']);
                return $row['Value'];
            }
               
        }
        
        return $itemCode;
    }

    public static function getItemUNCodeAndQuantity($itemCode)
    {
        $sage_base_url = env('SAGE_URL');
        $sageUser = env('SAGE_USER');
        $sagePwd = env('SAGE_PASSWORD');

        $filter = '$filter';

        $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(3600)->get("$sage_base_url/IC/ICItems?$filter=ItemNumber eq '$itemCode'");

        $result = $response->json();
        $result = $result['value'][0];
        Log::info("5555555 INVOICE RECEIVED ");
        Log::info($itemCode);
        Log::info("7777777 INVOICE RECEIVED ");
        Log::info($result);

        $quantityOnhand = $result['QuantityOnHand'] ?? 0;

        foreach($result['ItemOptionalFields'] as $row)
        {
            if($row["OptionalField"]== "UNSPSC")
                return $row['Value'];
        }
        
        $stock_arr = [
            'quantityOnhand'=>$quantityOnhand,
            'itemCode'=>$itemCode,
        ];

        Log::debug(';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;');
        Log::debug($stock_arr);
        return $stock_arr;
    }

    public static function verifyTpin($tpin)
    {
        return $tpin;

        // $settings = Settings::find(1);
        // $tinNumber = $settings->tinNumber;
        // $branchCode= $settings->branchCode;
        // $zra_base_url = $settings->baseUrl;

        // $zra_tpin_array = [
        //     "tpin"=>$tinNumber,
        //     "bhfId"=>$branchCode,
        //     "custmTpin"=> $tpin
        // ];
        // $response = Http::post($zra_base_url."customers/selectCustomer", $zra_tpin_array);
        // $result = $response->json();
        // Log::info('********* IN VERIFY TPIN ***********');
        // Log::info($result);
        // Log::info(json_encode($zra_tpin_array));

        // if($result['resultCd'] == '000')
        // {
        //     if(!empty($result['data']['custList'][0]['tpin']))
        //     {
        //         return $result['data']['custList'][0]['tpin'];
        //     }
        // }
        // else
        // {
        //     return null;
        // }

    }
}
