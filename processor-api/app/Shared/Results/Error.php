<?php
namespace App\Shared\Results;

use App\Shared\Enums\ErrorType;

readonly class Error
{
    public function __construct(
        public string $code,
        public ErrorType $type,
        public string $description,
        public ?string $detail = null
    ) {}

    public static function none(): self
    {
        return new self('', ErrorType::FAILURE, '');
    }

    public function toHttpStatus(): int
    {
        return $this->type->toHttpStatus();
    }
}
