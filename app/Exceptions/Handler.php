<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        // detect instance
        if ($exception instanceof  UnauthorizedHttpException ) {
            // detect previous instance
            if ($exception->getPrevious() instanceof  TokenExpiredException ) {
                return response()->json(['code'=>1024 ,'msg' => 'TOKEN_EXPIRED'], $exception->getStatusCode());
            } else if ($exception->getPrevious() instanceof TokenInvalidException) {
                return response()->json(['code'=>1024 ,'msg' => 'TOKEN_INVALID'], $exception->getStatusCode());
            } else if ($exception->getPrevious() instanceof TokenBlacklistedException) {
                return response()->json(['code'=>1024 ,'msg' => 'TOKEN_BLACKLISTED'], $exception->getStatusCode());
            } else {
                return response()->json(['code'=>1024 ,'msg' => "UNAUTHORIZED_REQUEST"], 401);
            }
        }
        return parent::render($request, $exception);
    }


//    protected function unauthenticated($request, AuthenticationException $exception) {
//        if ($request->expectsJson()) {
//            return response()->json(['error' => 'Unauthenticated.'], 401);
//        }
//        if(in_array('admin', $exception->guards())) {
//            return redirect()->guest('/admin/login');
//        }
//        return redirect()->guest('login');
//    }
}
