# Documentation Index

This directory contains the project's implementation references.

- `PRD.md` defines the product requirements and expected business scope.
- `BLUEPRINT.md` defines the technical architecture and implementation direction.
- `TASKS.md` is the execution checklist and milestone order of record.
- `WEB_ADMIN_COMPLETION_PLAN.md` defines the Blade web-application completion plan for Milestone 24.
- `MOBILE_API.md` documents the current mobile authentication flow and mobile-facing API usage.
- `OPERATIONS.md` documents the shared-hosting deployment, scheduler, queue, backup, and restore workflows.
- `PLATFORM_OWNER.md` documents tenant administration, subscriptions, feature flags, and suspension workflows for platform owners.
- `ACCOUNTANT.md` documents accounting operations for expenses, split payments, reversals, and financial periods.
- `RESIDENT.md` documents resident-facing wallet, debit, payment, and support flows.

Use `TASKS.md` as the authoritative sequence for implementation work. Update these documents when the product scope, technical design, or delivery plan changes.

Role-focused reading order:

- Platform owner workflows: `PLATFORM_OWNER.md`
- Accountant workflows: `ACCOUNTANT.md`
- Resident help flows: `RESIDENT.md`

Implementation-focused reading order:

- Delivery milestones and sequence: `TASKS.md`
- Blade web application completion: `WEB_ADMIN_COMPLETION_PLAN.md`
- Product scope and role expectations: `PRD.md`
- Technical behavior and accounting architecture: `BLUEPRINT.md`
- Mobile authentication and resident API details: `MOBILE_API.md`
- Shared-hosting operations and scheduled jobs: `OPERATIONS.md`

Useful bootstrap verification commands:

- `php artisan app:env-check`
- `php artisan app:health`
- `php artisan queue:work --once`
- `php artisan app:log-smoke-test`
- `php artisan backups:run`
