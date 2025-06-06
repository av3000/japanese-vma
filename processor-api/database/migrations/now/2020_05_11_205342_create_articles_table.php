<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->increments("id");
            $table->integer("user_id");
            $table->smallInteger('status')->default(0);
            $table->boolean('publicity')->default(0);
            $table->string("title_en")->default("");
            $table->text("content_en")->default("");
            $table->string("title_jp");
            $table->text("content_jp");
            $table->string("source_link");
            $table->string("n1")->default("0");
            $table->string("n2")->default("0");
            $table->string("n3")->default("0");
            $table->string("n4")->default("0");
            $table->string("n5")->default("0");
            $table->string("uncommon")->default("0");
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
