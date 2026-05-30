<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportProductionDumpSeeder extends Seeder
{
    /**
     * Run the database seeds by parsing the production MySQL dump.
     */
    public function run(): void
    {
        $sqlPath = base_path('Data for Database /How database looks currently/u550237388_INMACOMV2 (2).sql');
        if (!file_exists($sqlPath)) {
            $this->command->error("Production SQL dump not found at: {$sqlPath}");
            return;
        }

        $this->command->info("Temporarily disabling database foreign key constraints...");
        DB::statement("SET session_replication_role = 'replica';");

        $tablesToTruncate = [
            'audit_logs', 'cache', 'cache_locks', 'comments', 'comment_mentions',
            'compliance_thresholds', 'documents', 'document_storages',
            'iima_allocations', 'iima_eflow_key_points', 'iima_eflow_requirements',
            'iima_user_categories', 'management_areas', 'management_area_stations',
            'measurements', 'password_reset_tokens', 'registration_pins',
            'sessions', 'station_capabilities', 'station_revisions', 'stations', 'users',
            'water_quality_parameters'
        ];

        $totalInserted = [];

        foreach ($tablesToTruncate as $tbl) {
            if (Schema::hasTable($tbl)) {
                $this->command->info("Truncating table: {$tbl}");
                DB::table($tbl)->truncate();
                $totalInserted[$tbl] = 0;
            }
        }

        $this->command->info("Opening SQL dump file...");
        $handle = fopen($sqlPath, "r");
        if (!$handle) {
            $this->command->error("Failed to open file {$sqlPath}");
            return;
        }

        $currentTable = null;
        $columns = [];
        $batch = [];
        $batchSize = 200;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '--') || str_starts_with($line, '/*')) {
                continue;
            }

            // Detect INSERT INTO statement
            if (preg_match('/^INSERT INTO `([a-zA-Z0-9_]+)` \((.+)\) VALUES/i', $line, $matches)) {
                // If there was a pending batch from previous table, insert it
                if ($currentTable && $currentTable !== 'migrations' && !empty($batch)) {
                    $this->insertBatch($currentTable, $batch);
                    if (!isset($totalInserted[$currentTable])) {
                        $totalInserted[$currentTable] = 0;
                    }
                    $totalInserted[$currentTable] += count($batch);
                    $batch = [];
                }

                $currentTable = $matches[1];
                $colStr = $matches[2];
                $columns = array_map(function($c) {
                    return trim($c, " `\t\n\r\0\x0B");
                }, explode(',', $colStr));
                continue;
            }

            // Check if we are parsing values for a table
            if ($currentTable && str_starts_with($line, '(')) {
                $isLast = str_ends_with($line, ';');
                
                // If it is the migrations table, skip insertion logic completely
                if ($currentTable === 'migrations') {
                    if ($isLast) {
                        $currentTable = null;
                    }
                    continue;
                }

                $lineContent = rtrim($line, ',;');
                
                if (str_starts_with($lineContent, '(') && str_ends_with($lineContent, ')')) {
                    $valContent = substr($lineContent, 1, -1);
                    $rowValues = str_getcsv($valContent, ',', "'", "\\");
                    $rowValues = array_map('trim', $rowValues);
                    
                    if (count($rowValues) === count($columns)) {
                        $row = array_combine($columns, $rowValues);
                        $batch[] = $this->sanitizeRow($currentTable, $row);
                    } else {
                        $this->command->warn("Column count mismatch in table {$currentTable}: expected " . count($columns) . ", got " . count($rowValues));
                    }

                    if (count($batch) >= $batchSize) {
                        $this->insertBatch($currentTable, $batch);
                        if (!isset($totalInserted[$currentTable])) {
                            $totalInserted[$currentTable] = 0;
                        }
                        $totalInserted[$currentTable] += count($batch);
                        $batch = [];
                    }
                }

                if ($isLast) {
                    if (!empty($batch)) {
                        $this->insertBatch($currentTable, $batch);
                        if (!isset($totalInserted[$currentTable])) {
                            $totalInserted[$currentTable] = 0;
                        }
                        $totalInserted[$currentTable] += count($batch);
                        $batch = [];
                    }
                    $currentTable = null;
                }
            }
        }

        // Flush any remaining batch
        if ($currentTable && $currentTable !== 'migrations' && !empty($batch)) {
            $this->insertBatch($currentTable, $batch);
            if (!isset($totalInserted[$currentTable])) {
                $totalInserted[$currentTable] = 0;
            }
            $totalInserted[$currentTable] += count($batch);
        }

        fclose($handle);

        DB::statement("SET session_replication_role = 'origin';");
        $this->command->info("Foreign key checks restored.");

        $this->command->info("\n--- Import Summary ---");
        foreach ($totalInserted as $tbl => $count) {
            $this->command->info("Table '{$tbl}': imported {$count} records.");
        }
    }

    /**
     * Sanitize rows to match PostgreSQL column formats.
     */
    private function sanitizeRow(string $table, array $row): array
    {
        $sanitized = [];
        foreach ($row as $col => $val) {
            if ($val === 'NULL' || $val === 'null') {
                $sanitized[$col] = null;
                continue;
            }

            // Handle double-escaped JSON strings in MySQL dump for PostgreSQL json columns
            if ($val !== null && (str_starts_with($val, '{') || str_starts_with($val, '['))) {
                $val = str_replace(['\\\"', '\\"', '\"'], '"', $val);
            }

            // Handle boolean values in MySQL dump (which are 1 and 0) for PostgreSQL boolean columns
            if (str_starts_with($col, 'is_') || str_ends_with($col, '_observed') || str_ends_with($col, '_reported')) {
                if ($val === '1' || $val === 1 || $val === 'true') {
                    $sanitized[$col] = true;
                } elseif ($val === '0' || $val === 0 || $val === 'false') {
                    $sanitized[$col] = false;
                } else {
                    $sanitized[$col] = null;
                }
                continue;
            }

            $sanitized[$col] = $val;
        }
        return $sanitized;
    }

    /**
     * Insert a batch of records.
     */
    private function insertBatch(string $table, array $batch): void
    {
        if (!Schema::hasTable($table)) {
            $this->command->warn("Skipping unknown table: {$table}");
            return;
        }

        try {
            DB::table($table)->insert($batch);
        } catch (\Exception $e) {
            $this->command->error("Failed to insert batch into table {$table}: " . $e->getMessage());
            // Retry row by row to identify the exact failing row and log it for debug
            foreach ($batch as $index => $row) {
                try {
                    DB::table($table)->insert([$row]);
                } catch (\Exception $ex) {
                    $this->command->error("Failing row details in {$table} at index {$index}: " . json_encode($row));
                    $this->command->error("Error detail: " . $ex->getMessage());
                }
            }
        }
    }
}
