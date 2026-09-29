<?php
/**
 * Public project page — SEO meta + SPA (index.html).
 * Nginx: rewrite ^/p/([a-zA-Z0-9_-]+)/?$ /p.php?slug=$1 last;
 */
declare(strict_types=1);

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/project_slug.php';

function p_esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function p_abs_url(string $path): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'mendflow.us';
    $scheme = 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = (string)$_SERVER['HTTP_X_FORWARDED_PROTO'];
    } elseif (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $scheme = 'http';
    }
    return $scheme . '://' . $host . $path;
}

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '' && !empty($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/p/([^/?#]+)#', (string)$_SERVER['REQUEST_URI'], $m)) {
        $slug = urldecode($m[1]);
    }
}

$project = null;
if ($slug !== '') {
    try {
        $pdo = new PDO(
            'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4',
            env('DB_USER'),
            env('DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        ensureProjectSlugColumn($pdo);
        $st = $pdo->prepare("
            SELECT p.id, p.slug, p.title, p.description, p.cover_url, p.category, p.stage, p.tags, p.is_public,
                   u.first_name, u.last_name
            FROM projects p
            JOIN users u ON p.owner_id = u.id
            WHERE p.slug = ? LIMIT 1
        ");
        $st->execute([$slug]);
        $row = $st->fetch();
        if ($row && (int)($row['is_public'] ?? 0) === 1) {
            $project = $row;
        }
    } catch (Throwable $e) {
        $project = null;
    }
}

$indexFile = __DIR__ . '/index.html';
if (!is_readable($indexFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'index.html not found';
    exit;
}

$html = (string)file_get_contents($indexFile);

if ($project) {
    $pageTitle = p_esc($project['title'] . ' — Mendflow');
    $descRaw = trim(strip_tags((string)($project['description'] ?? '')));
    if ($descRaw === '') {
        $owner = trim(($project['first_name'] ?? '') . ' ' . ($project['last_name'] ?? ''));
        $descRaw = 'Проект «' . ($project['title'] ?? '') . '» на Mendflow';
        if ($owner !== '') {
            $descRaw .= ' · ' . $owner;
        }
    }
    $desc = p_esc(mb_substr($descRaw, 0, 200));
    $canonical = p_esc(p_abs_url('/p/' . rawurlencode((string)$project['slug'])));
    $ogTitle = p_esc((string)$project['title']);
    $cover = (string)($project['cover_url'] ?? '');
    if ($cover !== '' && !preg_match('#^https?://#i', $cover)) {
        $cover = p_abs_url('/' . ltrim($cover, '/'));
    }
    $ogImage = $cover !== '' ? p_esc($cover) : p_esc(p_abs_url('/icons/icon-512.png'));
    $slugJson = json_encode($project['slug'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

    $meta = "\n"
        . "  <meta name=\"description\" content=\"{$desc}\">\n"
        . "  <link rel=\"canonical\" href=\"{$canonical}\">\n"
        . "  <meta property=\"og:type\" content=\"website\">\n"
        . "  <meta property=\"og:site_name\" content=\"Mendflow\">\n"
        . "  <meta property=\"og:title\" content=\"{$ogTitle}\">\n"
        . "  <meta property=\"og:description\" content=\"{$desc}\">\n"
        . "  <meta property=\"og:url\" content=\"{$canonical}\">\n"
        . "  <meta property=\"og:image\" content=\"{$ogImage}\">\n"
        . "  <meta name=\"twitter:card\" content=\"summary_large_image\">\n"
        . "  <meta name=\"twitter:title\" content=\"{$ogTitle}\">\n"
        . "  <meta name=\"twitter:description\" content=\"{$desc}\">\n"
        . "  <meta name=\"twitter:image\" content=\"{$ogImage}\">\n"
        . "  <script>window.__MF_PENDING_PROJECT_SLUG__={$slugJson};</script>\n"
        . "  <noscript><article style=\"max-width:640px;margin:2rem auto;padding:0 1rem;font-family:sans-serif;line-height:1.5\">\n"
        . "    <h1>" . p_esc((string)$project['title']) . "</h1>\n"
        . "    <p>" . $desc . "</p>\n"
        . "    <p><a href=\"{$canonical}\">Открыть проект в Mendflow</a></p>\n"
        . "  </article></noscript>\n";

    $html = preg_replace('/<title>[^<]*<\/title>/i', '<title>' . $pageTitle . '</title>', $html, 1);
    $html = str_replace('</head>', $meta . '</head>', $html);
} elseif ($slug !== '') {
    http_response_code(404);
    $html = preg_replace('/<title>[^<]*<\/title>/i', '<title>Проект не найден — Mendflow</title>', $html, 1);
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=60');
echo $html;
