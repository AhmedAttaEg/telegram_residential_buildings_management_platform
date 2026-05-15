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

* [x] Create new Laravel application.
* [x] Verify Composer dependencies install successfully.
* [x] Ensure `.env.example` exists.
* [x] Do not install frontend starter kits initially.

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

* [x] Configure `.gitignore`.
* [x] Ignore `.env`, vendor, logs, cache, dumps, and generated files.
* [x] Keep `.env.example` committed.
* [x] Add `.gitkeep` files where required.

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

* [x] Create `docs/` directory.
* [x] Add `docs/PRD.md`.
* [x] Add `docs/BLUEPRINT.md`.
* [x] Add `docs/TASKS.md`.
* [x] Add `docs/README.md`.

### WtD

Verify all documentation files exist.

### DoD

Project documentation structure exists and is committed.

---

## 0.4 Configure Laravel Health Command

* [x] Create `app:health` command.
* [x] Verify Laravel boot.
* [x] Verify DB connectivity.
* [x] Print concise health report.

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

* [x] Configure MySQL variables.
* [x] Configure queue variables.
* [x] Configure cache variables.
* [x] Configure mail variables.
* [x] Configure tenant defaults.

### WtD

Copy `.env.example` into `.env` and verify no missing variables exist.

### DoD

Application boots successfully with documented environment variables.

---

## 1.2 Configure Localization

* [x] Configure Arabic locale.
* [x] Configure English locale.
* [x] Configure fallback locale.
* [x] Create translation directory structure.

### WtD

Switch locale and verify translated strings load correctly.

### DoD

Arabic and English localization work correctly.

---

## 1.3 Configure Queue System

* [x] Configure database queue driver.
* [x] Configure failed jobs table.
* [x] Configure queue workers.
* [x] Configure scheduler support.

### WtD

Run:

```bash
php artisan queue:work --once
```

### DoD

Queue worker executes without configuration errors.

---

## 1.4 Configure Logging Strategy

* [x] Configure daily logs.
* [x] Configure accounting logs.
* [x] Configure audit logs.
* [x] Configure API exception logging.
* [x] Configure sensitive-data redaction.

### WtD

Trigger test logs and inspect generated log files.

### DoD

Structured logging works without leaking secrets.

---

# 🧩 Milestone 2: Multi-Tenant Foundation

## 2.1 Create Tenant Migration

* [x] Create `tenants` table.
* [x] Add tenant status.
* [x] Add subscription fields.
* [x] Add feature flags JSON.
* [x] Add branding fields.

### WtD

Run migrations and inspect schema.

### DoD

`tenants` table supports SaaS tenant lifecycle.

---

## 2.2 Create Tenant Model

* [x] Create `Tenant` model.
* [x] Add relationships.
* [x] Add casts.
* [x] Add scopes.

### WtD

Create tenant through Tinker.

### DoD

Tenant model works with relationships and scopes.

---

## 2.3 Create Tenant Middleware

* [x] Resolve tenant from request.
* [x] Validate active subscription.
* [x] Prevent cross-tenant access.
* [x] Attach tenant context globally.

### WtD

Access tenant routes with valid and invalid tenant contexts.

### DoD

Tenant isolation works correctly.

---

## 2.4 Create Tenant Feature Flags System

* [x] Create feature flag structure.
* [x] Add tenant module toggles.
* [x] Add helper methods.
* [x] Add middleware support.

### WtD

Enable and disable features for test tenants.

### DoD

Tenant feature customization works.

---

# 🧩 Milestone 3: Authentication & Authorization

## 3.1 Configure Sanctum Authentication

* [x] Install Sanctum.
* [x] Configure API authentication.
* [x] Configure token expiration.
* [x] Configure mobile token flow.

### WtD

Authenticate using API token.

### DoD

API authentication works correctly.

---

## 3.2 Create Users Migration

* [x] Create `users` table.
* [x] Add tenant ownership.
* [x] Add role fields.
* [x] Add language preference.
* [x] Add status fields.

### WtD

Run migrations and inspect schema.

### DoD

Users table supports multi-tenant roles.

---

## 3.3 Create Roles & Permissions System

* [x] Create roles migration.
* [x] Create permissions migration.
* [x] Create pivot tables.
* [x] Add role middleware.

### WtD

Assign permissions and test route access.

### DoD

Role-based authorization works correctly.

---

## 3.4 Seed Default Roles

* [x] Seed platform owner role.
* [x] Seed tenant owner role.
* [x] Seed accountant role.
* [x] Seed maintenance role.
* [x] Seed resident role.

### WtD

Verify seeded roles exist.

### DoD

Default platform roles are available.

---

# 🧩 Milestone 4: Platform Owner Administration

## 4.1 Create Platform Owner Guard

* [x] Create owner middleware.
* [x] Restrict owner routes.
* [x] Prevent tenant escalation.
* [x] Add tests.

### WtD

Attempt owner route access with tenant user.

### DoD

Only platform owners can access owner routes.

---

## 4.2 Create Tenant Management APIs

* [x] Create tenant CRUD APIs.
* [x] Add pagination.
* [x] Add filtering.
* [x] Add tenant status updates.

### WtD

Create and manage tenants through APIs.

### DoD

Platform owner can manage tenant lifecycle.

---

## 4.3 Create Tenant Suspension Workflow

* [x] Add suspension service.
* [x] Add grace period support.
* [x] Add reminder support.
* [x] Prevent suspended tenant operations.

### WtD

Suspend tenant and verify restricted access.

### DoD

Suspended tenants cannot use platform features.

---

# 🧩 Milestone 5: Buildings & Units Domain

## 5.1 Create Buildings Migration

* [x] Create `buildings` table.
* [x] Add tenant ownership.
* [x] Add address fields.
* [x] Add status fields.

### WtD

Run migrations and inspect schema.

### DoD

Buildings schema supports tenant isolation.

---

## 5.2 Create Apartments Migration

* [x] Create `apartments` table.
* [x] Add building ownership.
* [x] Add unit identifiers.
* [x] Add occupancy fields.
* [x] Do not use balance/debt as accounting truth.

### WtD

Run migrations and inspect schema.

### DoD

Apartment schema supports accounting linkage.

---

## 5.3 Create Buildings & Apartments Models

* [x] Add relationships.
* [x] Add scopes.
* [x] Add accessors.
* [x] Add tenant filtering.

### WtD

Query buildings and apartments through Eloquent.

### DoD

Building and apartment relationships work correctly.

---

# 🧩 Milestone 6: Residents Domain

## 6.1 Create Residents Migration

* [x] Create `residents` table.
* [x] Add ownership fields.
* [x] Add tenant linkage.
* [x] Add contact fields.

### WtD

Run migrations and inspect schema.

### DoD

Residents schema supports ownership tracking.

---

## 6.2 Create Occupancy Migration

* [x] Create `apartment_residents` pivot.
* [x] Add ownership percentages.
* [x] Add occupancy dates.
* [x] Add tenancy types.

### WtD

Attach residents to apartments.

### DoD

Resident occupancy relationships work correctly.

---

# 🧩 Milestone 7: Accounting Foundation

## 7.1 Create Financial Periods Migration

* [x] Create `financial_periods` table.
* [x] Add tenant linkage.
* [x] Add period status.
* [x] Add locking support.

### WtD

Create financial periods through Tinker.

### DoD

Financial periods support accounting lifecycle.

---

## 7.2 Create Wallet Transactions Migration

* [x] Create wallet ledger table.
* [x] Add transaction types.
* [x] Add reversal support.
* [x] Add indexes.

### WtD

Insert test transactions.

### DoD

Wallet ledger schema supports accounting truth.

---

## 7.3 Create Debit Transactions Migration

* [x] Create debit ledger table.
* [x] Add manual debit types.
* [x] Add payment linkage.
* [x] Add reversal support.

### WtD

Insert debit transactions.

### DoD

Debit ledger supports audit-safe accounting.

---

## 7.4 Create Expenses Migration

* [x] Create expenses table.
* [x] Add building linkage.
* [x] Add creator tracking.
* [x] Add approval status.

### WtD

Create expense records.

### DoD

Expense schema supports future workflows.

---

## 7.5 Create Expense Splits Migration

* [x] Create expense splits table.
* [x] Add apartment linkage.
* [x] Add confirmation fields.
* [x] Add payment status.

### WtD

Allocate expenses across apartments.

### DoD

Expense split workflow is supported.

---

## 7.6 Create Expense Payments Migration

* [x] Create expense payments table.
* [x] Add wallet linkage.
* [x] Add reversal fields.
* [x] Add audit fields.

### WtD

Create payment records.

### DoD

Expense payments support audit-safe reversals.

---

# 🧩 Milestone 8: Accounting Services

## 8.1 Create WalletService

* [x] Implement wallet balance calculation.
* [x] Implement deposits.
* [x] Implement deductions.
* [x] Implement reversal support.

### WtD

Create transactions and verify calculated balances.

### DoD

Wallet balances derive only from ledger transactions.

---

## 8.2 Create DebitService

* [x] Implement debit calculations.
* [x] Implement manual debit logic.
* [x] Implement debit payments.
* [x] Implement reversal support.

### WtD

Create debit transactions and verify balances.

### DoD

Debit balances derive from transactions and unpaid splits.

---

## 8.3 Create ExpensePaymentService

* [x] Implement split payment workflow.
* [x] Deduct wallet balance.
* [x] Create payment records.
* [x] Prevent duplicate payments.
* [x] Support reversals.

### WtD

Pay expense splits and verify ledgers.

### DoD

Expense payment workflow is audit-safe and transactional.

---

# 🧩 Milestone 9: Payment Reversal Workflows

## 9.1 Implement Payment Reversal Service

* [x] Create reversal transactions.
* [x] Restore balances.
* [x] Reopen splits.
* [x] Preserve original records.

### WtD

Reverse payments and inspect resulting ledgers.

### DoD

Reversals restore balances without deleting history.

---

## 9.2 Implement Reversal Audit Logs

* [x] Log reversal actor.
* [x] Log timestamps.
* [x] Log original transaction linkage.
* [x] Log reversal reason.

### WtD

Inspect reversal audit logs.

### DoD

All reversals are fully auditable.

---

# 🧩 Milestone 10: Enterprise Accounting

## 10.1 Create Chart of Accounts

* [x] Create accounts migration.
* [x] Add account hierarchy.
* [x] Add account types.
* [x] Add tenant linkage.

### WtD

Create and query ledger accounts.

### DoD

Chart of accounts supports enterprise accounting.

---

## 10.2 Create Journal Entries

* [x] Create journal entries migration.
* [x] Create journal lines migration.
* [x] Enforce balancing.
* [x] Add posting support.

### WtD

Create balanced journal entries.

### DoD

Journal entries enforce double-entry accounting.

---

## 10.3 Create Trial Balance Reports

* [x] Calculate account balances.
* [x] Group accounts.
* [x] Generate summaries.
* [x] Add export support.

### WtD

Generate trial balance report.

### DoD

Trial balance reflects journal activity correctly.

---

# 🧩 Milestone 11: Maintenance Management

## 11.1 Create Maintenance Tickets Migration

* [x] Create tickets table.
* [x] Add priorities.
* [x] Add assignment fields.
* [x] Add status fields.

### WtD

Create maintenance tickets.

### DoD

Ticketing system supports operational workflows.

---

## 11.2 Create Work Orders Migration

* [x] Create work orders table.
* [x] Link tickets.
* [x] Add technician assignment.
* [x] Add SLA tracking.

### WtD

Create work orders from tickets.

### DoD

Work order lifecycle works correctly.

---

# 🧩 Milestone 12: Notifications System

## 12.1 Create Notification Infrastructure

* [x] Configure database notifications.
* [x] Configure email notifications.
* [x] Configure Telegram notifications.
* [x] Configure queue dispatch.

### WtD

Send test notifications.

### DoD

Notifications work asynchronously.

---

## 12.2 Create Subscription Reminder Notifications

* [x] Notify before expiration.
* [x] Notify on grace period.
* [x] Notify on suspension.
* [x] Support configurable schedules.

### WtD

Trigger subscription reminders.

### DoD

Subscription notifications work correctly.

---

# 🧩 Milestone 13: Subscription Billing

## 13.1 Create Subscription Plans Migration

* [x] Create plans table.
* [x] Add pricing fields.
* [x] Add feature limits.
* [x] Add billing cycle fields.

### WtD

Create subscription plans.

### DoD

Subscription plans support SaaS billing.

---

## 13.2 Create Tenant Subscriptions Migration

* [x] Create subscriptions table.
* [x] Add plan linkage.
* [x] Add status fields.
* [x] Add renewal dates.

### WtD

Attach plans to tenants.

### DoD

Tenant subscriptions support lifecycle management.

---

# 🧩 Milestone 14: REST API Foundation

## 14.1 Configure API Versioning

* [x] Create API v1 routes.
* [x] Configure namespaces.
* [x] Configure authentication.
* [x] Configure throttling.

### WtD

Call API routes using Postman.

### DoD

Versioned APIs work correctly.

---

## 14.2 Create API Response Standard

* [x] Create success formatter.
* [x] Create error formatter.
* [x] Add pagination format.
* [x] Add validation format.

### WtD

Trigger success and validation responses.

### DoD

API responses are standardized.

---

# 🧩 Milestone 15: Resident Portal APIs

## 15.1 Create Wallet APIs

* [x] Create wallet summary endpoint.
* [x] Create wallet history endpoint.
* [x] Add pagination.
* [x] Add tenant isolation.

### WtD

Query wallet APIs.

### DoD

Residents can retrieve wallet history securely.

---

## 15.2 Create Debit APIs

* [x] Create debit summary endpoint.
* [x] Create unpaid splits endpoint.
* [x] Add filtering.
* [x] Add tenant isolation.

### WtD

Query debit APIs.

### DoD

Residents can retrieve debit information securely.

---

# 🧩 Milestone 16: Mobile Readiness

## 16.1 Create Mobile Authentication Flow

* [x] Add mobile login APIs.
* [x] Add token refresh flow.
* [x] Add logout endpoints.
* [x] Add device tracking.

### WtD

Authenticate from mobile client.

### DoD

Mobile authentication flow works correctly.

---

## 16.2 Create Mobile API Documentation

* [x] Generate endpoint documentation.
* [x] Document authentication.
* [x] Document error responses.
* [x] Document pagination.

### WtD

Review generated API docs.

### DoD

Mobile integration documentation is complete.

---

# 🧩 Milestone 17: Audit & Compliance

## 17.1 Create Audit Logs Migration

* [x] Create audit logs table.
* [x] Add actor tracking.
* [x] Add subject tracking.
* [x] Add old/new values.

### WtD

Trigger auditable operations.

### DoD

Audit logs persist correctly.

---

## 17.2 Add Audit Observers

* [x] Observe accounting models.
* [x] Observe subscription changes.
* [x] Observe permission changes.
* [x] Observe tenant changes.

### WtD

Modify records and inspect audit logs.

### DoD

Critical operations are audited automatically.

---

# 🧩 Milestone 18: Testing Foundation

## 18.1 Configure Testing Environment

* [x] Configure `.env.testing`.
* [x] Configure testing database.
* [x] Configure queue fakes.
* [x] Configure notification fakes.

### WtD

Run:

```bash
php artisan test
```

### DoD

Testing environment boots successfully.

---

## 18.2 Create Migration Smoke Tests

* [x] Test fresh migrations.
* [x] Verify key tables.
* [x] Verify indexes.
* [x] Verify foreign keys.

### WtD

Run migration tests.

### DoD

Database schema is test-verified.

---

## 18.3 Create Accounting Integrity Tests

* [x] Test wallet calculations.
* [x] Test debit calculations.
* [x] Test expense payments.
* [x] Test reversals.

### WtD

Run accounting feature tests.

### DoD

Accounting calculations are reliable.

---

# 🧩 Milestone 19: Performance Optimization

## 19.1 Add Database Index Review

* [x] Review accounting indexes.
* [x] Review tenant indexes.
* [x] Review reporting indexes.
* [x] Add missing composite indexes.

### WtD

Run explain plans on heavy queries.

### DoD

Critical queries are indexed efficiently.

---

## 19.2 Prevent N+1 Queries

* [x] Review Eloquent relationships.
* [x] Add eager loading.
* [x] Add query scopes.
* [x] Add performance tests.

### WtD

Profile API requests.

### DoD

N+1 query problems are eliminated.

---

# 🧩 Milestone 20: Deployment & Operations

## 20.1 Configure Shared Hosting Deployment

* [x] Configure production environment.
* [x] Configure storage permissions.
* [x] Configure queues.
* [x] Configure scheduler.

### WtD

Deploy to Hostinger staging environment.

### DoD

Application deploys successfully on shared hosting.

---

## 20.2 Configure Automated Backups

* [x] Configure DB backups.
* [x] Configure storage backups.
* [x] Configure retention policy.
* [x] Configure restore testing.

### WtD

Generate and restore test backup.

### DoD

Backup and restore workflows operate correctly.

---

# 🧩 Milestone 21: Documentation System

## 21.1 Create Platform Owner Documentation

* [x] Document tenant management.
* [x] Document subscriptions.
* [x] Document feature flags.
* [x] Document suspension workflows.

### WtD

Review documentation completeness.

### DoD

Platform owner documentation is complete.

---

## 21.2 Create Accountant Documentation

* [x] Document expense workflows.
* [x] Document payment workflows.
* [x] Document reversals.
* [x] Document financial periods.

### WtD

Review accounting documentation.

### DoD

Accountant workflows are fully documented.

---

## 21.3 Create Resident Documentation

* [x] Document wallet system.
* [x] Document debit system.
* [x] Document payments.
* [x] Document maintenance requests.

### WtD

Review resident help flows.

### DoD

Resident documentation is complete.

---

# 🧩 Milestone 22: AI Readiness

## 22.1 Create AI Services Namespace

* [x] Create AI service structure.
* [x] Create AI contracts.
* [x] Create prompt DTOs.
* [x] Add provider abstraction.

### WtD

Resolve AI services through container.

### DoD

AI-ready architecture exists without business coupling.

---

## 22.2 Create Anomaly Detection Foundation

* [x] Define anomaly interfaces.
* [x] Define accounting anomaly DTOs.
* [x] Define alert structures.
* [x] Define queue workflows.

### WtD

Dispatch anomaly analysis jobs.

### DoD

AI accounting analysis foundation exists.

---

# 🧩 Milestone 23: Final Verification

## 23.1 Verify Tenant Isolation

* [x] Verify cross-tenant protection.
* [x] Verify API isolation.
* [x] Verify accounting isolation.
* [x] Verify permissions.

### WtD

Attempt unauthorized tenant access.

### DoD

Tenant isolation is fully enforced.

---

## 23.2 Verify Accounting Integrity

* [x] Verify wallet calculations.
* [x] Verify debit calculations.
* [x] Verify reversals.
* [x] Verify financial periods.

### WtD

Run full accounting regression suite.

### DoD

Accounting workflows are audit-safe.

---

## 23.3 Verify Subscription Lifecycle

* [x] Verify grace periods.
* [x] Verify suspensions.
* [x] Verify reactivation.
* [x] Verify notifications.

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

---

# Milestone 24: Full Blade Admin Web Application

## 24.1 Write Web Admin Completion Plan

* [x] Add `docs/WEB_ADMIN_COMPLETION_PLAN.md`.
* [x] Document scope, constraints, implementation order, and module coverage.
* [x] Align the plan with existing API-first and shared-hosting constraints.

### WtD

Review the document for implementation completeness.

### DoD

Milestone 24 has a committed implementation plan.

---

## 24.2 Update Project Documentation Index

* [x] Update `docs/README.md`.
* [x] Replace the generic root `README.md`.
* [x] Link the Blade web application completion plan and usage docs.

### WtD

Open the updated readme files and verify references are accurate.

### DoD

Project documentation reflects the web-application completion effort.

---

## 24.3 Build Web Foundation

* [x] Add authenticated and guest Blade layouts.
* [x] Add reusable Blade components.
* [x] Add Arabic and English locale switching.
* [x] Add RTL handling for Arabic.
* [x] Add role-aware navigation and dashboard resolution.

### WtD

Open the web UI and verify the shared layout renders for authenticated users.

### DoD

Blade foundation supports localized, responsive, reusable web screens.

---

## 24.4 Add Web Authentication

* [x] Add login and logout routes, controllers, views, and requests.
* [x] Preserve existing API and Sanctum authentication.
* [x] Redirect platform owners, tenant staff, and residents to the correct dashboards.
* [x] Add localized validation and error feedback.

### WtD

Log in with representative owner, tenant staff, and resident users.

### DoD

Session-based web authentication works without affecting API auth.

---

## 24.5 Add Policy and Web Authorization Layer

* [ ] Register policies for owner, tenant, accounting, maintenance, and audit resources.
* [ ] Add web-safe middleware and authorization flows.
* [ ] Enforce tenant isolation on every tenant-owned web route.

### WtD

Attempt authorized and unauthorized access across owner, tenant, and resident routes.

### DoD

Web routes enforce role, tenant, and resource ownership correctly.

---

## 24.6 Build Platform Owner Web Admin

* [ ] Add owner dashboard.
* [ ] Add tenants index/create/store/show/edit/update.
* [ ] Add tenant suspend/reactivate workflows.
* [ ] Add tenant feature flag editing.
* [ ] Add subscription lifecycle views and updates.
* [ ] Add subscription plan CRUD.
* [ ] Add platform audit log pages.
* [ ] Add tenant/system health pages.

### WtD

Use a platform owner account to complete the owner dashboard workflow.

### DoD

Platform owners can administer tenants, plans, audit logs, and health screens from Blade.

---

## 24.7 Build Tenant Admin CRUD

* [ ] Add tenant dashboard.
* [ ] Add buildings CRUD.
* [ ] Add apartments CRUD.
* [ ] Add residents CRUD.
* [ ] Add occupancy assignment workflows.
* [ ] Add tenant users and staff CRUD.
* [ ] Add role assignment by slug.
* [ ] Add tenant settings and audit log pages.

### WtD

Use a tenant admin account to create and manage tenant records through Blade.

### DoD

Tenant staff can manage operational data safely within tenant boundaries.

---

## 24.8 Build Accounting Admin UI

* [ ] Add financial period pages and close/reopen actions.
* [ ] Add wallet transaction pages and deposit workflow.
* [ ] Add debit transaction pages and manual debit workflow.
* [ ] Add expenses, split allocation, payment, and reversal workflows.
* [ ] Add chart of accounts pages.
* [ ] Add journal entry create/show/post pages.
* [ ] Add trial balance, wallet, debit, and outstanding balance reports.

### WtD

Run end-to-end accounting workflows through the Blade UI.

### DoD

Accounting mutations reuse existing services and preserve ledger truth.

---

## 24.9 Build Maintenance Admin UI

* [ ] Add maintenance ticket CRUD and status workflows.
* [ ] Add work order CRUD and assignment workflows.
* [ ] Add SLA fields and maintenance reporting pages.

### WtD

Use tenant maintenance flows to create, assign, and track tickets and work orders.

### DoD

Maintenance workflows are usable through the web application.

---

## 24.10 Build Resident Web Portal

* [ ] Add resident dashboard.
* [ ] Add wallet and debit pages.
* [ ] Add expense/payment history pages.
* [ ] Add resident maintenance ticket pages.
* [ ] Add resident profile page.
* [ ] Add notifications page when database notifications exist.

### WtD

Log in as a resident and verify only resident-owned data is visible.

### DoD

Residents can use a safe, isolated web portal for self-service workflows.

---

## 24.11 Add Reports and CSV Exports

* [ ] Add CSV exports for tenants, residents, apartments, wallet, debit, outstanding balances, maintenance, and audit logs.
* [ ] Use streamed responses or standard downloads only.
* [ ] Avoid heavy dependencies.

### WtD

Download representative CSV reports and inspect the exported contents.

### DoD

Operational web reports support lightweight export workflows.

---

## 24.12 Add Web Feature Test Coverage

* [ ] Add login/logout tests.
* [ ] Add owner CRUD tests.
* [ ] Add tenant isolation CRUD tests.
* [ ] Add resident onboarding tests.
* [ ] Add accounting mutation and reversal tests.
* [ ] Add maintenance workflow tests.
* [ ] Add resident portal isolation tests.
* [ ] Add authorization failure tests.

### WtD

Run:

```bash
php artisan test
```

### DoD

Milestone 24 behavior is covered by automated feature tests.

---

## 24.13 Finalize Web Admin Documentation

* [ ] Document web admin access.
* [ ] Document first platform owner creation.
* [ ] Document owner and tenant workflows.
* [ ] Document resident onboarding, accounting, maintenance, and reports.
* [ ] Document shared-hosting route/cache/asset notes.

### WtD

Review all documentation for completeness against the delivered Blade UI.

### DoD

Web application workflows are documented for operators and implementers.
