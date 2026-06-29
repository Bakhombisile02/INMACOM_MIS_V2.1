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
        Schema::create('stations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->float('latitude');
            $table->float('longitude');
            $table->string('category');
            $table->string('water_source');
            $table->string('water_body_type');
            $table->boolean('is_active');
            $table->boolean('is_real_time');
            $table->text('summary')->nullable();
            $table->string('telemetry_system')->nullable();
            $table->string('gauge_code')->nullable();
            $table->string('owner_org')->nullable();
            $table->string('country')->nullable();
            $table->string('river_basin')->nullable();
        });

        Schema::create('water_quality_parameters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('default_unit')->nullable();
            $table->integer('display_order');
            $table->boolean('is_active');
        });

        Schema::create('management_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('basin');
            $table->boolean('is_active');
            $table->string('country')->nullable();
            $table->text('description')->nullable();
        });

        Schema::create('hazard_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
        });

        Schema::create('hazard_status_levels', function (Blueprint $table) {
            $table->string('hazard_code');
            $table->string('level_code');
            $table->string('name');
            $table->integer('severity');
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->text('actions_required')->nullable();
            $table->primary(['hazard_code', 'level_code']);

            $table->foreign('hazard_code')->references('code')->on('hazard_types');
        });

        Schema::create('hazard_status_current', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('hazard_code');
            $table->uuid('area_id');
            $table->string('level_code');
            $table->timestamp('calculated_at');
            $table->float('score')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->uuid('calculated_by_id')->nullable();
            $table->text('calculation_notes')->nullable();

            $table->foreign('hazard_code')->references('code')->on('hazard_types');
            $table->foreign('area_id')->references('id')->on('management_areas');
            $table->foreign('calculated_by_id')->references('id')->on('users');
            $table->foreign(['hazard_code', 'level_code'])
                ->references(['hazard_code', 'level_code'])
                ->on('hazard_status_levels');
        });

        Schema::create('disaster_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->string('hazard_code');
            $table->string('title');
            $table->string('incident_status');
            $table->timestamp('reported_at');
            $table->uuid('submitted_by_id');
            $table->timestamp('submitted_at');
            $table->string('review_status');
            $table->text('description')->nullable();
            $table->string('severity_level')->nullable();
            $table->uuid('area_id')->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->float('affected_radius_km')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('reported_by_id')->nullable();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_contact')->nullable();
            $table->uuid('incident_commander_id')->nullable();
            $table->uuid('reviewed_by_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->boolean('is_self_override')->nullable();

            $table->foreign('hazard_code')->references('code')->on('hazard_types');
            $table->foreign('submitted_by_id')->references('id')->on('users');
            $table->foreign('area_id')->references('id')->on('management_areas');
            $table->foreign('reported_by_id')->references('id')->on('users');
            $table->foreign('incident_commander_id')->references('id')->on('users');
            $table->foreign('reviewed_by_id')->references('id')->on('users');
        });

        Schema::create('iima_user_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->float('assurance_pct_primary')->nullable();
            $table->float('allocation_share_primary')->nullable();
            $table->float('assurance_pct_secondary')->nullable();
            $table->float('allocation_share_secondary')->nullable();
            $table->integer('priority_order');
        });

        Schema::create('iima_restriction_levels', function (Blueprint $table) {
            $table->integer('level');
            $table->string('user_category');
            $table->float('max_curtailment_pct')->nullable();
            $table->text('description')->nullable();
            $table->primary(['level', 'user_category']);

            $table->foreign('user_category')->references('code')->on('iima_user_categories');
        });

        Schema::create('iima_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subcatchment_id');
            $table->string('country');
            $table->string('user_category');
            $table->float('allocation_mm3_a');
            $table->integer('effective_from');
            $table->text('note')->nullable();

            $table->foreign('subcatchment_id')->references('id')->on('management_areas');
            $table->foreign('user_category')->references('code')->on('iima_user_categories');
        });

        Schema::create('management_area_stations', function (Blueprint $table) {
            $table->uuid('management_area_id');
            $table->uuid('station_id');
            $table->primary(['management_area_id', 'station_id']);

            $table->foreign('management_area_id')->references('id')->on('management_areas');
            $table->foreign('station_id')->references('id')->on('stations');
        });

        Schema::create('station_capabilities', function (Blueprint $table) {
            $table->uuid('station_id');
            $table->string('measurement_type');
            $table->boolean('is_primary');
            $table->date('installed_at')->nullable();
            $table->text('notes')->nullable();
            $table->primary(['station_id', 'measurement_type']);

            $table->foreign('station_id')->references('id')->on('stations');
        });

        Schema::create('station_operational_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('station_id');
            $table->string('status');
            $table->text('reason')->nullable();
            $table->uuid('reported_by_id');
            $table->timestamp('started_at');
            $table->timestamp('expected_resolution_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->foreign('station_id')->references('id')->on('stations');
            $table->foreign('reported_by_id')->references('id')->on('users');
        });

        Schema::create('measurements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('station_id');
            $table->string('measurement_type');
            $table->uuid('parameter_id')->nullable();
            $table->float('fsc')->nullable();
            $table->float('value');
            $table->string('unit');
            $table->timestamp('date');
            $table->string('status');
            $table->uuid('submitted_by_id');
            $table->timestamp('submitted_at');
            $table->uuid('reviewed_by_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->boolean('is_self_override')->nullable();

            $table->foreign('station_id')->references('id')->on('stations');
            $table->foreign('parameter_id')->references('id')->on('water_quality_parameters');
            $table->foreign('submitted_by_id')->references('id')->on('users');
            $table->foreign('reviewed_by_id')->references('id')->on('users');
        });

        Schema::create('compliance_thresholds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('station_id');
            $table->string('data_type');
            $table->uuid('parameter_id');
            $table->float('min_value')->nullable();
            $table->float('max_value')->nullable();
            $table->string('unit')->nullable();
            $table->text('notes')->nullable();

            $table->foreign('station_id')->references('id')->on('stations');
            $table->foreign('parameter_id')->references('id')->on('water_quality_parameters');
        });

        Schema::create('hazard_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('hazard_code');
            $table->uuid('area_id');
            $table->string('level_code');
            $table->string('previous_level')->nullable();
            $table->float('score')->nullable();
            $table->timestamp('calculated_at');
            $table->uuid('calculated_by_id')->nullable();
            $table->text('calculation_notes')->nullable();

            $table->foreign('hazard_code')->references('code')->on('hazard_types');
            $table->foreign('area_id')->references('id')->on('management_areas');
            $table->foreign('calculated_by_id')->references('id')->on('users');
            $table->foreign(['hazard_code', 'level_code'])
                ->references(['hazard_code', 'level_code'])
                ->on('hazard_status_levels');
        });

        Schema::create('hazard_indicators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('hazard_code');
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('data_source')->nullable();
            $table->string('unit')->nullable();
            $table->boolean('is_active');
            $table->unique(['hazard_code', 'code']);

            $table->foreign('hazard_code')->references('code')->on('hazard_types');
        });

        Schema::create('hazard_indicator_thresholds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('indicator_id');
            $table->uuid('area_id')->nullable();
            $table->float('range_min')->nullable();
            $table->float('range_max')->nullable();
            $table->string('range_label')->nullable();
            $table->integer('rating');

            $table->foreign('indicator_id')->references('id')->on('hazard_indicators');
            $table->foreign('area_id')->references('id')->on('management_areas');
        });

        Schema::create('hazard_indicator_weights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('indicator_id');
            $table->uuid('area_id')->nullable();
            $table->float('weight');
            $table->integer('confidence_pct')->nullable();

            $table->foreign('indicator_id')->references('id')->on('hazard_indicators');
            $table->foreign('area_id')->references('id')->on('management_areas');
        });

        Schema::create('hazard_status_lookups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('hazard_code');
            $table->uuid('area_id')->nullable();
            $table->float('score_min')->nullable();
            $table->float('score_max')->nullable();
            $table->string('level_code');

            $table->foreign('hazard_code')->references('code')->on('hazard_types');
            $table->foreign('area_id')->references('id')->on('management_areas');
            $table->foreign(['hazard_code', 'level_code'])
                ->references(['hazard_code', 'level_code'])
                ->on('hazard_status_levels');
        });

        Schema::create('pollution_incident_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('incident_id')->unique();
            $table->integer('usepa_category')->nullable();
            $table->string('pollutant_name')->nullable();
            $table->string('cas_number')->nullable();
            $table->float('estimated_mass_kg')->nullable();
            $table->float('spill_volume_m3')->nullable();
            $table->string('river_reach')->nullable();
            $table->float('estimated_flow_m3_s')->nullable();
            $table->boolean('fish_kill_observed');
            $table->string('fish_kill_extent')->nullable();
            $table->boolean('waterborne_disease_reported');
            $table->string('disease_name')->nullable();
            $table->integer('disease_case_count')->nullable();
            $table->string('alert_color')->nullable();
            $table->string('pollution_source')->nullable();

            $table->foreign('incident_id')->references('id')->on('disaster_incidents');
        });

        Schema::create('incident_stations', function (Blueprint $table) {
            $table->uuid('incident_id');
            $table->uuid('station_id');
            $table->string('role');
            $table->text('notes')->nullable();
            $table->primary(['incident_id', 'station_id']);

            $table->foreign('incident_id')->references('id')->on('disaster_incidents');
            $table->foreign('station_id')->references('id')->on('stations');
        });

        Schema::create('incident_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('incident_id');
            $table->string('phase');
            $table->string('action_type');
            $table->text('description');
            $table->uuid('performed_by_id')->nullable();
            $table->string('organization')->nullable();
            $table->timestamp('performed_at');
            $table->text('outcome')->nullable();

            $table->foreign('incident_id')->references('id')->on('disaster_incidents');
            $table->foreign('performed_by_id')->references('id')->on('users');
        });

        Schema::create('incident_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('incident_id');
            $table->string('recipient_country')->nullable();
            $table->string('recipient_org')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_role')->nullable();
            $table->string('channel');
            $table->text('message')->nullable();
            $table->uuid('sent_by_id');
            $table->timestamp('sent_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by')->nullable();

            $table->foreign('incident_id')->references('id')->on('disaster_incidents');
            $table->foreign('sent_by_id')->references('id')->on('users');
        });

        Schema::create('iima_eflow_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subcatchment_id');
            $table->string('river');
            $table->string('key_point');
            $table->uuid('station_id')->nullable();
            $table->float('mean_annual_mm3');
            $table->float('min_flow_m3_s');
            $table->string('source_article')->nullable();
            $table->text('note')->nullable();

            $table->foreign('subcatchment_id')->references('id')->on('management_areas');
            $table->foreign('station_id')->references('id')->on('stations');
        });

        Schema::create('water_abstractions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subcatchment_id');
            $table->string('country');
            $table->string('user_category');
            $table->integer('period_year');
            $table->integer('period_month')->nullable();
            $table->float('volume_mm3');
            $table->string('data_source')->nullable();
            $table->boolean('is_estimate');
            $table->string('status');
            $table->uuid('submitted_by_id');
            $table->timestamp('submitted_at');
            $table->uuid('reviewed_by_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->boolean('is_self_override')->nullable();

            $table->foreign('subcatchment_id')->references('id')->on('management_areas');
            $table->foreign('user_category')->references('code')->on('iima_user_categories');
            $table->foreign('submitted_by_id')->references('id')->on('users');
            $table->foreign('reviewed_by_id')->references('id')->on('users');
        });

        Schema::create('iima_compliance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('check_type');
            $table->uuid('subcatchment_id')->nullable();
            $table->uuid('eflow_requirement_id')->nullable();
            $table->string('country')->nullable();
            $table->string('user_category')->nullable();
            $table->integer('period_year');
            $table->integer('period_month')->nullable();
            $table->float('allocated_value')->nullable();
            $table->float('actual_value')->nullable();
            $table->string('unit');
            $table->float('compliance_pct')->nullable();
            $table->boolean('is_compliant')->nullable();
            $table->integer('restriction_level_triggered')->nullable();
            $table->timestamp('assessed_at');
            $table->uuid('assessed_by_id')->nullable();
            $table->text('notes')->nullable();

            $table->foreign('subcatchment_id')->references('id')->on('management_areas');
            $table->foreign('eflow_requirement_id')->references('id')->on('iima_eflow_requirements');
            $table->foreign('user_category')->references('code')->on('iima_user_categories');
            $table->foreign('assessed_by_id')->references('id')->on('users');
        });

        Schema::create('folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('parent_id')->nullable();
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('folders');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_name');
            $table->uuid('folder_id')->nullable();
            $table->string('file_type');
            $table->integer('file_size')->nullable();
            $table->uuid('uploaded_by_id')->nullable();
            $table->timestamp('uploaded_at');
            $table->string('category')->nullable();

            $table->foreign('folder_id')->references('id')->on('folders');
            $table->foreign('uploaded_by_id')->references('id')->on('users');
        });

        Schema::create('external_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('link_name');
            $table->string('link_url');
            $table->text('description')->nullable();
            $table->integer('display_order');
            $table->boolean('is_active');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->string('action');
            $table->string('table_name');
            $table->string('record_id');
            $table->string('before_status')->nullable();
            $table->string('after_status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('occurred_at');

            $table->foreign('actor_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('external_links');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('folders');
        Schema::dropIfExists('iima_compliance_records');
        Schema::dropIfExists('water_abstractions');
        Schema::dropIfExists('iima_eflow_requirements');
        Schema::dropIfExists('incident_notifications');
        Schema::dropIfExists('incident_actions');
        Schema::dropIfExists('incident_stations');
        Schema::dropIfExists('pollution_incident_details');
        Schema::dropIfExists('hazard_status_lookups');
        Schema::dropIfExists('hazard_indicator_weights');
        Schema::dropIfExists('hazard_indicator_thresholds');
        Schema::dropIfExists('hazard_indicators');
        Schema::dropIfExists('hazard_status_history');
        Schema::dropIfExists('compliance_thresholds');
        Schema::dropIfExists('measurements');
        Schema::dropIfExists('station_operational_statuses');
        Schema::dropIfExists('station_capabilities');
        Schema::dropIfExists('management_area_stations');
        Schema::dropIfExists('iima_allocations');
        Schema::dropIfExists('iima_restriction_levels');
        Schema::dropIfExists('iima_user_categories');
        Schema::dropIfExists('disaster_incidents');
        Schema::dropIfExists('hazard_status_current');
        Schema::dropIfExists('hazard_status_levels');
        Schema::dropIfExists('hazard_types');
        Schema::dropIfExists('management_areas');
        Schema::dropIfExists('water_quality_parameters');
        Schema::dropIfExists('stations');
    }
};
