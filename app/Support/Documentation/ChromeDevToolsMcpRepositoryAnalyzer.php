<?php

declare(strict_types=1);

namespace App\Support\Documentation;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;

final class ChromeDevToolsMcpRepositoryAnalyzer
{
    public function analyze(?string $repositoryUrl = null): ChromeDevToolsMcpRepositoryAnalysis
    {
        $configuredRepositoryUrl = (string) config('services.chrome_devtools_mcp.repository.url');
        $repositoryUrl = trim((string) ($repositoryUrl ?? '')) !== '' ? trim((string) $repositoryUrl) : $configuredRepositoryUrl;
        $branch = (string) config('services.chrome_devtools_mcp.repository.branch', 'main');
        [$owner, $repository] = $this->parseGithubRepository($repositoryUrl);
        $rawBaseUrl = sprintf('https://raw.githubusercontent.com/%s/%s/%s', $owner, $repository, $branch);

        $client = $this->httpClient();
        $readmeMarkdown = $this->fetchDocument($client, $rawBaseUrl.'/README.md');
        $toolReferenceMarkdown = $this->fetchDocument($client, $rawBaseUrl.'/docs/tool-reference.md');
        $packagePayload = $this->fetchDocument($client->acceptJson(), $rawBaseUrl.'/package.json');

        try {
            /** @var array{name?: string, version?: string, license?: string, engines?: array{node?: string}} $package */
            $package = json_decode($packagePayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('El package.json remoto no se pudo decodificar.', 0, $exception);
        }

        $toolsByCategory = $this->extractToolCounts($toolReferenceMarkdown);
        $recommendedWorkflows = $this->recommendedWorkflows();
        $fetchedAt = now()->toIso8601ZuluString();

        return new ChromeDevToolsMcpRepositoryAnalysis(
            repositoryUrl: $repositoryUrl,
            branch: $branch,
            packageVersion: (string) ($package['version'] ?? 'unknown'),
            license: (string) ($package['license'] ?? 'unknown'),
            nodeRequirement: (string) ($package['engines']['node'] ?? 'unknown'),
            toolsByCategory: $toolsByCategory,
            recommendedWorkflows: $recommendedWorkflows,
            readmeMarkdown: $readmeMarkdown,
            toolReferenceMarkdown: $toolReferenceMarkdown,
            knowledgeMarkdown: $this->buildKnowledgeMarkdown(
                repositoryUrl: $repositoryUrl,
                branch: $branch,
                packageVersion: (string) ($package['version'] ?? 'unknown'),
                license: (string) ($package['license'] ?? 'unknown'),
                nodeRequirement: (string) ($package['engines']['node'] ?? 'unknown'),
                toolsByCategory: $toolsByCategory,
                recommendedWorkflows: $recommendedWorkflows,
                fetchedAt: $fetchedAt,
            ),
            fetchedAt: $fetchedAt,
        );
    }

    /**
     * @return array<string, string>
     */
    public function store(ChromeDevToolsMcpRepositoryAnalysis $analysis): array
    {
        $disk = Storage::disk('local');
        $basePath = trim((string) config('services.chrome_devtools_mcp.knowledge_path', 'ai/chrome-devtools-mcp'), '/');
        $summaryPath = $basePath.'/summary.json';
        $readmePath = $basePath.'/README.md';
        $toolReferencePath = $basePath.'/tool-reference.md';
        $knowledgePath = $basePath.'/knowledge.md';

        $disk->put($summaryPath, json_encode($analysis->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $disk->put($readmePath, $analysis->readmeMarkdown);
        $disk->put($toolReferencePath, $analysis->toolReferenceMarkdown);
        $disk->put($knowledgePath, $analysis->knowledgeMarkdown);

        return [
            'directory' => $disk->path($basePath),
            'summary' => $disk->path($summaryPath),
            'readme' => $disk->path($readmePath),
            'tool_reference' => $disk->path($toolReferencePath),
            'knowledge' => $disk->path($knowledgePath),
        ];
    }

    private function httpClient(): PendingRequest
    {
        return Http::accept('text/plain')
            ->connectTimeout((int) config('services.chrome_devtools_mcp.repository.connect_timeout', 10))
            ->retry(2, 200)
            ->timeout((int) config('services.chrome_devtools_mcp.repository.timeout', 20));
    }

    private function fetchDocument(PendingRequest $client, string $url): string
    {
        $response = $client->get($url)->throw();

        return $response->body();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseGithubRepository(string $repositoryUrl): array
    {
        $normalizedUrl = rtrim($repositoryUrl, '/');

        if (preg_match('#^https://github\.com/([^/]+)/([^/]+?)(?:\.git)?$#', $normalizedUrl, $matches) !== 1) {
            throw new RuntimeException('Solo se soportan URLs públicas de GitHub con formato https://github.com/owner/repo.');
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * @return array<string, int>
     */
    private function extractToolCounts(string $toolReferenceMarkdown): array
    {
        preg_match_all('/\*\*\[([^\]]+)\]\([^)]+\)\*\*\s+\((\d+)\s+tools?\)/i', $toolReferenceMarkdown, $matches, PREG_SET_ORDER);

        $categories = [];

        foreach ($matches as $match) {
            $categories[(string) $match[1]] = (int) $match[2];
        }

        if ($categories === []) {
            throw new RuntimeException('No fue posible extraer el inventario de tools desde docs/tool-reference.md.');
        }

        return $categories;
    }

    /**
     * @return array<int, array{title: string, objective: string, tools: array<int, string>}>
     */
    private function recommendedWorkflows(): array
    {
        return [
            [
                'title' => 'Smoke test para Blade y Livewire',
                'objective' => 'Abrir la vista, capturar el snapshot accesible, interactuar con botones o formularios y validar que aparezcan tablas, toasts o textos esperados.',
                'tools' => ['new_page', 'take_snapshot', 'click', 'fill_form', 'wait_for', 'take_screenshot'],
            ],
            [
                'title' => 'Diagnóstico UI + API en tiempo real',
                'objective' => 'Correlacionar errores visibles con consola, red y estado del DOM para entender si el fallo viene de JavaScript, Livewire, validación o backend.',
                'tools' => ['take_snapshot', 'list_console_messages', 'get_console_message', 'list_network_requests', 'get_network_request', 'evaluate_script'],
            ],
            [
                'title' => 'Auditoría de performance operativa',
                'objective' => 'Medir la salud de pantallas críticas como dashboard, transferencias o inventario antes de desplegar cambios sensibles.',
                'tools' => ['navigate_page', 'lighthouse_audit', 'performance_start_trace', 'performance_stop_trace', 'performance_analyze_insight'],
            ],
        ];
    }

    /**
     * @param  array<string, int>  $toolsByCategory
     * @param  array<int, array{title: string, objective: string, tools: array<int, string>}>  $recommendedWorkflows
     */
    private function buildKnowledgeMarkdown(
        string $repositoryUrl,
        string $branch,
        string $packageVersion,
        string $license,
        string $nodeRequirement,
        array $toolsByCategory,
        array $recommendedWorkflows,
        string $fetchedAt,
    ): string {
        $lines = [
            '# Chrome DevTools MCP · Knowledge Snapshot',
            '',
            '- Repo: '.$repositoryUrl,
            '- Branch: '.$branch,
            '- Version: '.$packageVersion,
            '- License: '.$license,
            '- Node requerido: '.$nodeRequirement,
            '- Total de tools detectadas: '.array_sum($toolsByCategory),
            '- Capturado: '.$fetchedAt,
            '',
            '## Categorías',
        ];

        foreach ($toolsByCategory as $category => $count) {
            $lines[] = sprintf('- %s: %d tools', $category, $count);
        }

        $lines[] = '';
        $lines[] = '## Comando sugerido';
        $lines[] = '';
        $lines[] = '```bash';
        $lines[] = 'npx -y chrome-devtools-mcp@latest --headless --isolated --no-usage-statistics';
        $lines[] = '```';
        $lines[] = '';
        $lines[] = '## Guías para el agente';
        $lines[] = '- Usa `take_snapshot` antes de `take_screenshot` cuando necesites razonar sobre el DOM.';
        $lines[] = '- Si falla una acción, inspecciona consola y red antes de proponer cambios de backend.';
        $lines[] = '- Para performance, `lighthouse_audit` cubre accesibilidad, SEO y best practices; el detalle fino sale de `performance_start_trace` y `performance_analyze_insight`.';
        $lines[] = '';
        $lines[] = '## Workflows recomendados para GACOV';

        foreach ($recommendedWorkflows as $workflow) {
            $lines[] = sprintf('- %s: %s', $workflow['title'], $workflow['objective']);
            $lines[] = '  Tools: '.implode(', ', $workflow['tools']);
        }

        $lines[] = '';
        $lines[] = '## Patrón de self-healing sugerido';
        $lines[] = '1. Abrir la ruta objetivo.';
        $lines[] = '2. Tomar snapshot y ejecutar la interacción.';
        $lines[] = '3. Revisar consola, requests y respuesta visual.';
        $lines[] = '4. Generar fix mínimo en Laravel/Livewire.';
        $lines[] = '5. Repetir el flujo hasta confirmar que el error desapareció.';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
