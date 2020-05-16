<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomlistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customlists', function (Blueprint $table) {
            $table->increments("id");
            $table->integer("user_id")->unsigned();
            $table->integer("status");
            $table->integer("type");
            $table->string("title");
            $table->integer("n1")->default(0);
            $table->integer("n2")->default(0);
            $table->integer("n3")->default(0);
            $table->integer("n4")->default(0);
            $table->integer("n5")->default(0);
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
        Schema::dropIfExists('customlists');
    }
}
