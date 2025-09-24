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
        Schema::table('articles', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('id');
        });

         DB::table('articles')->update([
            'uuid' => ObjectTemplateType::ARTICLE->value,
        ]);

        Schema::table('articles', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->change();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
