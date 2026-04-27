<?php

declare(strict_types=1);

namespace App\Support\Browser;

use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use stdClass;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Throwable;

final class ChromeDevToolsMcpClient
{
    private const string JSON_RPC_VERSION = '2.0';

    private ?InputStream $input = null;

    private ?Process $process = null;

    private int $nextRequestId = 1;

    private string $stdoutBuffer = '';

    private string $stderrBuffer = '';

    private bool $initialized = false;

    public function start(): void
    {
        $this->ensureInitialized();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTools(): array
    {
        $result = $this->sendRequest('tools/list');
        $tools = $result['tools'] ?? [];

        return is_array($tools) ? $tools : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function callTool(string $toolName, array $arguments = []): array
    {
        $result = $this->sendRequest('tools/call', [
            'name' => $toolName,
            'arguments' => $arguments === [] ? new stdClass : $arguments,
        ]);

        return is_array($result) ? $result : ['result' => $result];
    }

    /**
     * @return array<string, mixed>
     */
    public function navigateAndScreenshot(string $url): array
    {
        $this->callTool('navigate_page', ['url' => $url]);

        return $this->callTool('take_screenshot');
    }

    public function close(): void
    {
        $this->stop();
    }

    public function stop(): void
    {
        if ($this->input !== null) {
            $this->input->close();
        }

        if ($this->process !== null) {
            $this->process->stop(3);
        }

        $this->input = null;
        $this->process = null;
        $this->initialized = false;
        $this->stdoutBuffer = '';
        $this->stderrBuffer = '';
    }

    public function __destruct()
    {
        try {
            $this->stop();
        } catch (Throwable $exception) {
            Log::warning('No fue posible detener Chrome DevTools MCP limpiamente.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function sendRequest(string $method, array $params = []): array
    {
        $this->ensureInitialized();

        $result = $this->sendRequestInternal(
            method: $method,
            params: $params,
            timeoutSeconds: (int) config('services.chrome_devtools_mcp.request_timeout', 30),
        );

        return is_array($result) ? $result : ['result' => $result];
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized && $this->process?->isRunning()) {
            return;
        }

        $this->stop();
        $this->bootProcess();

        $result = $this->sendRequestInternal(
            method: 'initialize',
            params: [
                'protocolVersion' => (string) config('services.chrome_devtools_mcp.protocol_version', '2025-06-18'),
                'capabilities' => new stdClass,
                'clientInfo' => [
                    'name' => (string) config('services.chrome_devtools_mcp.client_name', 'gacov-inventarios'),
                    'version' => (string) config('services.chrome_devtools_mcp.client_version', '1.0.0'),
                ],
            ],
            timeoutSeconds: (int) config('services.chrome_devtools_mcp.startup_timeout', 45),
        );

        $this->sendNotification('notifications/initialized');
        $this->initialized = true;

        Log::info('Chrome DevTools MCP inicializado.', [
            'protocol_version' => $result['protocolVersion'] ?? null,
            'server_info' => $result['serverInfo'] ?? null,
        ]);
    }

    private function bootProcess(): void
    {
        $this->input = new InputStream;
        $this->process = new Process($this->command());
        $this->process->setInput($this->input);
        $this->process->setTimeout(null);
        $this->process->start();
    }

    /**
     * @return list<string>
     */
    private function command(): array
    {
        $command = [(string) config('services.chrome_devtools_mcp.command', 'npx')];
        $package = (string) config('services.chrome_devtools_mcp.package', 'chrome-devtools-mcp@latest');
        $arguments = config('services.chrome_devtools_mcp.arguments', []);

        if (! is_array($arguments)) {
            $arguments = [];
        }

        return [
            ...$command,
            '-y',
            $package,
            ...array_values(array_map(
                static fn (mixed $argument): string => (string) $argument,
                $arguments,
            )),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function sendRequestInternal(string $method, array $params, int $timeoutSeconds): mixed
    {
        $requestId = $this->nextRequestId++;

        $this->writeMessage([
            'jsonrpc' => self::JSON_RPC_VERSION,
            'id' => $requestId,
            'method' => $method,
            'params' => $params === [] ? new stdClass : $params,
        ]);

        $deadline = microtime(true) + max(1, $timeoutSeconds);

        while (true) {
            $message = $this->readMessageUntil($deadline);

            if (($message['id'] ?? null) !== $requestId) {
                $this->handleAsyncMessage($message);

                continue;
            }

            if (isset($message['error']) && is_array($message['error'])) {
                throw new RuntimeException($this->formatError($method, $message['error']));
            }

            return $message['result'] ?? [];
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function writeMessage(array $message): void
    {
        if ($this->input === null) {
            throw new RuntimeException('Chrome DevTools MCP no tiene stdin disponible.');
        }

        try {
            $payload = json_encode($message, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('No fue posible serializar el mensaje JSON-RPC.', 0, $exception);
        }

        $this->input->write($payload.PHP_EOL);
    }

    private function sendNotification(string $method): void
    {
        $this->writeMessage([
            'jsonrpc' => self::JSON_RPC_VERSION,
            'method' => $method,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function readMessageUntil(float $deadline): array
    {
        while (microtime(true) < $deadline) {
            $this->drainProcessBuffers();

            $message = $this->popBufferedMessage();

            if ($message !== null) {
                return $message;
            }

            if ($this->process === null || ! $this->process->isRunning()) {
                throw new RuntimeException(
                    'Chrome DevTools MCP terminó inesperadamente. '.trim($this->stderrBuffer)
                );
            }

            usleep(50_000);
        }

        throw new RuntimeException(
            'Chrome DevTools MCP agotó el tiempo de espera. '.trim($this->stderrBuffer)
        );
    }

    private function drainProcessBuffers(): void
    {
        if ($this->process === null) {
            return;
        }

        $this->stdoutBuffer .= $this->process->getIncrementalOutput();
        $this->stderrBuffer .= $this->process->getIncrementalErrorOutput();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function popBufferedMessage(): ?array
    {
        while (($newlinePosition = strpos($this->stdoutBuffer, "\n")) !== false) {
            $rawLine = trim(substr($this->stdoutBuffer, 0, $newlinePosition));
            $this->stdoutBuffer = (string) substr($this->stdoutBuffer, $newlinePosition + 1);

            if ($rawLine === '') {
                continue;
            }

            try {
                $decoded = json_decode($rawLine, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                Log::warning('Chrome DevTools MCP devolvió una línea no JSON.', [
                    'line' => $rawLine,
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleAsyncMessage(array $message): void
    {
        if (! isset($message['method'])) {
            return;
        }

        Log::debug('Mensaje async recibido desde Chrome DevTools MCP.', [
            'method' => $message['method'],
            'params' => $message['params'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $error
     */
    private function formatError(string $method, array $error): string
    {
        $code = $error['code'] ?? 'unknown';
        $message = $error['message'] ?? 'Error desconocido del servidor MCP.';
        $data = $error['data'] ?? null;

        return sprintf(
            'Chrome DevTools MCP rechazó %s (code=%s): %s%s',
            $method,
            (string) $code,
            (string) $message,
            $data === null ? '' : ' | data='.json_encode($data),
        );
    }
}
