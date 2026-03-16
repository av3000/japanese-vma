<?php

namespace App\Http\v1\Concerns;

use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\Request;

trait ResolvesOptionalApiUser
{
    protected function resolveOptionalApiUser(Request $request): ?User
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken === null || trim($bearerToken) === '') {
            return null;
        }

        return auth('api')->user();
    }

    protected function resolveOptionalApiUserId(Request $request): ?int
    {
        return $this->resolveOptionalApiUser($request)?->id;
    }
}
