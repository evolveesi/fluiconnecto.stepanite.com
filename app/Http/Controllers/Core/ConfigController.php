<?php

namespace App\Http\Controllers\Core;

use App\DeviceResponses;
use App\Http\Controllers\Controller;
use App\ItemClassList;
use App\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ConfigController extends Controller
{

    public static function LogDeviceData($zra_url,$request_data,$result){
        $device = new DeviceResponses();
        $device->url = $zra_url;
        $device->zra_request = json_encode($request_data,true);
        $device->zra_response = json_encode($result,true);
        $device->save();
    }
}
