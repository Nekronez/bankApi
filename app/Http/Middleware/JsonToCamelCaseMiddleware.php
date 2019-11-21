<?php

namespace App\Http\Middleware;

use Closure;
use Log;
use Illuminate\Support\Str;

class JsonToCamelCaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $array = json_decode($response->content(),true);
        $array = $this->convertKeysToCamelCase($array);
        $response->setContent(json_encode($array));
        
        return $response;
    }

    /**
     * Convert array keys in camelcase.
     *
     * @param  array()  $apiResponseArray
     * @return array()
     */
    private function convertKeysToCamelCase($apiResponseArray)
    {
        $arr = [];
        foreach ($apiResponseArray as $key => $value) {
            $key = Str::camel($key);

            if (is_array($value))
                $value = $this->convertKeysToCamelCase($value);

            $arr[$key] = $value;
        }
        return $arr;
    }
}
