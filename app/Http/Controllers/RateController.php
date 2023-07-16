<?php

namespace App\Http\Controllers;

use Colorful\Preacher\Preacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rules\Password;
use Psr\SimpleCache\InvalidArgumentException;

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
    const LEGAL_TENDER_API = 'https://api.exchangerate-api.com/v4/latest/USD';
    
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
        
        $tokenRate = 1;
        if ($requestParams['token'] !== 'usdt') {
            $response = Http::get(self::HUOBI_API, [
                'symbol' => $requestParams['token'].'usdt',
            ]);
            
            if (!is_array($response->json())) {
                return Preacher::msgCode(
                    Preacher::RESP_CODE_FAIL,
                    '火币接口异常'
                )->export()->json();
            }
            
            $response = $response->json();
            $response = (array) $response;
            
            if (!isset($response['status']) || $response['status'] !== 'ok') {
                return Preacher::msgCode(
                    Preacher::RESP_CODE_FAIL,
                    '火币接口数据异常',
                )->export()->json();
            }
            
            $tokenRate = $response['tick']['close'];
        }
        
        $requestParams['currency'] = strtoupper($requestParams['currency']);
        $currencyKey = $requestParams['currency'].'/USD';
        
        $currencyCache = null;
        
        try {
            $has = Cache::store('redis')->has($currencyKey);
        } catch (InvalidArgumentException $e) {
            return Preacher::msgCode(
                Preacher::RESP_CODE_FAIL,
                $e->getMessage(),
            )->export()->json();
        }
        
        // 不存在则请求
        if (!$has) {
            $response = Http::get(self::LEGAL_TENDER_API);
            
            if (!is_array($response->json())) {
                return Preacher::msgCode(
                    Preacher::RESP_CODE_FAIL,
                    '汇率接口异常'
                )->export()->json();
            }
            
            $response = $response->json();
            $response = (array) $response;
            
            if (!is_array($response['rates'])) {
                return Preacher::msgCode(
                    Preacher::RESP_CODE_FAIL,
                    '汇率接口数据异常'
                )->export()->json();
            }
            
            if (!isset($requestParams['currency'])) {
                return Preacher::msgCode(
                    Preacher::RESP_CODE_FAIL,
                    '法币错误或不存在'
                )->export()->json();
            }
            
            $currencyRate = $response['rates'][$requestParams['currency']];
            
            $currencyCache = Cache::store(
                'redis'
            )->put(
                $currencyKey,
                $currencyRate,
                30
            );
        }
        
        if ($has) {
            try {
                $currencyRate = (float) Cache::store(
                    'redis'
                )->get($currencyKey);
            } catch (InvalidArgumentException $e) {
                return Preacher::msgCode(
                    Preacher::RESP_CODE_FAIL,
                    $e->getMessage(),
                )->export()->json();
            }
        }
        
        $rate = $tokenRate * $currencyRate;
        
        return Preacher::receipt((object) [
            'rate'     => $rate,
            'currency' => $currencyRate,
            'token'    => $tokenRate,
            'cache'    => [
                'currency' => $currencyCache,
            ],
        ])->export()->json();
    }
    
}
