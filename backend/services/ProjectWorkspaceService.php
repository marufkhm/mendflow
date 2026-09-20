<?php

declare(strict_types=1);

final class ProjectWorkspaceService
{
    public function list(array $filters): array
    {
        return [
            'ok' => true,
            'filters' => $filters,
            'projects' => [],
        ];
    }
}
