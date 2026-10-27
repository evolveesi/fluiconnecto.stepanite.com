<?php

namespace App\Http\Controllers\API;

use App\AppSettings;
use App\ClassDetailList;
use App\ClassList;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\ConfigController;
use App\Settings;
use App\StandardCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AppSettingsController extends Controller
{
    public function getStandardCodes(){

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

        #making application request
        $response = Http::post($baseUrl.'code/selectCodes', $request_data);
        $result = $response->json();
        ConfigController::LogDeviceData("/code/selectCodes",$request_data,$result);

        #truncating old data to make space for new data
        Schema::disableForeignKeyConstraints();
        AppSettings::truncate();
        Schema::enableForeignKeyConstraints();

        #saving new device settings in database
        $standard_codes_data = $result['data']['clsList'];
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

                //                if ($standard_code_data['cdCls']=="05"){
                //                    DB::connection('sqlsrv')->table('CSOPTFH')
                //                        ->insert([
                //                            'OPTFIELD' => "COUNTRYCODE",
                //                            'AUDTDATE' => date('Ymd'),
                //                            'AUDTTIME' => time(),
                //                            'VALUE' => $standard_code_data['cdCls'],
                //                            'AUDTUSER' => "ADMIN",
                //                            'AUDTORG' => "ZZDAT",
                //                            'SORTEDVAL' => $standard_code_data['cdCls'],
                //                            'VDESC' => $standard_code_data['cdClsNm'],
                //                            'TYPE' => 1,
                //                            'LENGTH' => 60,
                //                            'DECIMALS' => 0,
                //                            'ALLOWNULL' => 0,
                //                            'VALIDATE' => 0,
                //                        ]);
                //                }
            }
            $count += 1;
            $standard_code->save();
        }

        if ($result['resultCd']=="000"){
            return redirect()->back()->with("status",$result['resultMsg']);
        }
        return redirect()->back()->with("error",$result['resultMsg']);

        #returning response in json format
        // return response()->json($result);
    }
}
