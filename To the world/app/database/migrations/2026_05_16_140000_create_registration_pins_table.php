<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_pins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 6)->unique();
            $table->string('role');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('reserved_for_email')->nullable();
            $table->string('reserved_for_uid')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['used_at', 'revoked_at']);
            $table->index('reserved_for_email');
            $table->index('reserved_for_uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_pins');
    }
};
