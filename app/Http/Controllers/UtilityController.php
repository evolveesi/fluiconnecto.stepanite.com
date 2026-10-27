<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UtilityController extends Controller
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
}
