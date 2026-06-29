<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisasterController;
use App\Http\Controllers\DocumentUploadController;
use App\Http\Controllers\GisController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PublicDocumentsController;
use App\Http\Controllers\PublicGisController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StationRevisionsController;
use App\Http\Controllers\StationsController;
use App\Http\Controllers\ThresholdsController;
use App\Http\Controllers\UserPasswordResetController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Landing/Index');
})->name('landing');

Route::get('/documents', [PublicDocumentsController::class, 'index'])->name('documents');
Route::get('/explore', [PublicGisController::class, 'explore'])->name('explore');
Route::get('/public/stations/{id}/historical-data', [PublicGisController::class, 'stationHistorical'])
    ->name('public.stations.historical-data');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/stations', [StationsController::class, 'index'])->name('stations.index');
    Route::post('/stations', [StationsController::class, 'store'])->middleware('role:admin,manager')->name('stations.store');
    Route::post('/stations/import', [StationsController::class, 'import'])->middleware('role:admin,manager')->name('stations.import');
    Route::get('/stations/{station}', [StationsController::class, 'show'])->name('stations.show');
    Route::get('/stations/{station}/export-csv', [StationsController::class, 'exportCsv'])->name('stations.export-csv');
    Route::patch('/stations/{station}', [StationsController::class, 'update'])->name('stations.update');
    Route::delete('/stations/{station}', [StationsController::class, 'destroy'])->middleware('role:admin')->name('stations.destroy');

    // Thresholds & Allocations Configuration
    Route::get('/thresholds', [ThresholdsController::class, 'index'])->name('thresholds.index');
    Route::post('/thresholds/compliance/group', [ThresholdsController::class, 'updateGroupCompliance'])->middleware('role:admin,manager')->name('thresholds.compliance.group');
    Route::patch('/thresholds/compliance/{id}', [ThresholdsController::class, 'updateCompliance'])->middleware('role:admin,manager')->name('thresholds.compliance.update');
    Route::post('/thresholds/compliance/{id}/reset', [ThresholdsController::class, 'resetCompliance'])->middleware('role:admin,manager')->name('thresholds.compliance.reset');
    Route::patch('/thresholds/eflow/{id}', [ThresholdsController::class, 'updateEflow'])->middleware('role:admin,manager')->name('thresholds.eflow.update');
    Route::post('/thresholds/allocations', [ThresholdsController::class, 'storeAllocation'])->middleware('role:admin,manager')->name('thresholds.allocations.store');
    Route::patch('/thresholds/allocations/{id}', [ThresholdsController::class, 'updateAllocation'])->middleware('role:admin,manager')->name('thresholds.allocations.update');
    Route::delete('/thresholds/allocations/{id}', [ThresholdsController::class, 'destroyAllocation'])->middleware('role:admin,manager')->name('thresholds.allocations.destroy');
    Route::patch('/thresholds/hazard', [ThresholdsController::class, 'updateHazard'])->middleware('role:admin,manager')->name('thresholds.hazard.update');

    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}', [UsersController::class, 'update'])->middleware('role:admin')->name('users.update');
    Route::delete('/users/{user}', [UsersController::class, 'destroy'])->middleware('role:admin')->name('users.destroy');
    Route::post('/users/{user}/password-reset', [UserPasswordResetController::class, 'store'])->middleware('role:admin')->name('users.password-reset');

    Route::get('/flow-levels', [GisController::class, 'flowLevels'])->name('flow-levels.index');
    Route::get('/flow-levels/export-csv', [GisController::class, 'exportFlowCsv'])->name('flow-levels.export-csv');

    Route::get('/dam-levels', [GisController::class, 'damLevels'])->name('dam-levels.index');
    Route::get('/dam-levels/export-csv', [GisController::class, 'exportDamCsv'])->name('dam-levels.export-csv');

    Route::get('/water-quality', [GisController::class, 'waterQuality'])->name('water-quality.index');
    Route::get('/water-quality/export-csv', [GisController::class, 'exportWaterQualityCsv'])->name('water-quality.export-csv');

    Route::get('/rainfall', [GisController::class, 'rainfall'])->name('rainfall.index');
    Route::get('/rainfall/export-csv', [GisController::class, 'exportRainfallCsv'])->name('rainfall.export-csv');

    Route::get('/groundwater', [GisController::class, 'groundwater'])->name('groundwater.index');
    Route::get('/groundwater/export-csv', [GisController::class, 'exportGroundwaterCsv'])->name('groundwater.export-csv');

    Route::get('/disaster-management', [DisasterController::class, 'index'])->name('disaster.index');

    // Live Measurements CRUD & 2-Step Verification
    Route::post('/measurements', [GisController::class, 'storeMeasurement'])->name('measurements.store');
    Route::patch('/measurements/{id}', [GisController::class, 'updateMeasurement'])->name('measurements.update');
    Route::delete('/measurements/{id}', [GisController::class, 'destroyMeasurement'])->name('measurements.destroy');
    Route::post('/measurements/{id}/approve', [GisController::class, 'approveMeasurement'])->middleware('role:admin,manager')->name('measurements.approve');
    Route::post('/measurements/{id}/reject', [GisController::class, 'rejectMeasurement'])->middleware('role:admin,manager')->name('measurements.reject');
    Route::get('/stations/{id}/historical-data', [GisController::class, 'getHistoricalData'])->name('stations.historical-data');

    // Bulk measurement imports
    Route::post('/flow-levels/import', [GisController::class, 'importFlow'])->middleware('role:admin,manager')->name('measurements.flow.import');
    Route::post('/dam-levels/import', [GisController::class, 'importDamLevel'])->middleware('role:admin,manager')->name('measurements.dam.import');
    Route::post('/water-quality/import', [GisController::class, 'importWaterQuality'])->middleware('role:admin,manager')->name('measurements.wq.import');
    Route::post('/rainfall/import', [GisController::class, 'importRainfall'])->middleware('role:admin,manager')->name('measurements.rainfall.import');
    Route::post('/groundwater/import', [GisController::class, 'importGroundwater'])->middleware('role:admin,manager')->name('measurements.groundwater.import');

    Route::get('/profile', fn () => Inertia::render('Profile/Index'))->name('profile');

    // Audit Log
    Route::get('/audit', [AuditController::class, 'index'])->middleware('role:admin,manager')->name('audit.index');
    Route::get('/audit/export', [AuditController::class, 'export'])->middleware('role:admin')->name('audit.export');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::patch('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');

    Route::get('/library', [LibraryController::class, 'index'])->name('library');
    Route::post('/library/documents', [DocumentUploadController::class, 'store'])
        ->name('library.documents.store');
    Route::get('/library/documents/{document}/download', [DocumentUploadController::class, 'download'])
        ->name('library.documents.download');

    // Comments (polymorphic on measurement / station / station_revision / disaster_incident)
    Route::get('/comments', [CommentsController::class, 'index'])->name('comments.index');
    Route::post('/comments', [CommentsController::class, 'store'])->name('comments.store');
    Route::patch('/comments/{comment}/resolve', [CommentsController::class, 'resolve'])->name('comments.resolve');
    Route::patch('/comments/{comment}/unresolve', [CommentsController::class, 'unresolve'])->name('comments.unresolve');
    Route::delete('/comments/{comment}', [CommentsController::class, 'destroy'])->name('comments.destroy');
    Route::get('/comments/mentions/me', [CommentsController::class, 'mentions'])->name('comments.mentions');
    Route::post('/comments/mentions/read', [CommentsController::class, 'markMentionsRead'])->name('comments.mentions.read');
    Route::get('/users/search', [CommentsController::class, 'searchUsers'])->name('users.search');

    // Station revisions (clerks submit, managers/admins approve/reject)
    Route::post('/station-revisions/{stationRevision}/approve', [StationRevisionsController::class, 'approve'])
        ->middleware('role:admin,manager')->name('station-revisions.approve');
    Route::post('/station-revisions/{stationRevision}/reject', [StationRevisionsController::class, 'reject'])
        ->middleware('role:admin,manager')->name('station-revisions.reject');
});

Route::get('/run-seed-staging-982347102', function () {
    set_time_limit(0);
    $output = '';
    try {
        Artisan::call('db:seed', ['--force' => true]);
        $output .= Artisan::output();
    } catch (Exception $e) {
        $output .= 'Error: '.$e->getMessage();
    }

    return response($output ?: 'Seed complete!', 200)->header('Content-Type', 'text/plain');
});

require __DIR__.'/auth.php';
