<?php

declare(strict_types=1);

namespace App\Application\Auth\Interfaces\Providers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Domain\Users\Models\User;

interface CurrentUserProviderInterface
{
    public function currentAuthenticatedUser(): ?AuthenticatedUser;

    public function currentUser(): ?User;

    public function currentAccessTokenId(): ?string;
}
