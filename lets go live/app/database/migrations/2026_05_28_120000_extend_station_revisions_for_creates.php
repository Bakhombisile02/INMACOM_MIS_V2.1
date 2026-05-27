<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing FK to allow making station_id nullable.
        Schema::table('station_revisions', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
        });

        Schema::table('station_revisions', function (Blueprint $table) {
            $table->uuid('station_id')->nullable()->change();
            $table->string('change_type', 20)->default('update')->after('status');
            $table->boolean('is_self_override')->default(false)->after('reviewed_at');

            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
        });

        // Backfill change_type for any existing rows (defensive — default already covers).
        DB::table('station_revisions')
            ->whereNull('change_type')
            ->update(['change_type' => 'update']);
    }

    public function down(): void
    {
        Schema::table('station_revisions', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
        });

        Schema::table('station_revisions', function (Blueprint $table) {
            $table->dropColumn(['change_type', 'is_self_override']);
            $table->uuid('station_id')->nullable(false)->change();
            $table->foreign('station_id')->references('id')->on('stations')->cascadeOnDelete();
        });
    }
};
