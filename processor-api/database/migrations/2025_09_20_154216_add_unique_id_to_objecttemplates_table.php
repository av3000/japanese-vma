<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('objecttemplates', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('id');
        });

        $templates = [
            ['id' => 1, 'title' => 'article', 'uuid' => 'ad69baf6-1a1f-42bd-8176-74ab5fbd69bd'],
            ['id' => 2, 'title' => 'artist', 'uuid' => '3105a1ce-c06f-4016-bf5b-b5287a023fd5'],
            ['id' => 3, 'title' => 'lyric', 'uuid' => '2ce2d586-169a-4e41-9cdd-251e93fde5e2'],
            ['id' => 4, 'title' => 'radical', 'uuid' => 'e7367bcb-114e-4e89-b17f-810dfe87a3dc'],
            ['id' => 5, 'title' => 'kanji', 'uuid' => '6cd99a38-fa88-4558-9f68-0f2162576f36'],
            ['id' => 6, 'title' => 'word', 'uuid' => 'd912962b-519e-4717-bcde-2cdd9fa00d37'],
            ['id' => 7, 'title' => 'sentence', 'uuid' => '91e47d5f-f994-4a9a-b1fc-53d63393bb70'],
            ['id' => 8, 'title' => 'list', 'uuid' => '93edeaab-85d0-44ad-ba2d-4602ab4061ba'],
            ['id' => 9, 'title' => 'post', 'uuid' => 'a4b78a83-f180-49b5-9f8a-39500cd8fabf'],
            ['id' => 10, 'title' => 'comment', 'uuid' => '5ee9d6b7-aaae-4e0e-b63d-eae66ea49aef'],
        ];

        foreach ($templates as $template) {
            DB::table('objecttemplates')
                ->where('id', $template['id'])
                ->update(['uuid' => $template['uuid']]);
        }

        Schema::table('objecttemplates', function (Blueprint $table) {
            $table->string('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objecttemplates', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
