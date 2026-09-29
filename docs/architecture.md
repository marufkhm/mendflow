# Mendflow — архитектура (C4)

## Уровень 1. Контекст

```mermaid
flowchart LR
    student([Студент])
    company([Компания])
    uni([Университет])
    admin([Модератор])
    mf[[Mendflow<br/>https://mendflow.us]]
    resend[(Resend<br/>email)]
    push[(Web Push<br/>браузера)]

    student -- лента, проекты, отклики --> mf
    company -- вакансии, отбор --> mf
    uni -- курсы, клубы, обмен --> mf
    admin -- жалобы, баны --> mf
    mf -- коды подтверждения --> resend
    mf -- уведомления --> push
```

## Уровень 2. Контейнеры

```mermaid
flowchart TB
    subgraph browser[Браузер]
        spa[SPA<br/>index.html + /js/*.js<br/>PWA, service worker]
    end

    subgraph server[DigitalOcean: /var/www/html/mendflow]
        nginx[nginx<br/>статика, /p/slug → p.php,<br/>client_max_body_size]
        php[PHP 8.5-FPM<br/>api/*.php]
        db[(MariaDB<br/>53 таблицы ядра + ~45 feature-таблиц)]
        uploads[/uploads/*<br/>файлы пользователей/]
    end

    resend[(Resend API)]

    spa -- HTTPS, JSON, Bearer token --> nginx
    spa -- SSE /api/sse.php --> nginx
    nginx --> php
    php --> db
    php --> uploads
    php -- HTTPS --> resend
```

## Уровень 3. Компоненты API

| Компонент | Файлы | Ответственность |
|---|---|---|
| Ядро | `db.php`, `config.php` | PDO, CORS, `verifyToken()`, rate limit |
| Аутентификация | `register*.php`, `verify-email.php`, `login.php`, `auth_email.php`, `mail.php`, `forgot-password.php`, `reset-password.php` | регистрация трёх типов аккаунтов, код подтверждения, выдача токена |
| Социальный граф | `friends.php`, `friend_recommendations.php`, `network.php`, `blocks.php` | заявки, дружба, рекомендации |
| Лента | `posts.php`, `comments.php`, `like.php`, `reposts.php`, `post_signals.php`, `feed_ranking.php` | посты, сигналы вовлечённости, ранжирование |
| Проекты | `projects.php`, `project_*.php`, `p.php` | проекты, канбан, вехи, роли, публичные ссылки `/p/{slug}` |
| Карьера | `jobs.php`, `companies.php` | вакансии, отклики |
| Университеты | `universities.php`, `courses.php`, `events.php` | курсы, клубы, обмен, мероприятия |
| Realtime | `auth.php`, `sse.php`, `presence.php`, `typing.php`, `realtime_emit.php`, `push.php` | события в реальном времени, web push |
| Модерация | `admin.php`, `reports.php`, `moderation_lib.php` | жалобы, баны |

## Поток алгоритма ленты

```mermaid
sequenceDiagram
    participant C as js/feed-ranking.js
    participant S as api/post_signals.php
    participant R as api/feed_ranking.php
    participant DB as MariaDB

    C->>C: impression (≥35% в зоне видимости), read_ms, watch_ms, click
    C->>S: POST batch (каждые 12 с, до 40 сигналов)
    S->>DB: post_signal_events, user_affinity, post_distribution
    C->>R: GET /api/posts.php?mode=recommendations
    R->>DB: кандидаты (друзья, 2-й круг, тиры test → expanded → full)
    R-->>C: посты по score (вовлечённость × affinity × затухание по времени)
```
