#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_ROOT="${PROJECT_ROOT}/deploy/cpanel"
APP_STAGING="${DEPLOY_ROOT}/gacov_app"
PUBLIC_STAGING="${DEPLOY_ROOT}/public_html"
STAGING_PUBLIC_DIR="${DEPLOY_ROOT}/staging-inv"
APP_ZIP="${DEPLOY_ROOT}/gacov_app.zip"
PUBLIC_ZIP="${DEPLOY_ROOT}/public_html.zip"
STAGING_PUBLIC_ZIP="${DEPLOY_ROOT}/staging-inv.zip"

rm -rf "${DEPLOY_ROOT}"
mkdir -p "${APP_STAGING}" "${PUBLIC_STAGING}" "${STAGING_PUBLIC_DIR}"

rsync -a \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.amr' \
  --exclude='.claude' \
  --exclude='.DS_Store' \
  --exclude='.env' \
  --exclude='.phpunit.result.cache' \
  --exclude='node_modules' \
  --exclude='tests' \
  --exclude='docs' \
  --exclude='auditorias' \
  --exclude='tmp' \
  --exclude='vendor.zip' \
  --exclude='auditoria_backup_*.tar.gz' \
  --exclude='database/database.sqlite' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='deploy' \
  "${PROJECT_ROOT}/" "${APP_STAGING}/"

rsync -a "${PROJECT_ROOT}/public/" "${PUBLIC_STAGING}/"
rsync -a "${PROJECT_ROOT}/public/" "${STAGING_PUBLIC_DIR}/"
cp "${PROJECT_ROOT}/docs/DEPLOY_CPANEL_SIN_TERMINAL.md" "${DEPLOY_ROOT}/INSTRUCCIONES.txt"

cat > "${PUBLIC_STAGING}/index.php" <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$baseCandidates = [
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
PHP

cat > "${PUBLIC_STAGING}/install.php" <<'PHP'
<?php

declare(strict_types=1);

$baseCandidates = [
    dirname(__DIR__).'/gacov_app',
    dirname(__DIR__).'/gacov_app/gacov_app',
];

$basePath = null;

foreach ($baseCandidates as $candidate) {
    if (is_file($candidate.'/public/install.php')) {
        $basePath = $candidate;
        break;
    }
}

if ($basePath === null) {
    http_response_code(500);
    exit('GACOV deploy error: installer backend path not found.');
}

define('CPANEL_PUBLIC_BASE_PATH', $basePath);

$_SERVER['SCRIPT_FILENAME'] = CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

chdir(CPANEL_PUBLIC_BASE_PATH.'/public');
require CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
PHP

cat > "${STAGING_PUBLIC_DIR}/index.php" <<'PHP'
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
PHP

cat > "${STAGING_PUBLIC_DIR}/install.php" <<'PHP'
<?php

declare(strict_types=1);

$baseCandidates = [
    dirname(__DIR__, 2).'/gacov_app',
    dirname(__DIR__, 2).'/gacov_app/gacov_app',
    dirname(__DIR__).'/gacov_app',
    dirname(__DIR__).'/gacov_app/gacov_app',
];

$basePath = null;

foreach ($baseCandidates as $candidate) {
    if (is_file($candidate.'/public/install.php')) {
        $basePath = $candidate;
        break;
    }
}

if ($basePath === null) {
    http_response_code(500);
    exit('GACOV deploy error: installer backend path not found.');
}

define('CPANEL_PUBLIC_BASE_PATH', $basePath);

$_SERVER['SCRIPT_FILENAME'] = CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

chdir(CPANEL_PUBLIC_BASE_PATH.'/public');
require CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
PHP

cat > "${STAGING_PUBLIC_DIR}/.htaccess" <<'APACHE'
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-XSS-Protection "1; mode=block"
    Header unset X-Powered-By
</IfModule>

RewriteEngine On
RewriteBase /staging-inv/

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

RewriteRule ^ index.php [L,QSA]

<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(env|sql|log|lock)$">
    Order allow,deny
    Deny from all
</FilesMatch>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType text/html "access plus 0 seconds"
</IfModule>

ServerSignature Off
Options -Indexes
APACHE

cd "${DEPLOY_ROOT}"
zip -rq "${APP_ZIP}" "gacov_app"
zip -rq "${PUBLIC_ZIP}" "public_html"
zip -rq "${STAGING_PUBLIC_ZIP}" "staging-inv"

printf 'Paquetes generados en:\n%s\n%s\n%s\n' "${APP_ZIP}" "${PUBLIC_ZIP}" "${STAGING_PUBLIC_ZIP}"
