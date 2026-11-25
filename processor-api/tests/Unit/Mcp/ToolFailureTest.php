<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use App\Mcp\Support\ToolFailure;
use App\Shared\Enums\HttpStatus;
use App\Shared\Results\Result;
use App\Shared\Results\ResultError;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ToolFailureTest extends TestCase
{
    public function test_a_validation_failure_names_each_field_with_its_message(): void
    {
        $exception = $this->validationFailure(
            ['query' => '', 'limit' => 21],
            ['query' => ['required'], 'limit' => ['integer', 'max:20']],
            ['query.required' => 'is required.', 'limit.max' => 'must be between 1 and 20.'],
        );

        $response = ToolFailure::fromValidation($exception);

        $this->assertTrue($response->isError());
        $this->assertSame(
            "query: is required.\nlimit: must be between 1 and 20.",
            $response->content()->toArray()['text'],
        );
    }

    public function test_a_client_error_result_passes_its_message_on(): void
    {
        $failure = Result::failure(new ResultError(
            'WORD_NOT_FOUND',
            HttpStatus::NOT_FOUND,
            "Word with identifier 'x' not found.",
        ));

        $response = ToolFailure::fromResult($failure);

        $this->assertTrue($response->isError());
        $this->assertSame("Word with identifier 'x' not found.", $response->content()->toArray()['text']);
    }

    public function test_an_error_message_wins_over_the_description(): void
    {
        $failure = Result::failure(new ResultError(
            'INVALID',
            HttpStatus::BAD_REQUEST,
            'Invalid.',
            errorMessage: 'The identifier must be a UUID.',
        ));

        $this->assertSame(
            'The identifier must be a UUID.',
            ToolFailure::fromResult($failure)->content()->toArray()['text'],
        );
    }

    public function test_a_server_error_result_does_not_leak_its_description(): void
    {
        $failure = Result::failure(new ResultError(
            'DB_DOWN',
            HttpStatus::INTERNAL_SERVER_ERROR,
            'SQLSTATE[08006] connection refused on host db-prod-1',
        ));

        $response = ToolFailure::fromResult($failure);

        $this->assertTrue($response->isError());
        $this->assertStringNotContainsString('SQLSTATE', $response->content()->toArray()['text']);
        $this->assertStringContainsString('Try again later', $response->content()->toArray()['text']);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<int, string>> $rules
     * @param array<string, string> $messages
     */
    private function validationFailure(array $data, array $rules, array $messages): ValidationException
    {
        try {
            Validator::validate($data, $rules, $messages);
        } catch (ValidationException $exception) {
            return $exception;
        }

        $this->fail('The data should have failed validation.');
    }
}
