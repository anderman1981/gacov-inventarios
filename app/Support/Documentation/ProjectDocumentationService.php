<?php

declare(strict_types=1);

namespace App\Support\Documentation;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

final class ProjectDocumentationService
{
    private readonly string $docsPath;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $documents = null;

    public function __construct()
    {
        $this->docsPath = base_path('docs');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function documents(): Collection
    {
        if ($this->documents !== null) {
            return $this->documents;
        }

        if (! File::isDirectory($this->docsPath)) {
            return $this->documents = collect();
        }

        $this->documents = collect(File::files($this->docsPath))
            ->filter(static fn (SplFileInfo $file): bool => $file->getExtension() === 'md')
            ->map(fn (SplFileInfo $file): array => $this->mapDocument($file))
            ->sortByDesc(static fn (array $document): int => $document['modified_at']->timestamp)
            ->sortBy(static fn (array $document): int => $document['slug'] === 'status' ? 0 : 1)
            ->values();

        return $this->documents;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(?string $slug): ?array
    {
        if ($slug === null || $slug === '') {
            return $this->documents()->first();
        }

        return $this->documents()->firstWhere('slug', $slug);
    }

    /**
     * @return array<string, mixed>
     */
    public function statusSummary(): array
    {
        $statusDocument = $this->find('status');

        if ($statusDocument === null) {
            return [
                'progress' => 0,
                'completed_items' => 0,
                'in_progress_items' => 0,
                'pending_items' => 0,
                'document_count' => $this->documents()->count(),
                'last_updated_at' => $this->documents()->max('modified_at'),
            ];
        }

        $raw = (string) $statusDocument['raw'];
        $overall = $this->checklistCounts($raw);
        $completedSection = $this->checklistCounts($this->sectionContent($raw, 'Completado'));
        $inProgressSection = $this->checklistCounts($this->sectionContent($raw, 'En curso'));
        $pendingSection = $this->checklistCounts($this->sectionContent($raw, 'Pendientes críticos'));

        return [
            'progress' => $overall['total'] > 0 ? (int) round(($overall['completed'] / $overall['total']) * 100) : 0,
            'completed_items' => $completedSection['total'] > 0 ? $completedSection['completed'] : $overall['completed'],
            'in_progress_items' => $inProgressSection['pending'],
            'pending_items' => $pendingSection['pending'],
            'document_count' => $this->documents()->count(),
            'last_updated_at' => $this->documents()->max('modified_at') ?? $statusDocument['modified_at'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDocument(SplFileInfo $file): array
    {
        $raw = File::get($file->getPathname());
        $title = $this->extractTitle($raw, $file->getBasename('.md'));
        $headings = $this->extractHeadings($raw);
        $checklists = $this->checklistCounts($raw);

        return [
            'slug' => $file->getBasename('.md'),
            'filename' => $file->getFilename(),
            'title' => $title,
            'excerpt' => $this->extractExcerpt($raw),
            'raw' => $raw,
            'html' => Str::markdown($raw, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
            'modified_at' => Carbon::createFromTimestamp($file->getMTime()),
            'headings' => $headings,
            'checklists' => $checklists,
            'category' => $this->resolveCategory($file->getBasename('.md')),
        ];
    }

    private function extractTitle(string $raw, string $fallback): string
    {
        if (preg_match('/^#\s+(.+)$/m', $raw, $matches) === 1) {
            return trim($matches[1]);
        }

        return Str::headline($fallback);
    }

    private function extractExcerpt(string $raw): string
    {
        $lines = preg_split('/\R/', $raw) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || Str::startsWith($trimmed, ['#', '- [', '```'])) {
                continue;
            }

            return Str::limit($trimmed, 160);
        }

        return 'Documento interno del proyecto.';
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function extractHeadings(string $raw): array
    {
        preg_match_all('/^(##|###)\s+(.+)$/m', $raw, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(static fn (array $match): array => [
                'level' => $match[1],
                'text' => trim($match[2]),
                'id' => Str::slug(trim($match[2])),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{total:int, completed:int, pending:int}
     */
    private function checklistCounts(string $raw): array
    {
        preg_match_all('/^\s*[-*]\s+\[(x| )\]\s+/mi', $raw, $matches);

        $items = $matches[1] ?? [];
        $completed = count(array_filter($items, static fn (string $value): bool => strtolower($value) === 'x'));
        $total = count($items);

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => max(0, $total - $completed),
        ];
    }

    private function sectionContent(string $raw, string $heading): string
    {
        $pattern = '/^##\s+'.preg_quote($heading, '/').'\s*$([\s\S]*?)(?=^##\s+|\z)/mi';

        if (preg_match($pattern, $raw, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }

    private function resolveCategory(string $slug): string
    {
        return match ($slug) {
            'status' => 'Seguimiento',
            'fase-1-roadmap' => 'Planeación',
            'modulos-y-entregables' => 'Alcance',
            default => 'Documentación',
        };
    }
}
