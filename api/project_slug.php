<?php
/**
 * Project slug helpers — shared by projects.php and search.php
 */
function ensureProjectSlugColumn(PDO $pdo): bool
{
    static $done = false;
    if ($done) {
        return true;
    }
    try {
        $col = $pdo->query("SHOW COLUMNS FROM projects LIKE 'slug'")->fetch();
        if (!$col) {
            $pdo->exec('ALTER TABLE projects ADD COLUMN slug VARCHAR(100) NULL DEFAULT NULL AFTER title');
        }
        try {
            $idx = $pdo->query("SHOW INDEX FROM projects WHERE Key_name = 'idx_projects_slug'")->fetch();
            if (!$idx) {
                $pdo->exec('ALTER TABLE projects ADD UNIQUE INDEX idx_projects_slug (slug)');
            }
        } catch (Throwable $e) {}

        $rows = $pdo->query("SELECT id, title FROM projects WHERE slug IS NULL OR slug = ''")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $slug = projectUniqueSlug($pdo, (string)$row['title'], (int)$row['id']);
            $pdo->prepare('UPDATE projects SET slug = ? WHERE id = ?')->execute([$slug, (int)$row['id']]);
        }
        $done = true;
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function projectUniqueSlug(PDO $pdo, string $title, int $excludeId = 0): string
{
    $base = preg_replace('/[^a-z0-9]+/i', '-', mb_strtolower(trim($title)));
    $base = trim((string)$base, '-') ?: 'project';
    if (mb_strlen($base) > 80) {
        $base = mb_substr($base, 0, 80);
    }
    $slug = $base;
    $n = 2;
    while (true) {
        $st = $pdo->prepare('SELECT id FROM projects WHERE slug = ? AND id != ? LIMIT 1');
        $st->execute([$slug, $excludeId]);
        if (!$st->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n;
        $n++;
    }
}

function projectResolveId(PDO $pdo, int $id, string $slug): int
{
    if ($id > 0) {
        return $id;
    }
    $slug = trim($slug);
    if ($slug === '') {
        return 0;
    }
    ensureProjectSlugColumn($pdo);
    $st = $pdo->prepare('SELECT id FROM projects WHERE slug = ? LIMIT 1');
    $st->execute([$slug]);
    return (int)$st->fetchColumn();
}

function projectInterestTokens(PDO $pdo, int $userId): array
{
    $tokens = [];
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
        $sel = ['id'];
        foreach (['specialty', 'interest1', 'interest2', 'interest3', 'organization'] as $c) {
            if (in_array($c, $cols, true)) {
                $sel[] = $c;
            }
        }
        $st = $pdo->prepare('SELECT ' . implode(', ', $sel) . ' FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $u = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        foreach ($u as $k => $v) {
            if ($k === 'id' || !$v) {
                continue;
            }
            foreach (preg_split('/[\s,;|]+/u', (string)$v, -1, PREG_SPLIT_NO_EMPTY) as $part) {
                $tokens[] = mb_strtolower(trim($part));
            }
        }
    } catch (Throwable $e) {}
    $interestMap = [
        'frontend' => ['web', 'product'],
        'backend' => ['web', 'product'],
        'mobile' => ['mobile', 'product'],
        'ai' => ['ai_ml', 'research'],
        'data' => ['ai_ml', 'research'],
        'design' => ['design', 'product'],
        'gamedev' => ['other', 'mobile'],
        'devops' => ['web', 'product'],
        'product' => ['product'],
        'startup' => ['product'],
        'research' => ['research'],
    ];
    $expanded = $tokens;
    foreach ($tokens as $t) {
        if (isset($interestMap[$t])) {
            $expanded = array_merge($expanded, $interestMap[$t]);
        }
    }
    return array_values(array_unique(array_filter($expanded)));
}

function projectScoreForUser(array $project, array $tokens): int
{
    if (!$tokens) {
        return 0;
    }
    $score = 0;
    $cat = (string)($project['category'] ?? '');
    $tags = is_array($project['tags'] ?? null)
        ? implode(' ', $project['tags'])
        : (string)($project['tags'] ?? '');
    $hay = mb_strtolower($cat . ' ' . $tags . ' ' . ($project['title'] ?? '') . ' ' . ($project['description'] ?? ''));

    foreach ($tokens as $tok) {
        if ($tok === $cat) {
            $score += 8;
        }
        if (str_contains($hay, $tok)) {
            $score += 4;
        }
    }
    $score += min(5, (int)($project['member_count'] ?? 0));
    return $score;
}
