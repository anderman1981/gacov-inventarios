<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Tests\TestCase;

final class AnalyzeChromeDevToolsMcpCommandTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function test_command_downloads_and_stores_chrome_devtools_mcp_knowledge(): void
    {
        Storage::fake('local');

        Http::fake([
            'https://raw.githubusercontent.com/ChromeDevTools/chrome-devtools-mcp/main/README.md' => Http::response(
                "# Chrome DevTools MCP\n\nChrome DevTools MCP connects coding agents with Chrome.\n",
                200,
            ),
            'https://raw.githubusercontent.com/ChromeDevTools/chrome-devtools-mcp/main/docs/tool-reference.md' => Http::response(
                '# Chrome DevTools MCP Tool Reference'
                .' - **[Input automation](#input-automation)** (9 tools)'
                .' - **[Navigation automation](#navigation-automation)** (6 tools)'
                .' - **[Emulation](#emulation)** (2 tools)'
                .' - **[Performance](#performance)** (4 tools)'
                .' - **[Network](#network)** (2 tools)'
                .' - **[Debugging](#debugging)** (6 tools)',
                200,
            ),
            'https://raw.githubusercontent.com/ChromeDevTools/chrome-devtools-mcp/main/package.json' => Http::response(
                json_encode([
                    'name' => 'chrome-devtools-mcp',
                    'version' => '0.21.0',
                    'license' => 'Apache-2.0',
                    'engines' => [
                        'node' => '^20.19.0 || ^22.12.0 || >=23',
                    ],
                ], JSON_THROW_ON_ERROR),
                200,
                ['Content-Type' => 'application/json'],
            ),
        ]);

        $this->artisan('ai:analyze-chrome-devtools-mcp')
            ->assertSuccessful();

        Storage::disk('local')->assertExists('ai/chrome-devtools-mcp/README.md');
        Storage::disk('local')->assertExists('ai/chrome-devtools-mcp/tool-reference.md');
        Storage::disk('local')->assertExists('ai/chrome-devtools-mcp/knowledge.md');
        Storage::disk('local')->assertExists('ai/chrome-devtools-mcp/summary.json');

        /** @var array<string, mixed> $summary */
        $summary = json_decode(
            Storage::disk('local')->get('ai/chrome-devtools-mcp/summary.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('0.21.0', $summary['package_version']);
        $this->assertSame('Apache-2.0', $summary['license']);
        $this->assertSame(29, $summary['total_tools']);
        $this->assertSame(9, $summary['tools_by_category']['Input automation']);
        $this->assertSame(6, $summary['tools_by_category']['Debugging']);
    }
}
