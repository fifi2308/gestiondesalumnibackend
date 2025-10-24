<?php

namespace App\Http\Middleware;

use Closure;

class HandleCors
{
    /**
     * Gère les en-têtes CORS pour toutes les requêtes.
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // 🔥 Autoriser ton frontend Angular
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:4200');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        // Si c’est une requête OPTIONS (pré-vol)
        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(200);
        }

        return $response;
    }
}
