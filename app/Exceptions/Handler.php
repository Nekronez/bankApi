<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Illuminate\Http\JsonResponse;
use Log;

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
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
		$parentRender = parent::render($request, $e);
        Log::info('ERRORmessage: '.$e );


	// if parent returns a JsonResponse
   	// for example in case of a ValidationException
   	if ($parentRender instanceof JsonResponse)
	{
            return $parentRender;
        }

	$eMessage="";
	switch ($parentRender->status()){
	    case 405:
		$eMessage="Method not allowed";
		break;
	    case 404:
		$eMessage="Not found";
                break;

	}
        return new JsonResponse([
            'errorMessage' => $e instanceof HttpException
                ? $e->getMessage().$eMessage
                : 'Server Error',
        ], $parentRender->status());
    }
}
