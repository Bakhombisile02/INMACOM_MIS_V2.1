<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Configuration constants for the labels
const CONFIRMED_LABEL = '[Confirmed July 2019]';
const UNCONFIRMED_LABEL = '[Inferred Metadata — Unconfirmed & Unverified]';

// 1. Fetch all stations with the unconfirmed/unverified tag in their summary
$stations = DB::table('stations')
    ->where('summary', 'like', '%' . UNCONFIRMED_LABEL . '%')
    ->get();

if ($stations->isEmpty()) {
    echo "No unconfirmed stations found in the database.\n";
    exit(0);
}

echo "Found " . $stations->count() . " unconfirmed stations.\n";

// 2. Build the backup structure and backup file content
$markdown = "# Unconfirmed Stations Backup\n\n";
$markdown .= "This file contains the backup of stations that had their summaries temporarily updated from \"Unconfirmed & Unverified\" to \"Confirmed\".\n\n";
$markdown .= "| ID | Code | Name | Original Summary |\n";
$markdown .= "| --- | --- | --- | --- |\n";

$backupData = [];
$esc = fn ($v) => str_replace(['|', "\n", "\r"], ['\\|', ' ', ' '], (string) $v);

foreach ($stations as $station) {
    $markdown .= "| " . $esc($station->id) . " | " . $esc($station->code) . " | " . $esc($station->name) . " | " . $esc($station->summary) . " |\n";
    $backupData[] = [
        'id' => $station->id,
        'code' => $station->code,
        'name' => $station->name,
        'summary' => $station->summary
    ];
}

// Write the markdown backup file to the workspace
$workspaceBackupPath = __DIR__ . '/../unconfirmed_stations_backup.md';
if (file_put_contents($workspaceBackupPath, $markdown) === false) {
    $err = error_get_last();
    echo "ERROR: Failed to write markdown backup to: " . $workspaceBackupPath . " (" . ($err['message'] ?? 'unknown error') . ")\n";
    exit(1);
}
echo "Written markdown backup to: " . $workspaceBackupPath . "\n";

// Write a JSON backup for programmatic rollback
$jsonBackupPath = __DIR__ . '/unconfirmed_stations_backup.json';
$json = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false || json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR: Failed to encode JSON backup. Error: " . json_last_error_msg() . "\n";
    exit(1);
}

if (file_put_contents($jsonBackupPath, $json) === false) {
    $err = error_get_last();
    echo "ERROR: Failed to write JSON backup to: " . $jsonBackupPath . " (" . ($err['message'] ?? 'unknown error') . ")\n";
    exit(1);
}
echo "Written JSON backup to: " . $jsonBackupPath . "\n";

// 3. Update the summaries in the database within a transaction
$updatedCount = 0;
DB::transaction(function () use ($stations, &$updatedCount) {
    foreach ($stations as $station) {
        $newSummary = str_replace(
            UNCONFIRMED_LABEL,
            CONFIRMED_LABEL,
            $station->summary
        );
        
        if ($newSummary === $station->summary) {
            continue;
        }
        
        DB::table('stations')
            ->where('id', $station->id)
            ->update(['summary' => $newSummary]);
        
        $updatedCount++;
    }
});

echo "Successfully updated " . $updatedCount . " stations in the database to confirmed.\n";
