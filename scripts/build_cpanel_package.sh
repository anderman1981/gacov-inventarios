#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_ROOT="${PROJECT_ROOT}/deploy/cpanel"
APP_STAGING="${DEPLOY_ROOT}/gacov_app"
PUBLIC_STAGING="${DEPLOY_ROOT}/public_html"
APP_ZIP="${DEPLOY_ROOT}/gacov_app.zip"
PUBLIC_ZIP="${DEPLOY_ROOT}/public_html.zip"

rm -rf "${DEPLOY_ROOT}"
mkdir -p "${APP_STAGING}" "${PUBLIC_STAGING}"

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
cp "${PROJECT_ROOT}/docs/DEPLOY_CPANEL_SIN_TERMINAL.md" "${DEPLOY_ROOT}/INSTRUCCIONES.txt"

cat > "${PUBLIC_STAGING}/index.php" <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = dirname(__DIR__).'/gacov_app';

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

define('CPANEL_PUBLIC_BASE_PATH', dirname(__DIR__).'/gacov_app');

$_SERVER['SCRIPT_FILENAME'] = CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

chdir(CPANEL_PUBLIC_BASE_PATH.'/public');
require CPANEL_PUBLIC_BASE_PATH.'/public/install.php';
PHP

cd "${DEPLOY_ROOT}"
zip -rq "${APP_ZIP}" "gacov_app"
zip -rq "${PUBLIC_ZIP}" "public_html"

printf 'Paquete generado en:\n%s\n%s\n' "${APP_ZIP}" "${PUBLIC_ZIP}"
