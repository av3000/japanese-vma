<?php

namespace App\Shared\Results;

readonly class SuccessResult extends Result
{
    public function __construct(public mixed $data = null) {}

    public function isSuccess(): bool
    {
        return true;
    }

    public function isFailure(): bool
    {
        return false;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getError(): ResultError
    {
        throw new \LogicException('Success result has no error');
    }
}
