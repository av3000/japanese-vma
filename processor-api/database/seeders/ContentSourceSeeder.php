<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\ContentSource;
use Illuminate\Database\Seeder;

/**
 * Production-safe and idempotent: registers the Content Sources the code ships adapters for.
 * An existing row keeps its `enabled` flag, so re-seeding never undoes an operator's kill switch.
 */
class ContentSourceSeeder extends Seeder
{
    public const NHK_NEWS = 'nhk-news';

    public function run(): void
    {
        ContentSource::firstOrCreate(
            ['key' => self::NHK_NEWS],
            [
                'name' => 'NHK News',
                'homepage_url' => 'https://news.web.nhk/newsweb',
                'enabled' => true,
            ],
        );
    }
}
