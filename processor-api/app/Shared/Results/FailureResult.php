<?php

namespace App\Shared\Results;

readonly class FailureResult extends Result
{
    public function __construct(public ResultError $error) {}

    public function isSuccess(): bool
    {
        return false;
    }

    public function isFailure(): bool
    {
        return true;
    }

    public function getData(): mixed
    {
        throw new \LogicException('Failure result has no data');
    }

    public function getError(): ResultError
    {
        return $this->error;
    }
}
