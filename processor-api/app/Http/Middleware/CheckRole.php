<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param string ...$roles One or more role names to check
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required'
            ], 401);
        }

        $userUuid = new EntityId($user->uuid);

        foreach ($roles as $role) {
            if ($this->roleService->userHasRole($userUuid, $role)) {
                return $next($request);
            }
        }

        return response()->json([
            'error' => 'Forbidden',
            'message' => 'Insufficient permissions. Required roles: ' . implode(', ', $roles)
        ], 403);
    }
}
