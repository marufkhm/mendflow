# PWE Architecture

PWE is now organized as a layered social-network project:

```text
User
  ↓
Frontend (PWA)
  ↓
REST API
  ↓
Backend
  ↓
Database
```

## Goal

Build PWE as a full student social network with:

- authentication
- student profiles
- student feed
- communities
- internships
- private projects workspace
- inbox and chats
- saved content and recommendations

## Project Structure

```text
mendflow/
├── index.html
├── style.css
├── script.js
├── manifest.json
├── sw.js
├── README.md
├── api/
│   ├── bootstrap.php
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── communities/
│   │   └── list.php
│   ├── feed/
│   │   └── list.php
│   ├── internships/
│   │   └── list.php
│   ├── messages/
│   │   └── list.php
│   ├── profile/
│   │   └── update.php
│   └── projects/
│       └── list.php
├── backend/
│   ├── config/
│   │   └── database.php
│   ├── lib/
│   │   ├── JsonResponse.php
│   │   └── Request.php
│   └── services/
│       ├── AuthService.php
│       ├── CommunityService.php
│       ├── FeedService.php
│       ├── InternshipService.php
│       ├── MessageService.php
│       ├── ProfileService.php
│       └── ProjectWorkspaceService.php
└── database/
    └── schema.sql
```

## Layer Responsibilities

### Frontend (PWA)

Files:

- [`index.html`](/Users/khm/Downloads/mendflow/index.html)
- [`style.css`](/Users/khm/Downloads/mendflow/style.css)
- [`script.js`](/Users/khm/Downloads/mendflow/script.js)
- [`manifest.json`](/Users/khm/Downloads/mendflow/manifest.json)
- [`sw.js`](/Users/khm/Downloads/mendflow/sw.js)

Responsible for:

- auth screens
- app navigation
- student feed UI
- communities UI
- internships filters
- profile editing
- private projects workspace UI
- offline shell and installability

### REST API

Files:

- [`api/bootstrap.php`](/Users/khm/Downloads/mendflow/api/bootstrap.php)
- [`api/auth/register.php`](/Users/khm/Downloads/mendflow/api/auth/register.php)
- [`api/auth/login.php`](/Users/khm/Downloads/mendflow/api/auth/login.php)
- [`api/feed/list.php`](/Users/khm/Downloads/mendflow/api/feed/list.php)
- [`api/communities/list.php`](/Users/khm/Downloads/mendflow/api/communities/list.php)
- [`api/internships/list.php`](/Users/khm/Downloads/mendflow/api/internships/list.php)
- [`api/messages/list.php`](/Users/khm/Downloads/mendflow/api/messages/list.php)
- [`api/profile/update.php`](/Users/khm/Downloads/mendflow/api/profile/update.php)
- [`api/projects/list.php`](/Users/khm/Downloads/mendflow/api/projects/list.php)

Responsible for:

- JSON endpoints
- auth requests
- feed requests
- community search
- internship filters
- messages and chats
- profile update requests
- private projects data

### Backend

Files:

- [`backend/config/database.php`](/Users/khm/Downloads/mendflow/backend/config/database.php)
- [`backend/lib/JsonResponse.php`](/Users/khm/Downloads/mendflow/backend/lib/JsonResponse.php)
- [`backend/lib/Request.php`](/Users/khm/Downloads/mendflow/backend/lib/Request.php)
- [`backend/services/AuthService.php`](/Users/khm/Downloads/mendflow/backend/services/AuthService.php)
- [`backend/services/FeedService.php`](/Users/khm/Downloads/mendflow/backend/services/FeedService.php)
- [`backend/services/CommunityService.php`](/Users/khm/Downloads/mendflow/backend/services/CommunityService.php)
- [`backend/services/InternshipService.php`](/Users/khm/Downloads/mendflow/backend/services/InternshipService.php)
- [`backend/services/MessageService.php`](/Users/khm/Downloads/mendflow/backend/services/MessageService.php)
- [`backend/services/ProfileService.php`](/Users/khm/Downloads/mendflow/backend/services/ProfileService.php)
- [`backend/services/ProjectWorkspaceService.php`](/Users/khm/Downloads/mendflow/backend/services/ProjectWorkspaceService.php)

Responsible for:

- business logic
- validation
- filtering
- shaping API responses
- session-aware user operations

### Database

File:

- [`database/schema.sql`](/Users/khm/Downloads/mendflow/database/schema.sql)

Responsible for:

- users
- profiles
- posts
- follows
- communities
- internships
- messages
- project workspaces
- todos and kanban tasks

## Recommended Next Build Steps

1. Connect frontend fetch requests to the new `/api/...` endpoints.
2. Replace local-only auth state with PHP session-based auth.
3. Persist student profile, feed, communities, internships, inbox, and projects in MySQL.
4. Add server-side high scores or activity metrics if the product later includes learning modules.

## SMTP Setup

Email verification is currently not required in the active flow.

If you later bring it back, SMTP settings should be filled in:

- [`backend/config/hosting.php`](/Users/khm/Downloads/mendflow/backend/config/hosting.php)

Mail fields live in:

- `HostingConfig::mail()`

## Uploads

The project now includes:

- `/uploads/avatars`
- `/uploads/posts`
- `/uploads/projects`
- `/uploads/messages`

Backend upload policy is limited to 7 MB and allows:

- `jpg`
- `jpeg`
- `png`
- `gif`
- `mp4`
- `mov`
- `pdf`

## Current Status

- The UI prototype already exists in the frontend.
- The layered backend structure is now scaffolded.
- The repo is ready to evolve from static prototype into a real product.
- Sections `Internships` and `My Projects` are still present in the codebase, but are currently hidden in the UI temporarily.

## InfinityFree Deploy

Project is now prepared for InfinityFree shared hosting.

### Files already prepared

- [`backend/config/hosting.php`](/Users/khm/Downloads/mendflow/backend/config/hosting.php)
- [`backend/config/database.php`](/Users/khm/Downloads/mendflow/backend/config/database.php)
- [`api/auth/register.php`](/Users/khm/Downloads/mendflow/api/auth/register.php)
- [`api/auth/login.php`](/Users/khm/Downloads/mendflow/api/auth/login.php)
- [`api/auth/stats.php`](/Users/khm/Downloads/mendflow/api/auth/stats.php)
- [`api/auth/users.php`](/Users/khm/Downloads/mendflow/api/auth/users.php)
- [`.htaccess`](/Users/khm/Downloads/mendflow/.htaccess)
- [`database/.htaccess`](/Users/khm/Downloads/mendflow/database/.htaccess)

### Your MySQL values

These are already inserted into [`backend/config/hosting.php`](/Users/khm/Downloads/mendflow/backend/config/hosting.php):

- `host`: `sql207.infinityfree.com`
- `port`: `3306`
- `database`: `if0_41616496_db_mendflow`
- `user`: `if0_41616496`
- `password`: `jZDmxPM7CX7o`

### What you still must edit

Open [`backend/config/hosting.php`](/Users/khm/Downloads/mendflow/backend/config/hosting.php) and replace:

- `https://your-subdomain.infinityfreeapp.com`

with your real InfinityFree site URL in both:

- `appUrl`
- `corsOrigin`

Example:

```php
'appUrl' => 'https://mendflow.infinityfreeapp.com',
'corsOrigin' => 'https://mendflow.infinityfreeapp.com',
```

### What to upload

Upload the full contents of `/Users/khm/Downloads/mendflow` into your InfinityFree `htdocs` folder:

- `index.html`
- `style.css`
- `script.js`
- `manifest.json`
- `sw.js`
- `api/`
- `backend/`
- `database/`
- `uploads/`
- `.htaccess`

### How to create the database

1. Open InfinityFree control panel.
2. Open `phpMyAdmin`.
3. Select database `if0_41616496_db_mendflow`.
4. Open [`database/schema.sql`](/Users/khm/Downloads/mendflow/database/schema.sql).
5. Copy all SQL from that file.
6. Paste it into phpMyAdmin SQL tab and run it.

If the database already exists and you only need the new profile fields, run:

- [`database/migrations/2026_04_18_profile_upgrade.sql`](/Users/khm/Downloads/mendflow/database/migrations/2026_04_18_profile_upgrade.sql)

### How the project behaves on hosting

When the site is opened through real hosting and not `file://`:

- registration goes to MySQL through `api/auth/register.php`
- login goes to MySQL through `api/auth/login.php`
- top counter uses `api/auth/stats.php`
- `Словить конект` loads registered users through `api/auth/users.php`

If you open the file locally without hosting, the site still falls back to localStorage mode.

### Important limitation right now

Chats UI now shows registered users from the server, but actual message storage is still local browser storage in the frontend prototype.
That means real cross-user messaging is not yet server-backed.

So after hosting:

- registration/login/list of users will work through MySQL
- real shared chat history between different devices still needs a backend messages API

### Recommended upload order

1. Edit [`backend/config/hosting.php`](/Users/khm/Downloads/mendflow/backend/config/hosting.php) with your real domain.
2. Import [`database/schema.sql`](/Users/khm/Downloads/mendflow/database/schema.sql) in phpMyAdmin.
3. Upload all files into `htdocs`.
4. Open your site URL.
5. Register a new account.
6. Check login.
7. Check `Словить конект` and registered counter.
