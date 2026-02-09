<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\v1\Articles\Requests\UpdateArticleRequest;

class UpdateArticleRequestTest extends TestCase
{
    public function test_has_any_updateable_fields_returns_false_for_empty_payload(): void
    {
        $request = UpdateArticleRequest::create('/', 'PUT', []);

        $this->assertFalse($request->hasAnyUpdateableFields());
    }

    public function test_has_any_updateable_fields_accepts_legacy_tags_alias(): void
    {
        $request = UpdateArticleRequest::create('/', 'PUT', [
            'tags' => ['#legacy']
        ]);

        $this->assertTrue($request->hasAnyUpdateableFields());
    }
}
