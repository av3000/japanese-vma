<?php

namespace App\Shared\Results;

abstract readonly class Result
{
    public static function success(mixed $data = null): SuccessResult
    {
        return new SuccessResult($data);
    }

    public static function failure(ResultError $error): FailureResult
    {
        return new FailureResult($error);
    }

    abstract public function isSuccess(): bool;
    abstract public function isFailure(): bool;

    abstract public function getData(): mixed;
    abstract public function getError(): ResultError;
}
