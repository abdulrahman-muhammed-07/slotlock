<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use App\Tenancy\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for the request from the bearer token and binds it into
 * the request-scoped CurrentCompany. Everything downstream (global scope,
 * services) reads the tenant from there, never from the request body.
 */
final class ResolveCompany
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $company = $token !== null
            ? Company::query()->where('api_token', $token)->first()
            : null;

        if ($company === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $this->currentCompany->set($company);

        return $next($request);
    }
}
