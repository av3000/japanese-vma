<?php

declare(strict_types=1);

namespace App\Domain\Users\DTOs;

/**
 * An administrator's create or update of an account. A null password leaves the stored one
 * unchanged; on create it is required by the caller's validation.
 */
final readonly class UserAccountWriteDTO
{
    public function __construct(
        public string $name,
        public string $email,
        #[\SensitiveParameter]
        public ?string $password = null,
    ) {
    }
}
