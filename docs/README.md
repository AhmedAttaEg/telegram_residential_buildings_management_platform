# Documentation Index

This directory contains the project’s implementation references.

- `PRD.md` defines the product requirements and expected business scope.
- `BLUEPRINT.md` defines the technical architecture and implementation direction.
- `TASKS.md` is the execution checklist and milestone order of record.
- `MOBILE_API.md` documents the current mobile authentication flow and mobile-facing API usage.
- `OPERATIONS.md` documents the shared-hosting deployment, scheduler, queue, backup, and restore workflows.

Use `TASKS.md` as the authoritative sequence for implementation work. Update these documents when the product scope, technical design, or delivery plan changes.

Useful bootstrap verification commands:

- `php artisan app:env-check`
- `php artisan queue:work --once`
- `php artisan app:log-smoke-test`
- `php artisan backups:run`
