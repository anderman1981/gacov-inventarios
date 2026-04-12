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
LOCAL_OCR_STRICT=true
LOCAL_OCR_ENDPOINT=http://127.0.0.1:8011
LOCAL_OCR_TIMEOUT=180
LOCAL_OCR_CONNECT_TIMEOUT=5
```

## Variables del servicio Python

```env
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2-vision:11b
LOCAL_OCR_ALLOWED_ORIGINS=http://127.0.0.1:9119,http://localhost:9119,http://127.0.0.1:8000,http://localhost:8000
LOCAL_OCR_OLLAMA_TIMEOUT=300
LOCAL_OCR_IMAGE_MAX_SIDE=1280
LOCAL_OCR_IMAGE_QUALITY=78
```

## Inicio local

```bash
cd python_services/local_ocr_service
./run_local.sh
```

## Notas

- El servicio local se integra primero en `Surtido Máquinas`.
- Con `LOCAL_OCR_STRICT=true`, Laravel usa solo OCR local y no intenta Gemini ni OpenAI.
- La carga inicial ahora puede procesarse directo desde el navegador hacia `127.0.0.1:8011`, evitando el timeout de cURL en Laravel cuando Ollama tarda más de lo normal.
- El servicio reduce y comprime la imagen antes de enviarla a Ollama para bajar el tiempo de inferencia.
- La precisión depende del modelo multimodal cargado en Ollama.
