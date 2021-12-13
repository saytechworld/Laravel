<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\ParameterBag;

class HandlePutFormData
{
     /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */

    public function handle($request, Closure $next)
    {
        if ($request->method() == 'POST' or $request->method() == 'GET') {
            if(preg_match('/application\/x-www-form-urlencoded/', $request->headers->get('Content-Type')))
            {
                // Fetch content and determine boundary
                $encodedJsonData  = file_get_contents('php://input');
                if(!empty($encodedJsonData))
                {
                    $decodedFormData = json_decode($encodedJsonData,true);
                    if(!empty($decodedFormData))
                    {
                        // Input modification
                        $request->replace($decodedFormData);
                    }
                }
            }
        }
        return $next($request);
    }
}
