<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The earlier domain migration provisioned a legacy `documents` table
        // (paired with `folders`) that has been superseded by the redesigned
        // document library. No production data depends on it yet, so drop the
        // legacy tables to make room for the new schema.
        Schema::dropIfExists('documents');
        Schema::dropIfExists('folders');

        Schema::create('document_storages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('visibility');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('storage_id')->nullable()->constrained('document_storages')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->enum('media_type', ['documents', 'images', 'videos', 'audio', 'archives']);
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['visibility', 'media_type']);
            $table->index('storage_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_storages');
    }
};
