# SQL — порядок сборки

Папка заменяет разрозненные `*.sql` в корне репозитория.

## Новая база

```bash
mysql -u mendflow -p mendflow < sql/00_schema.sql
```

## Живая база (users уже есть)

```bash
mysql -u mendflow -p mendflow < sql/10_upgrade_existing.sql
mysql -u mendflow -p mendflow < sql/20_friendships.sql   # если ошибка Unknown column status
mysql -u mendflow -p mendflow < sql/90_check.sql
```

## Файлы

| Файл | Когда |
|------|--------|
| `00_schema.sql` | Пустая БД. Полная схема по спринтам 1→19 |
| `10_upgrade_existing.sql` | Production без DROP TABLE |
| `20_friendships.sql` | Старая схема `user1_id` → `sender_id` / `status` |
| `90_check.sql` | Диагностика (`./db-check.sh`) |
| `91_reset_email_verification.sql` | Только тест: сброс `email_verified_at` |

Все файлы идемпотентны (можно запускать повторно). Проверено на MariaDB 11: новая БД (`00` → `10` → `20` → `90`) и старая БД из прежнего `install.sql` (`10` → `20`) дают одинаковый набор колонок.

Таблицы фич (статьи, курсы, вакансии, модерация и др.) API создаёт само при первом запросе — список в конце `00_schema.sql`.

Удалены из корня (есть в истории git): `install.sql`, `mendflow_schema.sql`, `check_database.sql`, `migration.sql`, `migration_v2.sql`, `migration_friendships.sql`, `migration_users_profile.sql`, `uni_*_migration.sql`, `university_migration.sql`, `uni_all_migration.sql` (был DROP TABLE), `events_migration.sql`, `projects_migration.sql`, sprint-миграции 5–19, `migration_resource_*.sql`, `migration_feed_ranking.sql`, `fix_missing_core_tables.sql`, `reset_email_verification.sql`.
