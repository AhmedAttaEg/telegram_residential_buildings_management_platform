# Residential Buildings Management Platform — Tasks & Milestones

> Goal: Build a Laravel + MySQL multi-tenant SaaS platform for residential buildings accounting, property management, maintenance management, subscription management, and enterprise community operations.
>
> The platform must support both simple accounting workflows and enterprise-grade accounting workflows depending on tenant type and enabled feature set.
>
> The platform must support:
>
> - Platform owner management.
> - Multi-tenant architecture.
> - Residential accounting.
> - Maintenance operations.
> - Subscription billing.
> - Resident portals.
> - Mobile-ready APIs.
> - Future AI integrations.
>
> Each task is atomic, implementation-ready, and designed for Codex CLI execution.

---

# 🧭 Execution Rules

* Each task should be small enough to complete in one focused implementation step.
* No task proceeds unless its Definition of Done is met.
* Commit after every completed task.
* Prefer Laravel Services, Actions, DTOs, Enums, Events, and Policies over hidden controller logic.
* Controllers must remain thin.
* All accounting operations must be audit-safe.
* Never use cached balances as accounting truth.
* Financial truth must come from transaction ledgers.
* Never delete financial records.
* Reversals must use compensating transactions.
* Every tenant-owned table must contain `tenant_id`.
* All accounting-critical operations must use database transactions.
* API-first architecture is mandatory.
* Mobile-readiness is mandatory.
* All code naming must be English-only.
* Arabic and English UI support are mandatory.
* Prefer queue-driven notifications and background processing.
* Feature flags must support tenant customization.
* The platform owner must control subscriptions, suspensions, and enabled modules.
* Shared-hosting compatibility is required for MVP.
* Future VPS/cloud migration readiness is mandatory.
* Treat this file as the authoritative execution order.

---

# 🧩 Milestone 0: Project Foundation

## 0.1 Create Laravel Project Skeleton

* [ ] Create new Laravel application.
* [ ] Verify Composer dependencies install successfully.
* [ ] Ensure `.env.example` exists.
* [ ] Do not install frontend starter kits initially.

### WtD

Run:

```bash
php artisan --version
```

Verify Laravel boots correctly.

### DoD

`php artisan --version` executes successfully without errors.

---

## 0.2 Configure Git Hygiene

* [ ] Configure `.gitignore`.
* [ ] Ignore `.env`, vendor, logs, cache, dumps, and generated files.
* [ ] Keep `.env.example` committed.
* [ ] Add `.gitkeep` files where required.

### WtD

Run:

```bash
git status
```

Verify secrets and generated files are ignored.

### DoD

Git excludes runtime and secret files correctly.

---

## 0.3 Create Documentation Structure

* [ ] Create `docs/` directory.
* [ ] Add `docs/PRD.md`.
* [ ] Add `docs/BLUEPRINT.md`.
* [ ] Add `docs/TASKS.md`.
* [ ] Add `docs/README.md`.

### WtD

Verify all documentation files exist.

### DoD

Project documentation structure exists and is committed.

---

## 0.4 Configure Laravel Health Command

* [ ] Create `app:health` command.
* [ ] Verify Laravel boot.
* [ ] Verify DB connectivity.
* [ ] Print concise health report.

### WtD

Run:

```bash
php artisan app:health
```

### DoD

Health command executes successfully.

---

# 🧩 Milestone 1: Environment & Configuration

## 1.1 Configure Environment Variables

* [ ] Configure MySQL variables.
* [ ] Configure queue variables.
* [ ] Configure cache variables.
* [ ] Configure mail variables.
* [ ] Configure tenant defaults.

### WtD

Copy `.env.example` into `.env` and verify no missing variables exist.

### DoD

Application boots successfully with documented environment variables.

---

## 1.2 Configure Localization

* [ ] Configure Arabic locale.
* [ ] Configure English locale.
* [ ] Configure fallback locale.
* [ ] Create translation directory structure.

### WtD

Switch locale and verify translated strings load correctly.

### DoD

Arabic and English localization work correctly.

---

## 1.3 Configure Queue System

* [ ] Configure database queue driver.
* [ ] Configure failed jobs table.
* [ ] Configure queue workers.
* [ ] Configure scheduler support.

### WtD

Run:

```bash
php artisan queue:work --once
```

### DoD

Queue worker executes without configuration errors.

---

## 1.4 Configure Logging Strategy

* [ ] Configure daily logs.
* [ ] Configure accounting logs.
* [ ] Configure audit logs.
* [ ] Configure API exception logging.
* [ ] Configure sensitive-data redaction.

### WtD

Trigger test logs and inspect generated log files.

### DoD

Structured logging works without leaking secrets.

---

# 🧩 Milestone 2: Multi-Tenant Foundation

## 2.1 Create Tenant Migration

* [ ] Create `tenants` table.
* [ ] Add tenant status.
* [ ] Add subscription fields.
* [ ] Add feature flags JSON.
* [ ] Add branding fields.

### WtD

Run migrations and inspect schema.

### DoD

`tenants` table supports SaaS tenant lifecycle.

---

## 2.2 Create Tenant Model

* [ ] Create `Tenant` model.
* [ ] Add relationships.
* [ ] Add casts.
* [ ] Add scopes.

### WtD

Create tenant through Tinker.

### DoD

Tenant model works with relationships and scopes.

---

## 2.3 Create Tenant Middleware

* [ ] Resolve tenant from request.
* [ ] Validate active subscription.
* [ ] Prevent cross-tenant access.
* [ ] Attach tenant context globally.

### WtD

Access tenant routes with valid and invalid tenant contexts.

### DoD

Tenant isolation works correctly.

---

## 2.4 Create Tenant Feature Flags System

* [ ] Create feature flag structure.
* [ ] Add tenant module toggles.
* [ ] Add helper methods.
* [ ] Add middleware support.

### WtD

Enable and disable features for test tenants.

### DoD

Tenant feature customization works.

---

# 🧩 Milestone 3: Authentication & Authorization

## 3.1 Configure Sanctum Authentication

* [ ] Install Sanctum.
* [ ] Configure API authentication.
* [ ] Configure token expiration.
* [ ] Configure mobile token flow.

### WtD

Authenticate using API token.

### DoD

API authentication works correctly.

---

## 3.2 Create Users Migration

* [ ] Create `users` table.
* [ ] Add tenant ownership.
* [ ] Add role fields.
* [ ] Add language preference.
* [ ] Add status fields.

### WtD

Run migrations and inspect schema.

### DoD

Users table supports multi-tenant roles.

---

## 3.3 Create Roles & Permissions System

* [ ] Create roles migration.
* [ ] Create permissions migration.
* [ ] Create pivot tables.
* [ ] Add role middleware.

### WtD

Assign permissions and test route access.

### DoD

Role-based authorization works correctly.

---

## 3.4 Seed Default Roles

* [ ] Seed platform owner role.
* [ ] Seed tenant owner role.
* [ ] Seed accountant role.
* [ ] Seed maintenance role.
* [ ] Seed resident role.

### WtD

Verify seeded roles exist.

### DoD

Default platform roles are available.

---

# 🧩 Milestone 4: Platform Owner Administration

## 4.1 Create Platform Owner Guard

* [ ] Create owner middleware.
* [ ] Restrict owner routes.
* [ ] Prevent tenant escalation.
* [ ] Add tests.

### WtD

Attempt owner route access with tenant user.

### DoD

Only platform owners can access owner routes.

---

## 4.2 Create Tenant Management APIs

* [ ] Create tenant CRUD APIs.
* [ ] Add pagination.
* [ ] Add filtering.
* [ ] Add tenant status updates.

### WtD

Create and manage tenants through APIs.

### DoD

Platform owner can manage tenant lifecycle.

---

## 4.3 Create Tenant Suspension Workflow

* [ ] Add suspension service.
* [ ] Add grace period support.
* [ ] Add reminder support.
* [ ] Prevent suspended tenant operations.

### WtD

Suspend tenant and verify restricted access.

### DoD

Suspended tenants cannot use platform features.

---

# 🧩 Milestone 5: Buildings & Units Domain

## 5.1 Create Buildings Migration

* [ ] Create `buildings` table.
* [ ] Add tenant ownership.
* [ ] Add address fields.
* [ ] Add status fields.

### WtD

Run migrations and inspect schema.

### DoD

Buildings schema supports tenant isolation.

---

## 5.2 Create Apartments Migration

* [ ] Create `apartments` table.
* [ ] Add building ownership.
* [ ] Add unit identifiers.
* [ ] Add occupancy fields.
* [ ] Do not use balance/debt as accounting truth.

### WtD

Run migrations and inspect schema.

### DoD

Apartment schema supports accounting linkage.

---

## 5.3 Create Buildings & Apartments Models

* [ ] Add relationships.
* [ ] Add scopes.
* [ ] Add accessors.
* [ ] Add tenant filtering.

### WtD

Query buildings and apartments through Eloquent.

### DoD

Building and apartment relationships work correctly.

---

# 🧩 Milestone 6: Residents Domain

## 6.1 Create Residents Migration

* [ ] Create `residents` table.
* [ ] Add ownership fields.
* [ ] Add tenant linkage.
* [ ] Add contact fields.

### WtD

Run migrations and inspect schema.

### DoD

Residents schema supports ownership tracking.

---

## 6.2 Create Occupancy Migration

* [ ] Create `apartment_residents` pivot.
* [ ] Add ownership percentages.
* [ ] Add occupancy dates.
* [ ] Add tenancy types.

### WtD

Attach residents to apartments.

### DoD

Resident occupancy relationships work correctly.

---

# 🧩 Milestone 7: Accounting Foundation

## 7.1 Create Financial Periods Migration

* [ ] Create `financial_periods` table.
* [ ] Add tenant linkage.
* [ ] Add period status.
* [ ] Add locking support.

### WtD

Create financial periods through Tinker.

### DoD

Financial periods support accounting lifecycle.

---

## 7.2 Create Wallet Transactions Migration

* [ ] Create wallet ledger table.
* [ ] Add transaction types.
* [ ] Add reversal support.
* [ ] Add indexes.

### WtD

Insert test transactions.

### DoD

Wallet ledger schema supports accounting truth.

---

## 7.3 Create Debit Transactions Migration

* [ ] Create debit ledger table.
* [ ] Add manual debit types.
* [ ] Add payment linkage.
* [ ] Add reversal support.

### WtD

Insert debit transactions.

### DoD

Debit ledger supports audit-safe accounting.

---

## 7.4 Create Expenses Migration

* [ ] Create expenses table.
* [ ] Add building linkage.
* [ ] Add creator tracking.
* [ ] Add approval status.

### WtD

Create expense records.

### DoD

Expense schema supports future workflows.

---

## 7.5 Create Expense Splits Migration

* [ ] Create expense splits table.
* [ ] Add apartment linkage.
* [ ] Add confirmation fields.
* [ ] Add payment status.

### WtD

Allocate expenses across apartments.

### DoD

Expense split workflow is supported.

---

## 7.6 Create Expense Payments Migration

* [ ] Create expense payments table.
* [ ] Add wallet linkage.
* [ ] Add reversal fields.
* [ ] Add audit fields.

### WtD

Create payment records.

### DoD

Expense payments support audit-safe reversals.

---

# 🧩 Milestone 8: Accounting Services

## 8.1 Create WalletService

* [ ] Implement wallet balance calculation.
* [ ] Implement deposits.
* [ ] Implement deductions.
* [ ] Implement reversal support.

### WtD

Create transactions and verify calculated balances.

### DoD

Wallet balances derive only from ledger transactions.

---

## 8.2 Create DebitService

* [ ] Implement debit calculations.
* [ ] Implement manual debit logic.
* [ ] Implement debit payments.
* [ ] Implement reversal support.

### WtD

Create debit transactions and verify balances.

### DoD

Debit balances derive from transactions and unpaid splits.

---

## 8.3 Create ExpensePaymentService

* [ ] Implement split payment workflow.
* [ ] Deduct wallet balance.
* [ ] Create payment records.
* [ ] Prevent duplicate payments.
* [ ] Support reversals.

### WtD

Pay expense splits and verify ledgers.

### DoD

Expense payment workflow is audit-safe and transactional.

---

# 🧩 Milestone 9: Payment Reversal Workflows

## 9.1 Implement Payment Reversal Service

* [ ] Create reversal transactions.
* [ ] Restore balances.
* [ ] Reopen splits.
* [ ] Preserve original records.

### WtD

Reverse payments and inspect resulting ledgers.

### DoD

Reversals restore balances without deleting history.

---

## 9.2 Implement Reversal Audit Logs

* [ ] Log reversal actor.
* [ ] Log timestamps.
* [ ] Log original transaction linkage.
* [ ] Log reversal reason.

### WtD

Inspect reversal audit logs.

### DoD

All reversals are fully auditable.

---

# 🧩 Milestone 10: Enterprise Accounting

## 10.1 Create Chart of Accounts

* [ ] Create accounts migration.
* [ ] Add account hierarchy.
* [ ] Add account types.
* [ ] Add tenant linkage.

### WtD

Create and query ledger accounts.

### DoD

Chart of accounts supports enterprise accounting.

---

## 10.2 Create Journal Entries

* [ ] Create journal entries migration.
* [ ] Create journal lines migration.
* [ ] Enforce balancing.
* [ ] Add posting support.

### WtD

Create balanced journal entries.

### DoD

Journal entries enforce double-entry accounting.

---

## 10.3 Create Trial Balance Reports

* [ ] Calculate account balances.
* [ ] Group accounts.
* [ ] Generate summaries.
* [ ] Add export support.

### WtD

Generate trial balance report.

### DoD

Trial balance reflects journal activity correctly.

---

# 🧩 Milestone 11: Maintenance Management

## 11.1 Create Maintenance Tickets Migration

* [ ] Create tickets table.
* [ ] Add priorities.
* [ ] Add assignment fields.
* [ ] Add status fields.

### WtD

Create maintenance tickets.

### DoD

Ticketing system supports operational workflows.

---

## 11.2 Create Work Orders Migration

* [ ] Create work orders table.
* [ ] Link tickets.
* [ ] Add technician assignment.
* [ ] Add SLA tracking.

### WtD

Create work orders from tickets.

### DoD

Work order lifecycle works correctly.

---

# 🧩 Milestone 12: Notifications System

## 12.1 Create Notification Infrastructure

* [ ] Configure database notifications.
* [ ] Configure email notifications.
* [ ] Configure Telegram notifications.
* [ ] Configure queue dispatch.

### WtD

Send test notifications.

### DoD

Notifications work asynchronously.

---

## 12.2 Create Subscription Reminder Notifications

* [ ] Notify before expiration.
* [ ] Notify on grace period.
* [ ] Notify on suspension.
* [ ] Support configurable schedules.

### WtD

Trigger subscription reminders.

### DoD

Subscription notifications work correctly.

---

# 🧩 Milestone 13: Subscription Billing

## 13.1 Create Subscription Plans Migration

* [ ] Create plans table.
* [ ] Add pricing fields.
* [ ] Add feature limits.
* [ ] Add billing cycle fields.

### WtD

Create subscription plans.

### DoD

Subscription plans support SaaS billing.

---

## 13.2 Create Tenant Subscriptions Migration

* [ ] Create subscriptions table.
* [ ] Add plan linkage.
* [ ] Add status fields.
* [ ] Add renewal dates.

### WtD

Attach plans to tenants.

### DoD

Tenant subscriptions support lifecycle management.

---

# 🧩 Milestone 14: REST API Foundation

## 14.1 Configure API Versioning

* [ ] Create API v1 routes.
* [ ] Configure namespaces.
* [ ] Configure authentication.
* [ ] Configure throttling.

### WtD

Call API routes using Postman.

### DoD

Versioned APIs work correctly.

---

## 14.2 Create API Response Standard

* [ ] Create success formatter.
* [ ] Create error formatter.
* [ ] Add pagination format.
* [ ] Add validation format.

### WtD

Trigger success and validation responses.

### DoD

API responses are standardized.

---

# 🧩 Milestone 15: Resident Portal APIs

## 15.1 Create Wallet APIs

* [ ] Create wallet summary endpoint.
* [ ] Create wallet history endpoint.
* [ ] Add pagination.
* [ ] Add tenant isolation.

### WtD

Query wallet APIs.

### DoD

Residents can retrieve wallet history securely.

---

## 15.2 Create Debit APIs

* [ ] Create debit summary endpoint.
* [ ] Create unpaid splits endpoint.
* [ ] Add filtering.
* [ ] Add tenant isolation.

### WtD

Query debit APIs.

### DoD

Residents can retrieve debit information securely.

---

# 🧩 Milestone 16: Mobile Readiness

## 16.1 Create Mobile Authentication Flow

* [ ] Add mobile login APIs.
* [ ] Add token refresh flow.
* [ ] Add logout endpoints.
* [ ] Add device tracking.

### WtD

Authenticate from mobile client.

### DoD

Mobile authentication flow works correctly.

---

## 16.2 Create Mobile API Documentation

* [ ] Generate endpoint documentation.
* [ ] Document authentication.
* [ ] Document error responses.
* [ ] Document pagination.

### WtD

Review generated API docs.

### DoD

Mobile integration documentation is complete.

---

# 🧩 Milestone 17: Audit & Compliance

## 17.1 Create Audit Logs Migration

* [ ] Create audit logs table.
* [ ] Add actor tracking.
* [ ] Add subject tracking.
* [ ] Add old/new values.

### WtD

Trigger auditable operations.

### DoD

Audit logs persist correctly.

---

## 17.2 Add Audit Observers

* [ ] Observe accounting models.
* [ ] Observe subscription changes.
* [ ] Observe permission changes.
* [ ] Observe tenant changes.

### WtD

Modify records and inspect audit logs.

### DoD

Critical operations are audited automatically.

---

# 🧩 Milestone 18: Testing Foundation

## 18.1 Configure Testing Environment

* [ ] Configure `.env.testing`.
* [ ] Configure testing database.
* [ ] Configure queue fakes.
* [ ] Configure notification fakes.

### WtD

Run:

```bash
php artisan test
```

### DoD

Testing environment boots successfully.

---

## 18.2 Create Migration Smoke Tests

* [ ] Test fresh migrations.
* [ ] Verify key tables.
* [ ] Verify indexes.
* [ ] Verify foreign keys.

### WtD

Run migration tests.

### DoD

Database schema is test-verified.

---

## 18.3 Create Accounting Integrity Tests

* [ ] Test wallet calculations.
* [ ] Test debit calculations.
* [ ] Test expense payments.
* [ ] Test reversals.

### WtD

Run accounting feature tests.

### DoD

Accounting calculations are reliable.

---

# 🧩 Milestone 19: Performance Optimization

## 19.1 Add Database Index Review

* [ ] Review accounting indexes.
* [ ] Review tenant indexes.
* [ ] Review reporting indexes.
* [ ] Add missing composite indexes.

### WtD

Run explain plans on heavy queries.

### DoD

Critical queries are indexed efficiently.

---

## 19.2 Prevent N+1 Queries

* [ ] Review Eloquent relationships.
* [ ] Add eager loading.
* [ ] Add query scopes.
* [ ] Add performance tests.

### WtD

Profile API requests.

### DoD

N+1 query problems are eliminated.

---

# 🧩 Milestone 20: Deployment & Operations

## 20.1 Configure Shared Hosting Deployment

* [ ] Configure production environment.
* [ ] Configure storage permissions.
* [ ] Configure queues.
* [ ] Configure scheduler.

### WtD

Deploy to Hostinger staging environment.

### DoD

Application deploys successfully on shared hosting.

---

## 20.2 Configure Automated Backups

* [ ] Configure DB backups.
* [ ] Configure storage backups.
* [ ] Configure retention policy.
* [ ] Configure restore testing.

### WtD

Generate and restore test backup.

### DoD

Backup and restore workflows operate correctly.

---

# 🧩 Milestone 21: Documentation System

## 21.1 Create Platform Owner Documentation

* [ ] Document tenant management.
* [ ] Document subscriptions.
* [ ] Document feature flags.
* [ ] Document suspension workflows.

### WtD

Review documentation completeness.

### DoD

Platform owner documentation is complete.

---

## 21.2 Create Accountant Documentation

* [ ] Document expense workflows.
* [ ] Document payment workflows.
* [ ] Document reversals.
* [ ] Document financial periods.

### WtD

Review accounting documentation.

### DoD

Accountant workflows are fully documented.

---

## 21.3 Create Resident Documentation

* [ ] Document wallet system.
* [ ] Document debit system.
* [ ] Document payments.
* [ ] Document maintenance requests.

### WtD

Review resident help flows.

### DoD

Resident documentation is complete.

---

# 🧩 Milestone 22: AI Readiness

## 22.1 Create AI Services Namespace

* [ ] Create AI service structure.
* [ ] Create AI contracts.
* [ ] Create prompt DTOs.
* [ ] Add provider abstraction.

### WtD

Resolve AI services through container.

### DoD

AI-ready architecture exists without business coupling.

---

## 22.2 Create Anomaly Detection Foundation

* [ ] Define anomaly interfaces.
* [ ] Define accounting anomaly DTOs.
* [ ] Define alert structures.
* [ ] Define queue workflows.

### WtD

Dispatch anomaly analysis jobs.

### DoD

AI accounting analysis foundation exists.

---

# 🧩 Milestone 23: Final Verification

## 23.1 Verify Tenant Isolation

* [ ] Verify cross-tenant protection.
* [ ] Verify API isolation.
* [ ] Verify accounting isolation.
* [ ] Verify permissions.

### WtD

Attempt unauthorized tenant access.

### DoD

Tenant isolation is fully enforced.

---

## 23.2 Verify Accounting Integrity

* [ ] Verify wallet calculations.
* [ ] Verify debit calculations.
* [ ] Verify reversals.
* [ ] Verify financial periods.

### WtD

Run full accounting regression suite.

### DoD

Accounting workflows are audit-safe.

---

## 23.3 Verify Subscription Lifecycle

* [ ] Verify grace periods.
* [ ] Verify suspensions.
* [ ] Verify reactivation.
* [ ] Verify notifications.

### WtD

Simulate subscription expiration.

### DoD

Subscription lifecycle works correctly.

---

# ✅ Final Definition of Done

* Laravel application boots successfully.
* Multi-tenant architecture works safely.
* Platform owner can manage tenants.
* Tenant feature flags work correctly.
* Residential accounting workflows work correctly.
* Wallet and debit calculations derive from ledgers.
* Expense splits and payments are audit-safe.
* Reversals work without deleting history.
* Financial periods support locking.
* Enterprise accounting supports journal entries.
* Maintenance workflows operate correctly.
* Subscription lifecycle works correctly.
* API-first architecture works correctly.
* Mobile-ready authentication works correctly.
* Audit logs cover critical workflows.
* Automated tests cover accounting and tenant safety.
* Shared-hosting deployment works correctly.
* Documentation exists for all critical roles.
* Platform is scalable toward VPS/cloud migration.
* AI-ready architecture foundation exists.

