<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

$token = 'change-this-now';
if ((string) ($_GET['token'] ?? '') !== $token) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

echo "INMACOM final fix (no DB writes)\n";
echo "===============================\n\n";

$appRoot = realpath(__DIR__.'/../app');
if ($appRoot === false || ! is_dir($appRoot)) {
    http_response_code(500);
    echo "ERROR: Cannot locate app folder at ../app\n";
    exit;
}

echo "App root: {$appRoot}\n";

$envPath = $appRoot.'/.env';
$envProdPath = $appRoot.'/.env.production';

if (! file_exists($envPath)) {
    if (file_exists($envProdPath)) {
        if (! copy($envProdPath, $envPath)) {
            http_response_code(500);
            echo "ERROR: Failed to create .env from .env.production\n";
            exit;
        }
        echo "Created .env from .env.production\n";
    } else {
        http_response_code(500);
        echo "ERROR: Missing both .env and .env.production\n";
        exit;
    }
}

$envBackup = $appRoot.'/.env.backup.'.date('Ymd-His');
if (copy($envPath, $envBackup)) {
    echo "Backup saved: {$envBackup}\n";
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    http_response_code(500);
    echo "ERROR: Failed to read .env\n";
    exit;
}

$setEnv = static function (array &$envLines, string $key, string $value): void {
    $pattern = '/^\s*'.preg_quote($key, '/').'\s*=/';
    $newLine = $key.'='.$value;

    foreach ($envLines as $idx => $line) {
        if (preg_match($pattern, (string) $line) === 1) {
            $envLines[$idx] = $newLine;

            return;
        }
    }

    $envLines[] = $newLine;
};

// Keep production DB data untouched; only force runtime connection/driver choices.
$setEnv($lines, 'APP_ENV', 'production');
$setEnv($lines, 'APP_DEBUG', 'false');
$setEnv($lines, 'APP_URL', 'https://inmacom.net');
$setEnv($lines, 'DB_CONNECTION', 'mysql');
$setEnv($lines, 'DB_HOST', '127.0.0.1');
$setEnv($lines, 'DB_PORT', '3306');
$setEnv($lines, 'SESSION_DRIVER', 'file');
$setEnv($lines, 'CACHE_STORE', 'file');
$setEnv($lines, 'QUEUE_CONNECTION', 'sync');
$setEnv($lines, 'FIREBASE_CREDENTIALS', 'storage/app/firebase-service-account.json');

$newEnv = implode("\n", $lines)."\n";
if (file_put_contents($envPath, $newEnv) === false) {
    http_response_code(500);
    echo "ERROR: Failed to write .env\n";
    exit;
}

echo "Updated .env runtime settings\n\n";

require $appRoot.'/vendor/autoload.php';

try {
    $app = require $appRoot.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'BOOT ERROR: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
    exit;
}

$commands = [
    'optimize:clear',
    'config:cache',
    'route:cache',
    'view:cache',
];

foreach ($commands as $command) {
    echo '> '.$command."\n";

    try {
        $exitCode = $kernel->call($command);
        $output = trim((string) $kernel->output());
        echo "Exit code: {$exitCode}\n";
        if ($output !== '') {
            echo $output."\n";
        }
        echo "\n";
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n";
        echo 'File: '.$e->getFile().':'.$e->getLine()."\n\n";
    }
}

try {
    $defaultConnection = (string) config('database.default');
    $mysqlCfg = (array) config('database.connections.mysql', []);

    echo "Diagnostics\n";
    echo "-----------\n";
    echo 'database.default: '.$defaultConnection."\n";
    echo 'mysql.host: '.(string) ($mysqlCfg['host'] ?? 'n/a')."\n";
    echo 'mysql.port: '.(string) ($mysqlCfg['port'] ?? 'n/a')."\n";
    echo 'mysql.database: '.(string) ($mysqlCfg['database'] ?? 'n/a')."\n";
    echo 'mysql.username: '.(string) ($mysqlCfg['username'] ?? 'n/a')."\n";
    echo 'session.driver: '.(string) config('session.driver')."\n";
    echo 'cache.default: '.(string) config('cache.default')."\n";

    try {
        DB::connection()->getPdo();
        echo "db.pdo: connected\n";
    } catch (Throwable $e) {
        echo 'db.pdo: ERROR '.$e->getMessage()."\n";
    }
} catch (Throwable $e) {
    echo 'DIAGNOSTIC ERROR: '.$e->getMessage()."\n";
}

echo "\nDone. Delete _final_fix.php immediately.\n";
