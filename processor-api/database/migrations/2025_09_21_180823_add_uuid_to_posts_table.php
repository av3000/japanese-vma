<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Domain\Shared\Enums\ObjectTemplateType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('id');
        });

        $posts = DB::table('posts')
            ->whereNull('uuid')
            ->select('id')
            ->get();

        foreach ($posts as $post) {
            DB::table('posts')
                ->where('id', $post->id)
                ->update(['uuid' => ObjectTemplateType::POST->value]);
        }

        $updatedCount = $posts->count();
        if ($updatedCount > 0) {
            \Log::info("Generated UUIDs for {$updatedCount} posts records");
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
