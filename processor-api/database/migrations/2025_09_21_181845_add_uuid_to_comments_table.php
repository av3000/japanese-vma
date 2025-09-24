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
       Schema::table('comments', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('id');
        });

        $comments = DB::table('comments')
            ->whereNull('uuid')
            ->select('id')
            ->get();

        foreach ($comments as $comment) {
            DB::table('comments')
                ->where('id', $comment->id)
                ->update(['uuid' => ObjectTemplateType::COMMENT->value]);
        }

        $updatedCount = $comments->count();
        if ($updatedCount > 0) {
            \Log::info("Generated UUIDs for {$updatedCount} comments records");
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->change();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
