<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$baseCandidates = [
    dirname(__DIR__, 2).'/gacov_app',
    dirname(__DIR__, 2).'/gacov_app/gacov_app',
    dirname(__DIR__).'/gacov_app',
    dirname(__DIR__).'/gacov_app/gacov_app',
];

$basePath = null;

foreach ($baseCandidates as $candidate) {
    if (is_file($candidate.'/bootstrap/app.php') && is_file($candidate.'/vendor/autoload.php')) {
        $basePath = $candidate;
        break;
    }
}

if ($basePath === null) {
    http_response_code(500);
    exit('GACOV deploy error: backend path not found.');
}

if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
