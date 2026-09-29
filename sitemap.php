<?php
/**
 * XML sitemap — public projects for search engines.
 */
declare(strict_types=1);

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/project_slug.php';

function sm_abs_url(string $path): string
{
    $base = rtrim((string)env('APP_URL', 'https://mendflow.us'), '/');
    return $base . $path;
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$urls = [
    ['loc' => sm_abs_url('/'), 'changefreq' => 'daily', 'priority' => '1.0'],
];

try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4',
        env('DB_USER'),
        env('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    ensureProjectSlugColumn($pdo);
    $rows = $pdo->query("
        SELECT slug, updated_at
        FROM projects
        WHERE is_public = 1 AND slug IS NOT NULL AND slug != ''
        ORDER BY updated_at DESC
        LIMIT 5000
    ")->fetchAll();
    foreach ($rows as $row) {
        $slug = trim((string)($row['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $lastmod = !empty($row['updated_at'])
            ? date('Y-m-d', strtotime((string)$row['updated_at']))
            : date('Y-m-d');
        $urls[] = [
            'loc' => sm_abs_url('/p/' . rawurlencode($slug)),
            'lastmod' => $lastmod,
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ];
    }
} catch (Throwable $e) {
    // static homepage only
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1) . "</lastmod>\n";
    }
    echo '    <changefreq>' . htmlspecialchars($u['changefreq'], ENT_XML1) . "</changefreq>\n";
    echo '    <priority>' . htmlspecialchars($u['priority'], ENT_XML1) . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
