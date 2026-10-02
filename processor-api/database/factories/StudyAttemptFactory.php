<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\StudyAttempt;
use App\Infrastructure\Persistence\Models\StudySession;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudyAttemptFactory extends Factory
{
    protected $model = StudyAttempt::class;

    public function definition(): array
    {
        return [
            'session_id' => StudySession::factory(),
            'item_id' => fake()->numberBetween(1, 10000),
            'entity_type_uuid' => ObjectTemplateType::KANJI->value,
            'attempt_no' => 1,
            'given_answer' => 'study',
            'expected_answers' => ['study', 'learning'],
            'is_correct' => true,
            'response_ms' => 2500,
            'answered_at' => now(),
        ];
    }
}
