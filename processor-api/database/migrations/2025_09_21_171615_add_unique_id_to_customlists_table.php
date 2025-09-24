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
        Schema::table('customlists', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('id');
        });

        $lists = DB::table('customlists')
            ->whereNull('uuid')
            ->select('id')
            ->get();

        foreach ($lists as $list) {
            DB::table('customlists')
                ->where('id', $list->id)
                ->update(['uuid' => ObjectTemplateType::ARTICLE->value]);
        }

        // Log how many were updated
        $updatedCount = $lists->count();
        if ($updatedCount > 0) {
            \Log::info("Generated UUIDs for {$updatedCount} customlists records");
        }

        Schema::table('customlists', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->change();
        });

        Schema::table('customlists', function (Blueprint $table) {
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customlists', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
