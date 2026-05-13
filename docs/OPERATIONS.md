# Operations Runbook

## Hostinger Staging Deploy

Target assumptions:

- Hostinger shared hosting with SSH and Git access.
- Laravel project lives outside `public_html` when possible.
- The web root points to the Laravel `public/` directory.

First-time setup:

1. Clone the repository on the staging host.
2. Copy `.env.hostinger.example` to `.env` and fill the real credentials and tokens.
3. Run `composer install --no-dev --optimize-autoloader`.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate --force`.
6. Run `php artisan storage:link`.
7. Ensure `storage/` and `bootstrap/cache/` are writable by the PHP user.
8. Run `php artisan app:env-check --reference=.env.hostinger.example`.
9. Run `php artisan app:health`.

Repeatable deploy flow:

1. `git pull`
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. `php artisan optimize:clear`
5. `php artisan config:cache`
6. `php artisan route:cache`
7. `php artisan view:cache`
8. `php artisan storage:link` if the public symlink is missing
9. `php artisan app:env-check --reference=.env.hostinger.example`
10. `php artisan app:health`
11. `php artisan queue:work database --stop-when-empty --max-time=55 --sleep=3 --tries=1`

## Cron Jobs

Scheduler cron:

```bash
* * * * * /usr/bin/php /home/USER/app/artisan schedule:run >> /dev/null 2>&1
```

Queue worker cron:

```bash
* * * * * /usr/bin/php /home/USER/app/artisan queue:work database --stop-when-empty --max-time=55 --sleep=3 --tries=1 >> /dev/null 2>&1
```

## Backups

The application stores backups under `BACKUP_ROOT`, which defaults to `storage/app/backups`.

Available commands:

- `php artisan backups:run`
- `php artisan backups:run --database-only`
- `php artisan backups:run --storage-only`
- `php artisan backups:cleanup`
- `php artisan backups:restore BACKUP_ID --force`

Backup contents:

- file-based SQLite copy in testing or SQL dump on MySQL/MariaDB
- `storage.tar` archive containing `storage/app`, `storage/framework`, and `storage/logs`
- `manifest.json` describing the backup set

Retention policy:

- keep daily backups for 7 days
- keep one weekly backup for 4 weeks after the daily window

## Restore Workflow

1. Identify the backup directory name under `BACKUP_ROOT`.
2. Put the application in maintenance mode if needed.
3. Run `php artisan backups:restore BACKUP_ID --force`.
4. Run `php artisan app:health`.
5. Confirm storage files and expected tenant data are present.

## Rollback Notes

- If a deploy fails before migrations, reset the working tree to the last known good commit and redeploy.
- If a deploy fails after migrations, restore the latest backup set before re-enabling traffic.
- If `storage` or `bootstrap/cache` become unwritable, fix ownership/permissions and rerun `php artisan app:health`.
