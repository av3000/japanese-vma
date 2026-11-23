<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => User::factory(),
            'type' => (string) PostTopic::OFF_TOPIC->value,
            'title' => 'Post title '.fake()->numberBetween(1, 99999),
            'content' => fake()->paragraph(),
            'locked' => false,
        ];
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }

    public function topic(PostTopic $topic): static
    {
        return $this->state(fn (): array => ['type' => (string) $topic->value]);
    }

    public function locked(): static
    {
        return $this->state(fn (): array => ['locked' => true]);
    }
}
