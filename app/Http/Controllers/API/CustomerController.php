<?php

namespace App\Http\Controllers\API;

use App\Branch;
use App\Customer;
use App\CustomerSearch;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\ConfigController;
use App\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CustomerController extends Controller
{
    public function storeCustomer(Request $request){
        $settings = Settings::find(1);
        Log::debug($request);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $baseUrl = $settings->baseUrl;

        #expected variables from api hit.
        $custNo= $request->query('custNo');
        $custTpin =  trim(str_replace("TPIN ", "", $request->query('custTpin')));
        $custNm = $request->query('custNm');
        $adrs = $request->query('adrs');
        $email = $request->query('email');
        $faxNo = "";
        $useYn= "Y";
        $remark= "Customer created via Sage 300";
        $regrNm= "Admin";
        $regrId= "Admin";
        $modrNm= "Admin";
        $modrId= "Admin";

        $customer = new Customer();
        $customer->Tpin= $tinNumber;
        $customer->bhfId= $branchCode;
        $customer->custNo = $custNo;
        $customer->custTpin = $custTpin;
        $customer->custNm = $custNm;
        $customer->Adrs = $adrs;
        $customer->Email = $email;
        $customer->faxNo = $faxNo;
        $customer->useYn = $useYn;
        $customer->remark = $remark;
        $customer->regrNm = $regrNm;
        $customer->regrId = $regrId;
        $customer->modrNm = $modrNm;
        $customer->modrId = $modrId;
        $customer->save();

        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "custNo" => $custNo,
            "custTpin" => $custTpin,
            "custNm" => $custNm,
            "adrs" => $adrs,
            "email" => $email,
            "faxNo" => $faxNo,
            "useYn" => $useYn,
            "remark" => $remark,
            "regrNm" => $regrNm,
            "regrId" => $regrId,
            "modrNm" => $modrNm,
            "modrId" => $modrId,
        ];
        $response = Http::post($baseUrl.'branches/saveBrancheCustomers', $request_data);
        $result = $response->json();
        ConfigController::LogDeviceData("/branches/saveBrancheCustomers",$request_data,$result);

        Log::debug($result);
        if (is_null($result['data'])){
            return response()->json($result);
        }
        return response()->json($result);
    }
    public function searchCustomer(Request $request){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $baseUrl = $settings->baseUrl;

        $customer_tpin = $request->custmTpin;

        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "custmTpin" => $customer_tpin
        ];
        $response = Http::post($baseUrl.'/customers/selectCustomer', $request_data);
        $result = $response->json();
        if ($result['resultCd']=="001"){
            return $result;
        }
        Log::debug($result);
        $customer_searches = $result['data']['custList'];
        foreach ($customer_searches as $customer_search_){
            $customer_search = new CustomerSearch();
            $customer_search->modrNm = $customer_search_['modrNm'];
            $customer_search->modrId = $customer_search_['modrId'];
            $customer_search->regrNm = $customer_search_['regrNm'];
            $customer_search->regrId = $customer_search_['regrId'];
            $customer_search->remark = $customer_search_['remark'];
            $customer_search->useYn = $customer_search_['useYn'];
            $customer_search->faxNo = $customer_search_['faxNo'];
            $customer_search->email = $customer_search_['email'];
            $customer_search->telNo = $customer_search_['telNo'];
            $customer_search->adrs = $customer_search_['adrs'];
            $customer_search->custNm = $customer_search_['custNm'];
            $customer_search->custNo = $customer_search_['custNo'];
            $customer_search->bhfId = $customer_search_['bhfId'];
            $customer_search->tpin = $customer_search_['tpin'];
            $customer_search->custTpin = $customer_search_['custTpin'];
            $customer_search->save();
        }
        return response()->json($result);
    }

    #get zra customers
    #check if they exist in sage300
    #if not exist create in sage300
    #if exists update sage300
    public function sync_zra_with_sage(){

    }

    #get sage300 customers
    #check if exist in zra
    #if not exist create in zra
    #if exists leave as is
    public function sync_sage_with_zra(){

    }
}
