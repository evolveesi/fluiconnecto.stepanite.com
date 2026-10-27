<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;


class CheckApiResponse
{


    public function handle($request, Closure $next){
        // Make the API request
        $response = Http::post('https://licenses.stepanite.com/api/license',[
            'tpin'=>env('TPIN')
        ]);
        Log::info("----checking license server ----");
        Log::info($response);

        // Check if the response is empty
        if ($response->json() === []) {
            return response()->json(['success' => false], 200);
        }

        // If the response contains data, proceed with the request
        return $next($request);
    }

}
