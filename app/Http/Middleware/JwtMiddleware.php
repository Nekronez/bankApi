<?php
namespace App\Http\Middleware;
use Closure;
use Exception;
use App\User;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Log;

class JwtMiddleware
{
    public function handle($request, Closure $next, $tokenType)
    {
        $arrayAuthorization = explode(' ', $request->header('Authorization'));
        if(count($arrayAuthorization) != 2){
            return response()->json([
                'errorMessage' => 'Token not provided.'
            ], 401);
        }
        
        $token = $arrayAuthorization[1];

        if(!$token) {
            // Unauthorized response if token not there
            return response()->json([
                'errorMessage' => 'Token not provided.'
            ], 401);
        }
        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return response()->json([
                'errorMessage' => 'Provided token is expired.'
            ], 401);
        } catch(Exception $e) {
            return response()->json([
                'errorMessage' => 'Invalid token provided.'
            ], 401);
        }
        
        if($tokenType != $credentials->typ){
            return response()->json([
                'errorMessage' => 'Invalid token provided.'
            ], 401);
        }

        if($tokenType == 'session'){
            $request->sms = $credentials->sms;
        }
        
        $user = User::find($credentials->sub);

        // Now let's put the user in the request class so that you can grab it from there
        $request->auth = $user;

        return $next($request);
    }
}
