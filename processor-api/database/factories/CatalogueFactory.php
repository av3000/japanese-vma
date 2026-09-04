<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CatalogueFactory extends Factory
{
    protected $model = Catalogue::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'publicity' => true,
            'type' => SavedListType::ARTICLES,
            'user_id' => User::factory(),
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
        ];
    }

    public function ofType(SavedListType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function asPrivate(): static
    {
        return $this->state(fn (): array => ['publicity' => false]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }
}
