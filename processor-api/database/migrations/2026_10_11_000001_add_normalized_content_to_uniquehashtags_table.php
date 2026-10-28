<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #187: the key two tags are compared on (HashtagName::keyFor - NFKC fold, then lower case),
 * backfilled in PHP so it matches what new writes store exactly.
 *
 * Report first: existing tags may already collide (`#JLPT` and `#jlpt`), so the index is not
 * unique yet. Collisions are logged here, listed by `php artisan hashtags:duplicates`, and merged
 * in the admin panel; a follow-up migration makes the index unique once production is clean.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uniquehashtags', function (Blueprint $table): void {
            $table->string('normalized_content')->nullable()->after('content');
            $table->index('normalized_content', 'uniquehashtags_normalized_content_index');
        });

        DB::table('uniquehashtags')->orderBy('id')->chunkById(500, function ($tags): void {
            foreach ($tags as $tag) {
                DB::table('uniquehashtags')
                    ->where('id', $tag->id)
                    ->update(['normalized_content' => $this->normalise((string) $tag->content)]);
            }
        });

        $collisions = DB::table('uniquehashtags')
            ->select('normalized_content')
            ->groupBy('normalized_content')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($collisions > 0) {
            Log::warning("uniquehashtags: {$collisions} normalised keys are shared by more than one tag. Run `php artisan hashtags:duplicates` and merge them in the admin panel.");
        }
    }

    /**
     * Frozen copy of HashtagName::keyFor() as it was when this migration ran. A migration must not
     * depend on application code that can change later, or replaying it on a fresh database would
     * store keys production never had.
     */
    private function normalise(string $value): string
    {
        $folded = Normalizer::normalize(trim($value), Normalizer::FORM_KC);

        return mb_strtolower($folded === false ? trim($value) : $folded);
    }

    public function down(): void
    {
        Schema::table('uniquehashtags', function (Blueprint $table): void {
            $table->dropIndex('uniquehashtags_normalized_content_index');
            $table->dropColumn('normalized_content');
        });
    }
};
