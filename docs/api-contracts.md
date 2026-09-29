# Mendflow — контракты API (счастливый путь)

Базовый URL: `https://mendflow.us/api/`. Тело запросов и ответов — JSON.
Авторизация: заголовок `Authorization: Bearer <token>` (альтернатива — `X-Auth-Token`).
Ошибки: HTTP-код 4xx/5xx и тело `{"error": "..."}`.

## Регистрация и вход

### POST `register.php`

```json
{ "firstName": "Иван", "lastName": "Петров", "email": "ivan@example.com", "password": "..." }
```

`200` — токен **не выдаётся**, нужен код из письма:

```json
{ "success": true, "requires_verification": true, "email_verified": false, "email": "ivan@example.com", "message": "..." }
```

Ошибки: `400` пустые поля / формат email / слабый пароль, `409` email занят, `429` > 15 попыток в час.
Аналоги для других ролей: `register-company.php`, `register-university.php`.

### POST `verify-email.php`

```json
{ "email": "ivan@example.com", "code": "123456" }
```

`200`:

```json
{ "success": true, "email_verified": true, "token": "...", "user": { "id": 1, "first_name": "Иван" }, "message": "Email подтверждён!" }
```

Ошибки: `400` неверный или просроченный код, `429` слишком много попыток.
Повторная отправка кода: `resend-verification.php`.

### POST `login.php`

```json
{ "email": "ivan@example.com", "password": "..." }
```

`200`: `{ "success": true, "token": "...", "user": { ... } }`

Ошибки: `401` неверные данные, `403` email не подтверждён (`requires_verification: true`, код отправлен повторно), `403` аккаунт заблокирован (`banned: true`), `429` > 40 попыток за 15 минут.

## Лента

### GET `posts.php?page=1&mode=recommendations`

`mode`: `mixed` (по умолчанию) | `friends` | `subscriptions` | `recommendations`.

`200`: `{ "posts": [ { "id": 10, "content": "...", "author": { ... }, "likes_count": 3 } ], "page": 1, "has_more": true }`

Один пост: `GET posts.php?single=1&id=10` → `{ "post": { ... } }`.

### POST `posts.php` (auth)

```json
{ "content": "Текст", "image": null, "post_type": "post" }
```

`post_type`: `post` | `discussion` | `article`. `200`: `{ "post": { ... } }`. Ошибка: `400` пустой текст.

### DELETE `posts.php?id=10` (auth)

`200`: `{ "success": true }`. Ошибка: `403` не автор.

### POST `post_signals.php` (auth)

```json
{ "action": "batch", "signals": [ { "post_id": 10, "impression": 1, "click": 0, "read_ms": 5400, "watch_ms": 0 } ] }
```

`200`: `{ "success": true, "count": 2 }`. Не более 40 сигналов за запрос.

## Проекты

### POST `projects.php` (auth)

```json
{ "action": "create", "title": "Mendflow", "description": "...", "category": "other", "tags": ["php", "spa"], "is_public": 1 }
```

`200`: `{ "project": { ... }, "seed": { ... } }`. Ошибка: `400` нет названия.

### GET `projects.php?action=...`

| action | Ответ |
|---|---|
| `explore` | `{ "projects": [...] }` |
| `recommended` | `{ "projects": [...] }` |
| `my` | `{ "projects": [...] }` |
| `detail&id=5` | `{ "project": { ... } }`; `404` нет проекта, `403` закрытый |
| `members&id=5` | `{ "members": [...] }` |

Заявка в команду: `POST projects.php` `{ "action": "join_request", "project_id": 5 }`.
Публичная страница: `GET /p/{slug}` (SEO-обёртка `p.php` + SPA).

## Вакансии

### GET `jobs.php?action=list`

`200`: `{ "vacancies": [...] }`. Детали: `action=detail&id=7` → `{ "vacancy": { ... } }`.

### POST `jobs.php` — компания (auth)

```json
{ "action": "create", "title": "Junior PHP", "description": "..." }
```

`200`: `{ "success": true, "vacancy": { ... } }`. Ошибки: `400` нет title, `403` компания не верифицирована (вакансия сохраняется черновиком).

### POST `jobs.php` — отклик студента (auth)

```json
{ "action": "apply", "id": 7, "message": "Здравствуйте!" }
```

`200`: `{ "success": true, "application_id": 12 }`; к отклику прикладывается снимок резюме.
Ошибки: `400` вакансия закрыта, `403` недоступна для профиля, `409` уже откликнулся.
