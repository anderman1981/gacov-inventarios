<?php

declare(strict_types=1);

namespace App\Support\Documentation;

final readonly class ChromeDevToolsMcpRepositoryAnalysis
{
    /**
     * @param  array<string, int>  $toolsByCategory
     * @param  array<int, array{title: string, objective: string, tools: array<int, string>}>  $recommendedWorkflows
     */
    public function __construct(
        public string $repositoryUrl,
        public string $branch,
        public string $packageVersion,
        public string $license,
        public string $nodeRequirement,
        public array $toolsByCategory,
        public array $recommendedWorkflows,
        public string $readmeMarkdown,
        public string $toolReferenceMarkdown,
        public string $knowledgeMarkdown,
        public string $fetchedAt,
    ) {}

    public function totalTools(): int
    {
        return array_sum($this->toolsByCategory);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'repository_url' => $this->repositoryUrl,
            'branch' => $this->branch,
            'package_version' => $this->packageVersion,
            'license' => $this->license,
            'node_requirement' => $this->nodeRequirement,
            'total_tools' => $this->totalTools(),
            'tools_by_category' => $this->toolsByCategory,
            'recommended_workflows' => $this->recommendedWorkflows,
            'fetched_at' => $this->fetchedAt,
        ];
    }
}
