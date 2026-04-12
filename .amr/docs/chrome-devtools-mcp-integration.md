# Integración Chrome DevTools MCP

## Qué quedó incorporado

- Cliente `App\Support\Browser\ChromeDevToolsMcpClient` para hablar con `chrome-devtools-mcp` por `stdio` usando JSON-RPC real.
- Comando `php artisan ai:analyze-chrome-devtools-mcp` para descargar `README.md`, `docs/tool-reference.md` y resumirlos como conocimiento local.
- Configuración en `config/services.php` y variables `CHROME_DEVTOOLS_MCP_*` en `.env.example`.
- Persistencia del snapshot de conocimiento en `storage/app/private/ai/chrome-devtools-mcp/`.

## Decisiones importantes

- El cliente NO inicia Chrome en el constructor. Se hace lazy-start en la primera llamada para evitar procesos huérfanos en cada request.
- La negociación MCP se hace con `initialize`, luego `notifications/initialized`, y las ejecuciones usan `tools/call`.
- Se dejó `--headless --isolated --no-usage-statistics` por defecto para que el flujo sea seguro en entornos de servidor.

## Uso rápido

```bash
php artisan ai:analyze-chrome-devtools-mcp
```

Ejemplo en código:

```php
<?php

declare(strict_types=1);

use App\Support\Browser\ChromeDevToolsMcpClient;

$client = app(ChromeDevToolsMcpClient::class);
$client->callTool('new_page', ['url' => config('app.url')]);
$snapshot = $client->callTool('take_snapshot');
$client->close();
```

## Flujo sugerido de auto-corrección

1. Abrir pantalla crítica con `new_page` o `navigate_page`.
2. Capturar `take_snapshot` antes de decidir clics o inputs.
3. Si falla algo, revisar `list_console_messages` y `list_network_requests`.
4. Aplicar fix en Laravel o Livewire.
5. Repetir el flujo hasta obtener snapshot limpio y comportamiento esperado.

## Pendiente consciente

Todavía no existe un vector store en este proyecto. Por ahora la ingesta queda lista en archivos locales para que luego se conecte a Qdrant, Pinecone u otro backend RAG sin rehacer la extracción.
