<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$this->roleService->isAdmin(new EntityId($user->uuid))) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
