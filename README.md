# Residential Buildings Management Platform

Laravel 12 multi-tenant SaaS platform for residential building operations, accounting, subscriptions, maintenance, and resident self-service.

## Product Surface

The project currently contains two primary application surfaces:

- API-first backend endpoints for mobile clients, tenant operations, and platform-owner administration.
- Blade web application work that begins with Milestone 24 to complete the full admin and resident portal experience.

Core implemented backend domains include:

- platform owner tenant management
- subscription lifecycle and reminders
- tenant isolation middleware
- resident wallet and debit visibility APIs
- ledger-based accounting services
- journal entries and trial balance
- maintenance domain foundations
- audit logging
- queue-backed notifications
- shared-hosting deployment support

## Technical Principles

- All tenant-owned operations must enforce tenant isolation.
- Controllers stay thin; business logic belongs in services, actions, DTOs, policies, and requests.
- Financial truth comes from ledgers and accounting records, not cached balances.
- Financial records are never deleted; reversals create compensating history.
- Shared-hosting compatibility is a first-class constraint.
- Application code remains English-only while the UI supports Arabic and English.

## Current Routes

- Web routes currently host the Blade surface and authentication entrypoints.
- API routes live under `/api` and `/api/v1`.
- Resident mobile authentication uses Sanctum bearer tokens.

See:

- `docs/MOBILE_API.md`
- `docs/OPERATIONS.md`
- `docs/WEB_ADMIN_COMPLETION_PLAN.md`

## Local Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder
npm install
npm run build
php artisan app:health
```

## Shared Hosting Notes

The project is designed to remain deployable on shared hosting that supports:

- PHP 8.2+
- Composer
- MySQL or MariaDB
- SSH access
- cron jobs
- Laravel `public/` web root

Operational details live in `docs/OPERATIONS.md`.

## Documentation

- milestone execution: `docs/TASKS.md`
- Blade web completion plan: `docs/WEB_ADMIN_COMPLETION_PLAN.md`
- platform owner operations: `docs/PLATFORM_OWNER.md`
- accounting workflows: `docs/ACCOUNTANT.md`
- resident workflows: `docs/RESIDENT.md`
- mobile API usage: `docs/MOBILE_API.md`
- deployment runbook: `docs/OPERATIONS.md`
