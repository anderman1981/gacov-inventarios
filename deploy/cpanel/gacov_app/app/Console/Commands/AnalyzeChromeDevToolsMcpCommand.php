<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Documentation\ChromeDevToolsMcpRepositoryAnalyzer;
use Illuminate\Console\Command;
use Throwable;

final class AnalyzeChromeDevToolsMcpCommand extends Command
{
    protected $signature = 'ai:analyze-chrome-devtools-mcp
        {url? : URL pública del repo en GitHub}';

    protected $description = 'Descarga y resume la base oficial de Chrome DevTools MCP para alimentar la base de conocimiento local.';

    public function handle(ChromeDevToolsMcpRepositoryAnalyzer $analyzer): int
    {
        $repositoryUrl = $this->argument('url');

        $this->components->info('Analizando la documentación oficial de Chrome DevTools MCP...');

        try {
            $analysis = $analyzer->analyze(is_string($repositoryUrl) ? $repositoryUrl : null);
            $stored = $analyzer->store($analysis);
        } catch (Throwable $exception) {
            $this->components->error('No fue posible completar el análisis: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Dato', 'Valor'],
            [
                ['Repo', $analysis->repositoryUrl],
                ['Branch', $analysis->branch],
                ['Versión npm', $analysis->packageVersion],
                ['Licencia', $analysis->license],
                ['Node requerido', $analysis->nodeRequirement],
                ['Tools detectadas', (string) $analysis->totalTools()],
                ['Knowledge local', $stored['knowledge']],
            ],
        );

        $this->components->info('Análisis completado y guardado en la base de conocimiento local.');

        return self::SUCCESS;
    }
}
