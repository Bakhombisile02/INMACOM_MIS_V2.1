<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('commentable_type');
            $table->uuid('commentable_id');
            $table->uuid('parent_id')->nullable();
            $table->string('field_name')->nullable();
            $table->uuid('author_id');
            $table->text('body');
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by_id')->nullable();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index('parent_id');
            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('resolved_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('comments')->nullOnDelete();
        });

        Schema::create('comment_mentions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('comment_id');
            $table->uuid('mentioned_user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['comment_id', 'mentioned_user_id']);
            $table->index(['mentioned_user_id', 'read_at']);
            $table->foreign('comment_id')->references('id')->on('comments')->cascadeOnDelete();
            $table->foreign('mentioned_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('station_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('station_id');
            $table->uuid('submitted_by_id');
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->json('proposed_changes');
            $table->uuid('reviewed_by_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['station_id', 'status']);
            $table->index('status');
            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
            $table->foreign('submitted_by_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_revisions');
        Schema::dropIfExists('comment_mentions');
        Schema::dropIfExists('comments');
    }
};
