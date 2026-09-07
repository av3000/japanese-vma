<?php

declare(strict_types=1);

namespace Tests\Support;

use Database\Seeders\ObjectTemplatesTableSeeder;
use Database\Seeders\RoleSeeder;

trait SeedsBaselineData
{
    protected function seedBaselineData(): void
    {
        $this->seed([
            RoleSeeder::class,
            ObjectTemplatesTableSeeder::class,
        ]);
    }
}
