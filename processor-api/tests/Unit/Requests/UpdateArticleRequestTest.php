<?php

namespace Tests\Unit\Requests;

use App\Http\v1\Articles\Requests\UpdateArticleRequest;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateArticleRequestTest extends TestCase
{
    public function test_empty_payload_fails_with_validation_problem_response(): void
    {
        $request = $this->makeRequest([]);

        try {
            $request->validateResolved();
            $this->fail('Expected empty article update request to fail validation.');
        } catch (HttpResponseException $exception) {
            $response = $exception->getResponse();

            $this->assertSame(422, $response->getStatusCode());
            $this->assertSame('No fields to update', $response->getData(true)['title']);
            $this->assertSame(
                'At least one field must be provided for update operation',
                $response->getData(true)['errors']['fields'][0]
            );
        }
    }

    public function test_legacy_tags_alias_is_accepted_as_updateable_field_and_normalized(): void
    {
        $request = $this->makeRequest([
            'tags' => ['#legacy'],
        ]);

        $request->validateResolved();

        $this->assertSame(['#legacy'], $request->validated('hashtags'));
    }

    public function test_normal_validation_errors_take_precedence_over_empty_update_response(): void
    {
        $request = $this->makeRequest([
            'title_jp' => 'x',
        ]);

        try {
            $request->validateResolved();
            $this->fail('Expected invalid title to fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('title_jp', $exception->errors());
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function makeRequest(array $payload): UpdateArticleRequest
    {
        $request = UpdateArticleRequest::create('/api/v1/articles/'.Str::uuid(), 'PUT', $payload);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        auth('api')->setUser(new User);

        return $request;
    }
}
