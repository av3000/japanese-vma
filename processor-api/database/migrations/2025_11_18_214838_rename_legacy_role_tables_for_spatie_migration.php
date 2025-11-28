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
        Schema::rename('roles', 'roles_legacy');
        Schema::rename('user_role', 'user_role_legacy');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('roles_legacy', 'roles');
        Schema::rename('user_role_legacy', 'user_role');
    }
};
