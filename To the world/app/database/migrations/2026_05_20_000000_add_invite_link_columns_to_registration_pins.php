<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_pins', function (Blueprint $table) {
            $table->uuid('link_token')->nullable()->unique()->after('note');
            $table->timestamp('link_opened_at')->nullable()->after('link_token');
            $table->string('sent_to_email')->nullable()->after('link_opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('registration_pins', function (Blueprint $table) {
            $table->dropColumn(['link_token', 'link_opened_at', 'sent_to_email']);
        });
    }
};
