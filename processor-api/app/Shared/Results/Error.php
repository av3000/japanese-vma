<?php

namespace App\Shared\Results;

use App\Shared\Enums\HttpStatus;

readonly class Error
{
    public function __construct(
        public string $code,
        public HttpStatus $status,
        public string $description,
        public ?string $detail = null,
        public ?string $errorMessage = null
    ) {}

    public static function none(): self
    {
        return new self('', HttpStatus::INTERNAL_SERVER_ERROR, '');
    }

    public function toHttpStatus(): HttpStatus
    {
        return $this->status;
    }
}
