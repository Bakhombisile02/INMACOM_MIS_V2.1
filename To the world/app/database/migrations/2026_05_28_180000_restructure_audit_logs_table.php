<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Drop old thin columns
            $table->dropColumn(['action', 'table_name', 'record_id', 'before_status', 'after_status']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            // Add aligned columns
            $table->string('action_type')->after('actor_id');
            $table->string('entity_type')->after('action_type');
            $table->string('entity_id')->after('entity_type');
            $table->string('entity_label')->nullable()->after('entity_id');
            $table->json('previous_state')->nullable()->after('entity_label');
            $table->json('new_state')->nullable()->after('previous_state');
            $table->string('actor_ip', 45)->nullable()->after('reason');

            $table->index(['actor_id', 'occurred_at']);
            $table->index(['action_type', 'occurred_at']);
            $table->index('entity_type');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['actor_id', 'occurred_at']);
            $table->dropIndex(['action_type', 'occurred_at']);
            $table->dropIndex(['entity_type']);
            $table->dropColumn(['action_type', 'entity_type', 'entity_id', 'entity_label', 'previous_state', 'new_state', 'actor_ip']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action')->after('actor_id');
            $table->string('table_name')->after('action');
            $table->string('record_id')->after('table_name');
            $table->string('before_status')->nullable()->after('record_id');
            $table->string('after_status')->nullable()->after('before_status');
        });
    }
};
