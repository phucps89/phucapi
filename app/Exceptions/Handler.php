<?php

namespace App\Exceptions;

use App\Services\Response\ResponseFacade;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        switch (get_class($e)) {
            case TokenExpiredException::class :
                return ResponseFacade::send('Token is expired', Response::HTTP_UNAUTHORIZED, true);
            case TokenInvalidException::class :
                return ResponseFacade::send('Invalid token', Response::HTTP_UNAUTHORIZED, true);
            case JWTException::class :
                return ResponseFacade::send('Token not found', Response::HTTP_UNAUTHORIZED);
            case UnauthorizedHttpException::class :
                return ResponseFacade::send('Invalid token', Response::HTTP_UNAUTHORIZED, true);
            case NotFoundHttpException::class :
                return ResponseFacade::send('Route not found', Response::HTTP_NOT_FOUND);
            case MethodNotAllowedHttpException::class :
                return ResponseFacade::send('Method not allowed', Response::HTTP_METHOD_NOT_ALLOWED);
            case BadRequestHttpException::class :
                return ResponseFacade::send($e->getMessage(), Response::HTTP_BAD_REQUEST);
            case ValidationException::class:
                return ResponseFacade::send($e->getResponse(), Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
            default :
                //print_r(array_slice($e->getTrace(), 0, 4));exit;
                return ResponseFacade::send($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
