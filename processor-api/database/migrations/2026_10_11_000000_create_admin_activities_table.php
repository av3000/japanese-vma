<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #181: append-only log of privileged mutations. Rows are immutable, so there is no
 * `updated_at`. Actor name and subject label are snapshots that survive renames and deletes.
 * No IP address is stored; it is personal data the log does not need.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('action', 64);
            $table->string('subject_type', 32);
            $table->string('subject_id', 64);
            $table->string('subject_label')->nullable();
            $table->json('changes')->nullable();
            $table->string('source', 16);
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activities');
    }
};
