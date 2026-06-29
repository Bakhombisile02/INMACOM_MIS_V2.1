<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;

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

echo "INMACOM repair (no DB changes)\n";
echo "=============================\n\n";

$appRoot = realpath(__DIR__.'/../app');

if ($appRoot === false || ! is_dir($appRoot)) {
    http_response_code(500);
    echo "ERROR: Cannot locate app folder at ../app\n";
    exit;
}

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
    $dbConfig = (array) config('database.connections.'.$defaultConnection, []);

    echo "Database diagnostics\n";
    echo "--------------------\n";
    echo 'default: '.$defaultConnection."\n";

    if ($defaultConnection === 'sqlite') {
        echo 'sqlite.database: '.(string) ($dbConfig['database'] ?? 'n/a')."\n";
        echo "WARNING: Laravel is using sqlite right now.\n";
    } else {
        echo 'host: '.(string) ($dbConfig['host'] ?? 'n/a')."\n";
        echo 'port: '.(string) ($dbConfig['port'] ?? 'n/a')."\n";
        echo 'database: '.(string) ($dbConfig['database'] ?? 'n/a')."\n";
        echo 'username: '.(string) ($dbConfig['username'] ?? 'n/a')."\n";
    }

    echo 'session.driver: '.(string) config('session.driver')."\n";
    echo 'cache.default: '.(string) config('cache.default')."\n";
} catch (Throwable $e) {
    echo 'DIAGNOSTIC ERROR: '.$e->getMessage()."\n";
}

echo "\nDone. Delete _repair_nodb.php immediately.\n";
