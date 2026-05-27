<?php

/**
 * Hostinger entry point for INMACOM MIS.
 *
 * Layout assumption:
 *   /home/u550237388/domains/<domain>/public_html/   <-- this file
 *   /home/u550237388/domains/<domain>/app/           <-- Laravel project root
 *
 * If your layout differs, adjust APP_ROOT below.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Path to the Laravel project root (one level up, sibling folder named "app").
$APP_ROOT = realpath(__DIR__.'/../app');

if ($APP_ROOT === false || ! is_dir($APP_ROOT)) {
    http_response_code(500);
    exit('Deployment misconfigured: Laravel app folder not found at '.__DIR__.'/../app');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $APP_ROOT.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $APP_ROOT.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $APP_ROOT.'/bootstrap/app.php';

// Repoint storage/public paths at the sibling project.
$app->usePublicPath(__DIR__);
$app->useStoragePath($APP_ROOT.'/storage');

$app->handleRequest(Request::capture());
