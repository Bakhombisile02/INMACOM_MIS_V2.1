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

echo "INMACOM repair v2\n";
echo "=================\n\n";

$appRoot = realpath(__DIR__.'/../app');

if ($appRoot === false || ! is_dir($appRoot)) {
    http_response_code(500);
    echo "ERROR: Cannot locate app folder at ../app\n";
    exit;
}

echo "App root: {$appRoot}\n";

if (! file_exists($appRoot.'/vendor/autoload.php')) {
    http_response_code(500);
    echo "ERROR: Missing vendor/autoload.php\n";
    exit;
}

if (! file_exists($appRoot.'/bootstrap/app.php')) {
    http_response_code(500);
    echo "ERROR: Missing bootstrap/app.php\n";
    exit;
}

try {
    require $appRoot.'/vendor/autoload.php';
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
    'migrate --force',
    'storage:link',
    'config:cache',
    'route:cache',
    'view:cache',
];

foreach ($commands as $command) {
    echo "> {$command}\n";

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

echo "Done. Delete _repair2.php immediately.\n";
