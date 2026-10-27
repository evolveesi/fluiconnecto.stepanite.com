<?php

namespace App\Http\Controllers\API;

use App\Branch;
use App\BranchUser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\ConfigController;
use App\ItemClassList;
use App\Settings;
use App\StandardCode;
use App\ClassList;
use App\ClassDetailList;
use App\ZRANotice;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ConfigurationsAPIController extends Controller
{

    public function getItemClassList(){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $lastRequestDate = $settings->lastRequestDate;
        $baseUrl = $settings->baseUrl;
        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastRequestDate
        ];
        $response = Http::post($baseUrl.'itemClass/selectItemsClass', $request_data);
        $result = $response->json();
        ConfigController::LogDeviceData("/itemClass/selectItemsClass",$request_data,$result);


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
        return response()->json($result);
    }
    
    public function getZRANotices(){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $lastRequestDate = $settings->lastRequestDate;
        $baseUrl = $settings->baseUrl;
        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastRequestDate
        ];
        $response = Http::post($baseUrl.'notices/selectNotices', $request_data);
        $result = $response->json();
        ConfigController::LogDeviceData("/notices/selectNotices",$request_data,$result);

        Schema::disableForeignKeyConstraints();
        ZRANotice::truncate();
        Schema::enableForeignKeyConstraints();
        $noticesLists = $result['data']['noticeList'];
        foreach ($noticesLists as $noticesList){
            $notice = new ZRANotice();
            $notice->noticeNo = $noticesList['noticeNo'];
            $notice->title = $noticesList['title'];
            $notice->cont = $noticesList['cont'];
            $notice->dtlUrl = $noticesList['dtlUrl'];
            $notice->regrNm = $noticesList['regrNm'];
            $notice->regDt = $noticesList['regDt'];
            $notice->save();
        }
        return response()->json($result);
    }

    public function createBranchUser(Request $request){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $lastRequestDate = $settings->lastRequestDate;
        $baseUrl = $settings->baseUrl;


        #expected variables from api hit.
        $userId = $request->userId;
        $userNm = $request->userNm;
        $adrs = $request->adrs;
        $useYn = $request->useYn;
        $regrNm = "Admin";
        $regrId = "Admin";
        $modrNm= "Admin";
        $modrId= "Admin";

        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastRequestDate,
            "userId" => $userId,
            "userNm" => $userNm,
            "adrs" => $adrs,
            "useYn" => $useYn,
            "regrNm" => $regrNm,
            "regrId" => $regrId,
            "modrNm" => $modrNm,
            "modrId" => $modrId,
        ];
        $response = Http::post($baseUrl.'branches/saveBrancheUser', $request_data);
        $result = $response->json();
        ConfigController::LogDeviceData("/branches/saveBrancheUser",$request_data,$result);

        $resultCd = $result['resultCd'];
        $resultMsg = $result['resultMsg'];
        $resultDt = $result['resultDt'];
        $branch_user = new BranchUser();

        $branch_user->userNm = $userNm;
        $branch_user->userId = $userId;
        $branch_user->adrs = $adrs;
        $branch_user->useYn = $useYn;
        $branch_user->regrNm = $regrNm;
        $branch_user->regrId = $regrId;
        $branch_user->modrNm = $modrNm;
        $branch_user->modrId = $modrId;
        $branch_user->save();

        return response()->json($result);
    }
    public function getBranches(){
        $settings = Settings::find(1);
        $tinNumber = $settings->tinNumber;
        $branchCode= $settings->branchCode;
        $lastRequestDate = $settings->lastRequestDate;
        $baseUrl = $settings->baseUrl;
        $request_data = [
            "tpin" => $tinNumber,
            "bhfId" => $branchCode,
            "lastReqDt" => $lastRequestDate
        ];
        $response = Http::post($baseUrl.'branches/selectBranches', $request_data);
        $result = $response->json();
        ConfigController::LogDeviceData("/branches/selectBranches",$request_data,$result);

        Schema::disableForeignKeyConstraints();
        Branch::truncate();
        Schema::enableForeignKeyConstraints();

        $branchLists = $result['data']['bhfList'];
        foreach ($branchLists as $branchList){
            $branch = new Branch();
            $branch->tin = $branchList['tin'];
            $branch->bhfId = $branchList['bhfId'];
            $branch->bhfNm = $branchList['bhfNm'];
            $branch->bhfSttsCd = $branchList['bhfSttsCd'];
            $branch->prvncNm = $branchList['prvncNm'];
            $branch->dstrtNm = $branchList['dstrtNm'];
            $branch->sctrNm = $branchList['sctrNm'];
            $branch->locDesc = $branchList['locDesc'];
            $branch->mgrNm = $branchList['mgrNm'];
            $branch->mgrTelNo = $branchList['mgrTelNo'];
            $branch->mgrEmail = $branchList['mgrEmail'];
            $branch->hqYn = $branchList['hqYn'];
            $branch->save();
        }
        return response()->json($result);
    }
}
