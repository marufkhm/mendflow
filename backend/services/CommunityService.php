<?php

declare(strict_types=1);

final class CommunityService
{
    public function list(array $filters): array
    {
        return [
            'ok' => true,
            'filters' => $filters,
            'items' => [],
        ];
    }
}
