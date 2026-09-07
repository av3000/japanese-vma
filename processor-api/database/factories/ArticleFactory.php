<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title_jp' => '記事タイトル'.fake()->numberBetween(1, 99999),
            'title_en' => fake()->sentence(4),
            'content_jp' => '日本語の本文です。'.fake()->paragraph(),
            'content_en' => fake()->paragraphs(2, true),
            'source_link' => fake()->url(),
            'publicity' => PublicityStatus::PUBLIC,
            'status' => ArticleStatus::APPROVED,
            'user_id' => User::factory(),
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'n4' => 0,
            'n5' => 0,
            'uncommon' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => ArticleStatus::PENDING]);
    }

    public function asPrivate(): static
    {
        return $this->state(fn (): array => ['publicity' => PublicityStatus::PRIVATE]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }
}
