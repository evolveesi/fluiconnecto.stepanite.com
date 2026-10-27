<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UtilsController extends Controller
{
    // TODO Fetch Tax From SAGE 300
    public static function getTaxRate($tax_class) 
    {
        // $sage_base_url = env('SAGE_URL');
        // $sageUser = env('SAGE_USER');
        // $sagePwd = env('SAGE_PASSWORD');
        // $response =Http::withBasicAuth($sageUser, $sagePwd)->timeout(360)->get("$sage_base_url/TX/TXTaxRates");
        // $result = $response->json();

        // foreach($result['value'] as $row)
        // {
        //     dd($row);
        // }

        // $tax_rates = UtilsController::getTaxRates();
        $taxRtA = 16;//$taxRtA;
        $taxRtB = 16;//$taxRtB;
        $taxRtC1 = 0;
        $taxRtC2 = 0;
        $taxRtC3 = 0;
        $taxRtD = 0;
        $taxRtRvat = 16;//$taxRtRvat;
        $taxRtE = 0;
        $taxRtF = 10;//$taxRtF;
        $taxRtIpl1 = 5;//$taxRtIpl1;
        $taxRtIpl2 = 0;
        $taxRtTl = 1.5;//$taxRtTl;
        $taxRtEcm = 5;//$taxRtEcm;
        $taxRtExeeg = 3;//$taxRtExeeg;

        if(strtoupper($tax_class) == 'A') return $taxRtA;
        if(strtoupper($tax_class) == 'B') return $taxRtB;
        if(strtoupper($tax_class) == 'C1') return $taxRtC1;
        if(strtoupper($tax_class) == 'C2') return $taxRtC2;
        if(strtoupper($tax_class) == 'C3') return $taxRtC3;
        if(strtoupper($tax_class) == 'D') return $taxRtD;
        if(strtoupper($tax_class) == 'RVAT') return $taxRtRvat;
        if(strtoupper($tax_class) == 'E') return $taxRtE;
        if(strtoupper($tax_class) == 'F') return $taxRtF;
        if(strtoupper($tax_class) == 'IPL1') return$taxRtIpl1;
        if(strtoupper($tax_class) == 'IPL2') return $taxRtIpl2;
        if(strtoupper($tax_class) == 'TL') return$taxRtTl;
        if(strtoupper($tax_class) == 'ECM') return$taxRtEcm;
        if(strtoupper($tax_class) == 'EXEEG') return$taxRtExeeg;
    }
}
