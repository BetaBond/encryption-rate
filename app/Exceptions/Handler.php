<?php

namespace App\Exceptions;

use Colorful\Preacher\Preacher;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Exception handler
 *
 * @namespace App\Exceptions
 */
class Handler extends ExceptionHandler
{
    
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels
        = [
            //
        ];
    
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport
        = [
            //
        ];
    
    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash
        = [
            'current_password',
            'password',
            'password_confirmation',
        ];
    
    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    
    /**
     * Render
     *
     * @param $request
     * @param  Throwable  $e
     *
     * @return HttpResponse|JsonResponse|Response
     * @throws Throwable
     */
    public function render(
        $request,
        Throwable $e
    ): HttpResponse|JsonResponse|Response {
        $classArray = explode(DIRECTORY_SEPARATOR, get_class($e));
        
        return match (end($classArray)) {
            'AccessDeniedHttpException' => Preacher::msgCode(
                Preacher::RESP_CODE_ACCESS_DENIED,
                '访问被拒绝'
            )->export()->json(),
            
            default => parent::render($request, $e)
        };
    }
    
    /**
     * Creates a response object from the given validation exception.
     *
     * @param  ValidationException  $e
     * @param  Request  $request
     *
     * @return Response
     */
    protected function convertValidationExceptionToResponse(
        ValidationException $e,
        $request
    ): Response {
        if ($e->response) {
            return $e->response;
        }
        
        return Preacher::msgCode(
            Preacher::RESP_CODE_WARN,
            $e->validator->errors()->first()
        )->export()->json();
    }
    
}