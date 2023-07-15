<?php

namespace App\Http\Controllers;

use Colorful\Preacher\Preacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rules\Password;

/**
 * 控制器
 *
 * @author beta
 */
class RateController
{
    
    /**
     * 法币汇率接口
     *
     * @var string
     */
    const LEGAL_TENDER_API = 'https://api.it120.cc/gooking/forex/rate';
    
    const HUOBI_API = 'https://api.huobi.pro/market/detail/merged';
    
    /**
     * 汇率转换
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function conversion(Request $request): JsonResponse
    {
        $requestParams = $request::validate([
            'token'    => ['required', 'string'],
            'currency' => ['required', 'string'],
        ]);
        
        $response = Http::get(self::HUOBI_API, [
            'symbol' => $requestParams['token'].'usdt',
        ]);
        
        if (!is_array($response->json())) {
            return Preacher::msgCode(
                '48',
                Preacher::RESP_CODE_FAIL
            )->export()->json();
        }
        
        $response = $response->json();
        $response = (array) $response;
        
        if (!isset($response['status']) || $response['status'] !== 'ok') {
            return Preacher::msgCode(
                '58',
                Preacher::RESP_CODE_FAIL
            )->export()->json();
        }
        
        $tokenRate = $response['tick']['close'];
        
        $response = Http::get(self::LEGAL_TENDER_API, [
            'fromCode' => strtoupper($requestParams['currency']),
            'toCode'   => 'USD',
        ]);
        
        if (!is_array($response->json())) {
            return Preacher::code(
                Preacher::RESP_CODE_FAIL
            )->export()->json();
        }
        
        $response = $response->json();
        $response = (array) $response;
        
        if ($response['code'] !== 0) {
            return Preacher::msgCode(
                $response['msg'],
                Preacher::RESP_CODE_FAIL
            )->export()->json();
        }
        
        $currencyRate = $response['data']['rate'];
        $rate = $tokenRate / $currencyRate;
        
        return Preacher::receipt((object) [
            'rate' => $rate,
        ])->export()->json();
    }
    
}
