<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$jsonBackupPath = __DIR__ . '/unconfirmed_stations_backup.json';

if (!file_exists($jsonBackupPath)) {
    echo "Backup file not found at " . $jsonBackupPath . "\n";
    exit(1);
}

$rawJson = file_get_contents($jsonBackupPath);
$backupData = json_decode($rawJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR: Failed to parse JSON backup from " . $jsonBackupPath . ". Error: " . json_last_error_msg() . "\n";
    exit(1);
}

if (!is_array($backupData) || empty($backupData)) {
    echo "Backup data is empty or invalid.\n";
    exit(1);
}

echo "Found " . count($backupData) . " stations in backup to restore.\n";

$restoredCount = 0;
foreach ($backupData as $row) {
    DB::table('stations')
        ->where('id', $row['id'])
        ->update(['summary' => $row['summary']]);
    
    $restoredCount++;
}

echo "Successfully restored " . $restoredCount . " stations back to their unconfirmed state.\n";
