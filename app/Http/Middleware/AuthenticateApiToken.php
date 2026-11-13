<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Resolve the mobile bearer token to a user and attach it to the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! is_string($plainToken) || $plainToken === '') {
            return $this->unauthorized('Authentication required.');
        }

        $token = ApiToken::where('token', ApiToken::hashToken($plainToken))->first();

        if ($token === null) {
            return $this->unauthorized('Your session has expired. Please sign in again.');
        }

        $user = User::find($token->user_id);

        if ($user === null) {
            $token->delete();

            return $this->unauthorized('Your session has expired. Please sign in again.');
        }

        $token->forceFill(['last_used_at' => now()])->save();

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }
}
