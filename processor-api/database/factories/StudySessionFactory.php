<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Study\Enums\AnswerMode;
use App\Domain\Study\Enums\FlashcardField;
use App\Domain\Study\Enums\ScriptStrictness;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\StudySession;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StudySessionFactory extends Factory
{
    protected $model = StudySession::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'catalogue_id' => Catalogue::factory()->ofType(SavedListType::KANJIS),
            'catalogue_type' => SavedListType::KANJIS->value,
            'prompt_field' => FlashcardField::CHARACTER->value,
            'answer_field' => FlashcardField::MEANING->value,
            'answer_mode' => AnswerMode::OPTIONS->value,
            'script_strictness' => ScriptStrictness::STRICT->value,
            'card_count' => 10,
            'correct_count' => null,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function forCatalogue(Catalogue $catalogue): static
    {
        return $this->state(fn (): array => [
            'catalogue_id' => $catalogue->id,
            'catalogue_type' => $catalogue->type->value,
            'user_id' => $catalogue->user_id,
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }

    public function completed(int $correctCount = 7): static
    {
        return $this->state(fn (): array => [
            'correct_count' => $correctCount,
            'completed_at' => now(),
        ]);
    }
}
