# Web Admin Completion Plan

## Objective

Complete the platform as a usable, shared-hosting-friendly Blade web application without breaking the existing API-first architecture, multi-tenant safety, accounting integrity, or mobile authentication flows.

## Current State

- The backend domain is already present for tenants, subscriptions, residents, buildings, apartments, accounting, maintenance, audit logs, notifications, and localization.
- The current app surface is mostly API-driven.
- `routes/web.php` only exposes the default landing page.
- Tailwind and Vite are already available and should be reused.

## Delivery Principles

- Keep controllers thin.
- Prefer Services, Actions, Form Requests, DTOs, Policies, and ViewModels.
- Preserve tenant isolation on all tenant-owned operations.
- Keep all accounting-critical writes transactional.
- Use ledger-derived accounting truth only.
- Never delete financial records; reverse them.
- Preserve shared-hosting compatibility with Blade-first progressive enhancement.
- Keep application code in English only while supporting Arabic and English UI.

## Milestone 24 Scope

### 1. Web Foundation

- Add the authenticated Blade shell and guest pages.
- Add Arabic and English localization switching.
- Add RTL support for Arabic.
- Add reusable Blade components:
  - app layout
  - sidebar
  - topbar
  - breadcrumbs
  - alert messages
  - form input
  - select input
  - textarea
  - submit button
  - data table
  - pagination
  - status badge
  - empty state
  - confirmation modal or confirmation pattern without heavy JS
- Add role-aware and tenant-aware navigation.
- Add role-based post-login dashboard resolution.

### 2. Authentication Web UI

- Add web login/logout while preserving API and Sanctum authentication.
- Reuse the existing `User` model and role system.
- Redirect users using this precedence:
  - platform owner -> `/owner/dashboard`
  - tenant staff -> `/admin/dashboard`
  - resident -> `/resident/dashboard`
- Add localized validation and error messages.

### 3. Platform Owner Web Admin

- Dashboard
- Tenants index/create/store/show/edit/update
- Tenant suspend/reactivate
- Tenant feature flag editing
- Tenant subscriptions view/update
- Subscription plans index/create/store/show/edit/update
- Audit logs index/show
- Tenant health/status page
- System health page backed by existing health checks

### 4. Tenant Admin

- Dashboard
- Buildings CRUD
- Apartments CRUD
- Residents CRUD
- Occupancy assignment
- Tenant users/staff CRUD
- Role assignment
- Tenant settings
- Tenant audit logs

Resident onboarding must be able to create or link:

- resident record
- optional user account
- resident role assignment by slug
- apartment occupancy pivot record

### 5. Accounting Admin UI

- Financial periods index/create/show/close/reopen
- Wallet transactions index/show
- Resident wallet summary
- Wallet deposit form
- Manual debit form
- Debit transactions index/show
- Expenses index/create/store/show/edit/update
- Expense allocation and splits
- Expense payment from wallet
- Payment reversal workflow
- Chart of accounts index/create/edit/show
- Journal entries index/create/show/post
- Trial balance report
- Wallet report
- Debit report
- Outstanding balances report

Reuse existing accounting services rather than duplicating business logic in controllers.

### 6. Maintenance Admin UI

- Maintenance tickets index/create/store/show/edit/update
- Ticket status changes
- Work orders index/create/store/show/edit/update
- Technician assignment
- SLA fields
- Maintenance report

### 7. Resident Web Portal

- Resident dashboard
- Wallet summary and history
- Debit summary and unpaid splits
- Payment history
- Maintenance ticket list/create/show
- Profile page
- Notifications page when database notifications exist

Resident users must only see their own tenant, resident, apartment, and financial data.

### 8. Reports and Export

Add CSV exports where practical for:

- tenants
- residents
- apartments
- wallet report
- debit report
- outstanding balances
- maintenance report
- audit logs

### 9. Authorization and Validation

- Add policies for all listed owner, tenant, accounting, and maintenance models.
- Add Form Requests for all create/update/action forms.
- Protect every web route with middleware and/or policy checks.

### 10. Testing

Add feature coverage for:

- web login/logout
- owner tenant CRUD
- owner subscription plan CRUD
- tenant dashboard access
- building CRUD tenant isolation
- apartment CRUD tenant isolation
- resident CRUD and onboarding
- wallet deposit
- manual debit
- expense creation/allocation/payment
- payment reversal
- financial period close/reopen
- maintenance ticket workflow
- work order workflow
- resident portal isolation
- report pages
- authorization failures

Run:

```bash
php artisan test
```

### 11. Documentation

Update project documentation to cover:

- web admin access
- first platform owner creation
- owner dashboard usage
- tenant onboarding
- tenant admin workflow
- resident onboarding
- accounting workflow
- maintenance workflow
- report workflow
- shared-hosting route/cache/asset notes

## Implementation Order

1. Inspect existing routes, controllers, services, models, requests, views, config, and tests.
2. Write this plan document.
3. Add Milestone 24 to `docs/TASKS.md`.
4. Implement web foundation and authentication.
5. Implement platform owner admin.
6. Implement tenant admin CRUD.
7. Implement accounting UI.
8. Implement maintenance UI.
9. Implement resident portal.
10. Add reports and CSV exports.
11. Add tests.
12. Run formatting and test suite.
13. Update remaining documentation and README.
