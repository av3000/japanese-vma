<?php
namespace App\Shared\Results;

use App\Shared\Results\Error;

abstract readonly class Result
{
    public static function success(mixed $data = null): SuccessResult
    {
        return new SuccessResult($data);
    }

    public static function failure(Error $error): FailureResult
    {
        return new FailureResult($error);
    }

    abstract public function isSuccess(): bool;
    abstract public function isFailure(): bool;
}

readonly class SuccessResult extends Result
{
    public function __construct(public mixed $data = null) {}

    public function isSuccess(): bool { return true; }
    public function isFailure(): bool { return false; }
}

readonly class FailureResult extends Result
{
    public function __construct(public Error $error) {}

    public function isSuccess(): bool { return false; }
    public function isFailure(): bool { return true; }
}
