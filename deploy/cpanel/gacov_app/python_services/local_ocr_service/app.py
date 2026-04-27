from __future__ import annotations

import base64
import io
import json
import os
import re
import time
import urllib.error
import urllib.request
from typing import Any

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from PIL import Image, ImageEnhance, ImageFilter, ImageOps


app = FastAPI(title="GACOV Local OCR Service", version="1.0.0")
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        origin.strip()
        for origin in os.getenv(
            "LOCAL_OCR_ALLOWED_ORIGINS",
            "http://127.0.0.1:9119,http://localhost:9119,http://127.0.0.1:8000,http://localhost:8000",
        ).split(",")
        if origin.strip() != ""
    ],
    allow_methods=["GET", "POST"],  # GET para /health, POST para /ocr/stocking-sheet
    allow_headers=["Content-Type"],
)


class ProductCatalogItem(BaseModel):
    code: str
    name: str


class StockingSheetRequest(BaseModel):
    image_base64: str
    route_name: str | None = None
    route_code: str | None = None
    route_machine_codes: list[str] = Field(default_factory=list)
    product_catalog: list[ProductCatalogItem] = Field(default_factory=list)


class StockingSheetRow(BaseModel):
    cod: str
    producto: str
    maquinas: dict[str, int] = Field(default_factory=dict)


class StockingSheetResponse(BaseModel):
    ruta_detectada: str | None = None
    rutero_detectado: str | None = None
    filas: list[StockingSheetRow] = Field(default_factory=list)


def normalize_machine_code(code: str) -> str:
    normalized = re.sub(r"[^A-Za-z0-9]", "", code.strip().upper())

    if normalized == "":
        return ""

    digits_match = re.search(r"(\d+)", normalized)

    if digits_match is None:
        return normalized

    return digits_match.group(1).lstrip("0") or "0"


def decode_image(image_base64: str) -> Image.Image:
    try:
        binary = base64.b64decode(image_base64, validate=True)
    except Exception as exc:  # pragma: no cover - defensive for malformed payloads
        raise HTTPException(status_code=422, detail="La imagen enviada no está en base64 válido.") from exc

    try:
        image = Image.open(io.BytesIO(binary))
    except Exception as exc:  # pragma: no cover - defensive for malformed payloads
        raise HTTPException(status_code=422, detail="No fue posible abrir la imagen enviada al OCR local.") from exc

    return image.convert("RGB")


def preprocess_image(image: Image.Image) -> Image.Image:
    grayscale = ImageOps.grayscale(image)
    enhanced = ImageOps.autocontrast(grayscale)
    enhanced = ImageEnhance.Contrast(enhanced).enhance(1.35)
    enhanced = enhanced.filter(ImageFilter.SHARPEN)

    width, height = enhanced.size

    # Avoid sending oversized images to Ollama vision models; the previous
    # unconditional upscale made inference much heavier and caused timeouts.
    max_side = max(width, height)
    target_max_side = max(960, int(os.getenv("LOCAL_OCR_IMAGE_MAX_SIDE", "1280")))

    if max_side > target_max_side:
        scale = target_max_side / max_side
        enhanced = enhanced.resize(
            (max(1, int(width * scale)), max(1, int(height * scale))),
            Image.LANCZOS,
        )

    return enhanced


def image_to_base64_jpeg(image: Image.Image) -> str:
    buffer = io.BytesIO()
    image.save(
        buffer,
        format="JPEG",
        quality=max(50, min(92, int(os.getenv("LOCAL_OCR_IMAGE_QUALITY", "78")))),
        optimize=True,
    )
    return base64.b64encode(buffer.getvalue()).decode("utf-8")


def ollama_host() -> str:
    return os.getenv("OLLAMA_HOST", "http://127.0.0.1:11434").rstrip("/")


def ollama_model() -> str:
    return os.getenv("OLLAMA_MODEL", "").strip()


def ollama_timeout() -> int:
    return max(60, int(os.getenv("LOCAL_OCR_OLLAMA_TIMEOUT", "300")))


def build_stocking_prompt(request: StockingSheetRequest) -> str:
    machine_codes = ", ".join(request.route_machine_codes) if request.route_machine_codes else "sin máquinas configuradas"

    return f"""
Analiza esta foto de una planilla física de surtido para máquinas.

Contexto esperado:
- Ruta esperada: {request.route_name or "sin ruta configurada"}
- Código de ruta: {request.route_code or "sin código"}
- Códigos de máquinas válidas para esta ruta: {machine_codes}

Objetivo:
- Devuelve únicamente JSON válido.
- Detecta ruta y rutero si aparecen.
- Extrae filas con COD, nombre del producto y cantidades por máquina.
- Prioriza los códigos de máquina visibles en el encabezado de columnas.
- Si una máquina aparece como M104 o 104, usa solo el número visible como clave final: "104".
- Si una celda está vacía, devuelve 0.
- No inventes productos ni máquinas.

Formato obligatorio:
{{
  "ruta_detectada": "Ruta 1",
  "rutero_detectado": "Osvaldo",
  "filas": [
    {{
      "cod": "117",
      "producto": "CAPPUCCINO AMARETTO",
      "maquinas": {{
        "104": 25,
        "83": 0
      }}
    }}
  ]
}}
""".strip()


def extract_json_payload(text: str) -> dict[str, Any]:
    cleaned = text.strip()

    if cleaned.startswith("```"):
        cleaned = re.sub(r"^```(?:json)?\s*|\s*```$", "", cleaned, flags=re.IGNORECASE).strip()

    try:
        decoded = json.loads(cleaned)
    except json.JSONDecodeError as exc:
        raise HTTPException(status_code=502, detail="Ollama respondió, pero no devolvió JSON válido.") from exc

    if not isinstance(decoded, dict):
        raise HTTPException(status_code=502, detail="Ollama devolvió una estructura inesperada para la planilla.")

    return decoded


def call_ollama_stocking(request: StockingSheetRequest, image: Image.Image) -> dict[str, Any]:
    model = ollama_model()

    if model == "":
        raise HTTPException(
            status_code=503,
            detail="El OCR local requiere OLLAMA_MODEL configurado para analizar esta planilla.",
        )

    payload = {
        "model": model,
        "prompt": build_stocking_prompt(request),
        "stream": False,
        "format": "json",
        "images": [image_to_base64_jpeg(preprocess_image(image))],
        "options": {
            "temperature": 0,
        },
    }

    endpoint = f"{ollama_host()}/api/generate"
    http_request = urllib.request.Request(
        endpoint,
        data=json.dumps(payload).encode("utf-8"),
        headers={"Content-Type": "application/json"},
        method="POST",
    )

    try:
        with urllib.request.urlopen(http_request, timeout=ollama_timeout()) as response:
            body = response.read().decode("utf-8")
    except urllib.error.URLError as exc:
        raise HTTPException(
            status_code=503,
            detail="No fue posible conectar con Ollama local. Verifica que el servicio esté iniciado.",
        ) from exc

    try:
        decoded = json.loads(body)
    except json.JSONDecodeError as exc:
        raise HTTPException(status_code=502, detail="Ollama local respondió con un payload inválido.") from exc

    response_text = str(decoded.get("response", "")).strip()

    if response_text == "":
        raise HTTPException(status_code=502, detail="Ollama local no devolvió contenido para la planilla.")

    return extract_json_payload(response_text)


def validate_stocking_payload(payload: dict[str, Any], request: StockingSheetRequest) -> StockingSheetResponse:
    rows: list[StockingSheetRow] = []
    allowed_machine_codes = {
        normalize_machine_code(machine_code)
        for machine_code in request.route_machine_codes
        if normalize_machine_code(machine_code) != ""
    }

    for raw_row in payload.get("filas", []):
        if not isinstance(raw_row, dict):
            continue

        code = str(raw_row.get("cod", "")).strip()
        product = str(raw_row.get("producto", "")).strip()

        if code == "" or product == "":
            continue

        machine_values: dict[str, int] = {}

        for raw_machine_code, raw_quantity in dict(raw_row.get("maquinas", {})).items():
            machine_code = normalize_machine_code(str(raw_machine_code))

            if machine_code == "":
                continue

            if allowed_machine_codes and machine_code not in allowed_machine_codes:
                continue

            try:
                quantity = max(0, int(raw_quantity))
            except (TypeError, ValueError):
                quantity = 0

            machine_values[machine_code] = quantity

        rows.append(
            StockingSheetRow(
                cod=code,
                producto=product,
                maquinas=machine_values,
            )
        )

    return StockingSheetResponse(
        ruta_detectada=(str(payload.get("ruta_detectada", "")).strip() or None),
        rutero_detectado=(str(payload.get("rutero_detectado", "")).strip() or None),
        filas=rows,
    )


@app.get("/health")
def health() -> dict[str, Any]:
    # No exponer detalles de la arquitectura interna (host, model, timeout)
    model_configured = ollama_model() != ""
    return {
        "status": "ok",
        "backend": "ollama",
        "model_configured": model_configured,
    }


@app.post("/ocr/stocking-sheet", response_model=StockingSheetResponse)
def ocr_stocking_sheet(request: StockingSheetRequest) -> StockingSheetResponse:
    started_at = time.monotonic()
    image = decode_image(request.image_base64)
    payload = call_ollama_stocking(request, image)
    response = validate_stocking_payload(payload, request)

    if response.filas == []:
        raise HTTPException(
            status_code=422,
            detail="El OCR local no pudo detectar filas válidas en la planilla de surtido.",
        )

    return response
