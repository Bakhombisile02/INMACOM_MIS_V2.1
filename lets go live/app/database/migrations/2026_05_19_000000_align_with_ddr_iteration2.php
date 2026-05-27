<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('water_quality_parameters') && ! Schema::hasColumn('water_quality_parameters', 'is_priority_pollutant')) {
            Schema::table('water_quality_parameters', function (Blueprint $table) {
                $table->boolean('is_priority_pollutant')->default(false);
            });
        }

        if (! Schema::hasTable('iima_eflow_key_points')) {
            Schema::create('iima_eflow_key_points', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->string('alternate_code')->nullable()->unique();
                $table->string('name');
                $table->string('river')->nullable();
                $table->string('country')->nullable();
                $table->uuid('subcatchment_id')->nullable();
                $table->uuid('station_id')->nullable();
                $table->float('latitude')->nullable();
                $table->float('longitude')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('note')->nullable();

                $table->foreign('subcatchment_id')->references('id')->on('management_areas')->nullOnDelete();
                $table->foreign('station_id')->references('id')->on('stations')->nullOnDelete();
            });
        }

        if (Schema::hasTable('iima_eflow_requirements') && ! Schema::hasColumn('iima_eflow_requirements', 'key_point_id')) {
            Schema::table('iima_eflow_requirements', function (Blueprint $table) {
                $table->uuid('key_point_id')->nullable();
                $table->foreign('key_point_id')->references('id')->on('iima_eflow_key_points')->nullOnDelete();
                $table->index('key_point_id');
            });
        }

        $this->backfillLegacyEflowKeyPoints();

        if (! Schema::hasTable('hazard_indicator_readings')) {
            Schema::create('hazard_indicator_readings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('indicator_id');
                $table->uuid('area_id')->nullable();
                $table->uuid('station_id')->nullable();
                $table->float('value');
                $table->string('unit')->nullable();
                $table->timestamp('observed_at');
                $table->float('score')->nullable();
                $table->integer('confidence_pct')->nullable();
                $table->string('data_source')->nullable();
                $table->text('notes')->nullable();
                $table->uuid('submitted_by_id')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->uuid('reviewed_by_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();

                $table->foreign('indicator_id')->references('id')->on('hazard_indicators');
                $table->foreign('area_id')->references('id')->on('management_areas')->nullOnDelete();
                $table->foreign('station_id')->references('id')->on('stations')->nullOnDelete();
                $table->foreign('submitted_by_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('reviewed_by_id')->references('id')->on('users')->nullOnDelete();

                $table->index(['indicator_id', 'observed_at']);
                $table->index(['area_id', 'observed_at']);
                $table->index(['station_id', 'observed_at']);
            });
        }

        if (! Schema::hasTable('hazard_scores')) {
            Schema::create('hazard_scores', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('hazard_code');
                $table->uuid('area_id');
                $table->string('level_code')->nullable();
                $table->float('score');
                $table->timestamp('calculated_at');
                $table->uuid('calculated_by_id')->nullable();
                $table->string('methodology')->nullable();
                $table->text('notes')->nullable();

                $table->foreign('hazard_code')->references('code')->on('hazard_types');
                $table->foreign('area_id')->references('id')->on('management_areas');
                $table->foreign('calculated_by_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign(['hazard_code', 'level_code'])
                    ->references(['hazard_code', 'level_code'])
                    ->on('hazard_status_levels');

                $table->index(['hazard_code', 'area_id', 'calculated_at']);
                $table->index(['area_id', 'calculated_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hazard_scores')) {
            Schema::dropIfExists('hazard_scores');
        }

        if (Schema::hasTable('hazard_indicator_readings')) {
            Schema::dropIfExists('hazard_indicator_readings');
        }

        if (Schema::hasTable('iima_eflow_requirements') && Schema::hasColumn('iima_eflow_requirements', 'key_point_id')) {
            Schema::table('iima_eflow_requirements', function (Blueprint $table) {
                $table->dropForeign(['key_point_id']);
                $table->dropColumn('key_point_id');
            });
        }

        if (Schema::hasTable('iima_eflow_key_points')) {
            Schema::dropIfExists('iima_eflow_key_points');
        }

        if (Schema::hasTable('water_quality_parameters') && Schema::hasColumn('water_quality_parameters', 'is_priority_pollutant')) {
            Schema::table('water_quality_parameters', function (Blueprint $table) {
                $table->dropColumn('is_priority_pollutant');
            });
        }
    }

    private function backfillLegacyEflowKeyPoints(): void
    {
        if (! Schema::hasTable('iima_eflow_requirements') || ! Schema::hasTable('iima_eflow_key_points')) {
            return;
        }

        $requirements = DB::table('iima_eflow_requirements')
            ->select('key_point', 'river', 'subcatchment_id', 'station_id')
            ->whereNotNull('key_point')
            ->where('key_point', '!=', '')
            ->distinct()
            ->get();

        foreach ($requirements as $requirement) {
            $code = trim((string) $requirement->key_point);

            if ($code === '') {
                continue;
            }

            $keyPointId = DB::table('iima_eflow_key_points')
                ->where('code', $code)
                ->value('id');

            if ($keyPointId === null) {
                $keyPointId = (string) Str::uuid();

                DB::table('iima_eflow_key_points')->insert([
                    'id' => $keyPointId,
                    'code' => $code,
                    'alternate_code' => null,
                    'name' => $code,
                    'river' => $requirement->river,
                    'country' => null,
                    'subcatchment_id' => $requirement->subcatchment_id,
                    'station_id' => $requirement->station_id,
                    'latitude' => null,
                    'longitude' => null,
                    'is_active' => true,
                    'note' => 'Auto-migrated from iima_eflow_requirements.key_point',
                ]);
            }

            DB::table('iima_eflow_requirements')
                ->where('key_point', $code)
                ->whereNull('key_point_id')
                ->update(['key_point_id' => $keyPointId]);
        }
    }
};
