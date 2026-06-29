<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;

// One-time emergency repair endpoint for shared hosting without SSH.
// 1) Set a strong token below.
// 2) Run once from browser: /_repair.php?token=YOUR_TOKEN
// 3) Delete this file immediately after success.

$token = 'change-this-now';

if (($_GET['token'] ?? '') !== $token) {
    http_response_code(403);
    exit('Forbidden');
}

$appRoot = realpath(__DIR__.'/../app');

if ($appRoot === false || ! is_dir($appRoot)) {
    http_response_code(500);
    exit('Cannot locate app folder at ../app');
}

require $appRoot.'/vendor/autoload.php';

$app = require $appRoot.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$commands = [
    'optimize:clear',
    'migrate --force',
    'storage:link',
    'config:cache',
    'route:cache',
    'view:cache',
];

header('Content-Type: text/plain; charset=utf-8');

echo "INMACOM repair run\n";
echo "=================\n\n";

foreach ($commands as $command) {
    echo '> '.$command."\n";

    try {
        $kernel->call($command);
        $output = trim((string) $kernel->output());
        echo $output !== '' ? $output."\n\n" : "OK\n\n";
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n\n";
    }
}

echo "Done. Delete public_html/_repair.php now.\n";
