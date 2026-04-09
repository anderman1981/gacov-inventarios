# OCR local para surtido

## Objetivo

Agregar un proveedor local para lectura de planillas de surtido sin depender de cuota en Gemini u OpenAI.

## Implementación

- Servicio Python con FastAPI en `python_services/local_ocr_service/app.py`
- Backend principal: `Ollama` local mediante endpoint `http://127.0.0.1:11434/api/generate`
- Endpoint expuesto para Laravel:
  - `POST /ocr/stocking-sheet`
  - `GET /health`

## Variables Laravel

```env
LOCAL_OCR_ENABLED=true
LOCAL_OCR_ENDPOINT=http://127.0.0.1:8011
LOCAL_OCR_TIMEOUT=120
LOCAL_OCR_CONNECT_TIMEOUT=5
```

## Variables del servicio Python

```env
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2-vision:11b
```

## Inicio local

```bash
cd python_services/local_ocr_service
./run_local.sh
```

## Notas

- El servicio local se integra primero en `Surtido Máquinas`.
- Si `Ollama` no está disponible, Laravel continúa con Gemini y luego OpenAI.
- La precisión depende del modelo multimodal cargado en Ollama.
