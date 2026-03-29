<?php

namespace Tests\Unit\Shared\Results;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\FailureResult;
use App\Shared\Results\Result;
use App\Shared\Results\ResultError;
use App\Shared\Results\SuccessResult;
use PHPUnit\Framework\TestCase;

class ResultTypesTest extends TestCase
{
    public function test_result_factories_use_split_result_types_and_result_error(): void
    {
        $error = new ResultError(
            code: 'Catalogues.CreationFailed',
            status: HttpStatus::BAD_REQUEST,
            description: 'Catalogue creation failed',
        );

        $success = Result::success(['uuid' => '123']);
        $failure = Result::failure($error);

        $this->assertInstanceOf(SuccessResult::class, $success);
        $this->assertSame(['uuid' => '123'], $success->getData());

        $this->assertInstanceOf(FailureResult::class, $failure);
        $this->assertSame($error, $failure->getError());
    }
}
