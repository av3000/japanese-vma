<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #409: the allow-list that turns a source's own genres and topics into the platform's
 * hashtags. Anything not listed here is dropped, so imports cannot fragment the tag space with
 * programme names or one-off topics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_tag_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_source_id')->constrained('content_sources')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('external_key', 191);
            $table->string('hashtag', 50);
            $table->timestamps();

            $table->unique(['content_source_id', 'kind', 'external_key'], 'source_tag_mappings_source_kind_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_tag_mappings');
    }
};
