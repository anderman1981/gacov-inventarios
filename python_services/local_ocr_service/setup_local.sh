#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$ROOT_DIR/.venv"

if ! command -v python3 >/dev/null 2>&1; then
  echo "python3 no está instalado en este equipo."
  exit 1
fi

if ! command -v tesseract >/dev/null 2>&1; then
  echo "tesseract no está instalado. Instálalo antes de continuar."
  exit 1
fi

if ! command -v ollama >/dev/null 2>&1; then
  echo "ollama no está instalado. Instálalo antes de continuar."
  exit 1
fi

if [[ ! -d "$VENV_DIR" ]]; then
  python3 -m venv "$VENV_DIR"
fi

source "$VENV_DIR/bin/activate"
python -m pip install --upgrade pip
python -m pip install -r "$ROOT_DIR/requirements.txt"

echo "OCR local listo."
echo "Siguiente paso:"
echo "1. Verifica que Ollama tenga el modelo configurado en OLLAMA_MODEL."
echo "2. Ejecuta: $ROOT_DIR/run_local.sh"
