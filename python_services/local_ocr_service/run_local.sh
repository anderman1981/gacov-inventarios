#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$ROOT_DIR/../.." && pwd)"
VENV_PYTHON="$ROOT_DIR/.venv/bin/python"

if [[ -f "$PROJECT_ROOT/.env" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "$PROJECT_ROOT/.env"
  set +a
fi

HOST="${LOCAL_OCR_HOST:-127.0.0.1}"
PORT="${LOCAL_OCR_PORT:-8011}"

cd "$ROOT_DIR"

if [[ -x "$VENV_PYTHON" ]]; then
  exec "$VENV_PYTHON" -m uvicorn app:app --host "$HOST" --port "$PORT"
fi

exec python3 -m uvicorn app:app --host "$HOST" --port "$PORT"
