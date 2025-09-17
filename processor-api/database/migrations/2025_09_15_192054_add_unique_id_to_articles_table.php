<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->uuid('unique_id')->nullable()->after('id');
        });


        //  populate existing records with unique IDs
        DB::table('articles')->orderBy('id')->chunkById(100, function ($articles) {
            foreach ($articles as $article) {
                DB::table('articles')
                    ->where('id', $article->id)
                    ->update(['unique_id' => Str::uuid()->toString()]);
            }
        });

        // Make it required and unique
        Schema::table('articles', function (Blueprint $table) {
            $table->uuid('unique_id')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('unique_id');
        });
    }
};
