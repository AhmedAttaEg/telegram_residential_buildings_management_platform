# BLUEPRINT.md

# Residential Buildings Management Platform
## System Architecture & Technical Blueprint

Version: 1.0

---

# 1. System Philosophy

The platform is designed as:

- Multi-tenant SaaS.
- Modular Laravel application.
- Accounting-first architecture.
- Event/audit-driven system.
- API-first ecosystem.
- Mobile-ready.
- Enterprise-scalable.

Core principle:

```text
Transactions are source of truth.
```

Never rely on cached balances for accounting correctness.

---

# 2. High-Level Architecture

## Layers

### Presentation Layer

- Web Admin Panel
- Resident Portal
- Mobile APIs
- Telegram Integration
- Future Mobile Apps

### Application Layer

- Laravel Services
- Domain Logic
- Workflow Engines
- Accounting Engine
- Notification Engine

### Data Layer

- MySQL
- Queue Jobs
- Cache
- Logs
- Audit Records

---

# 3. Multi-Tenancy Blueprint

## Shared Database Multi-Tenant

All tenant data isolated by:

```text
tenant_id
```

All tenant-owned tables must contain:

```text
tenant_id
```

Examples:

- buildings
- apartments
- expenses
- wallet_transactions
- users
- subscriptions

---

# 4. Recommended Laravel Structure

```text
app/
 ├── Actions/
 ├── Console/
 ├── DTOs/
 ├── Enums/
 ├── Events/
 ├── Exceptions/
 ├── Http/
 ├── Jobs/
 ├── Listeners/
 ├── Models/
 ├── Notifications/
 ├── Observers/
 ├── Policies/
 ├── Repositories/
 ├── Services/
 ├── Support/
 ├── Traits/
 └── ValueObjects/
```

---

# 5. Core Domain Modules

## Tenant Domain

Entities:

- Tenant
- TenantSubscription
- TenantFeature
- TenantBranding
- TenantSettings

---

## Buildings Domain

Entities:

- Building
- Tower
- Floor
- Apartment
- Facility
- ParkingSpot

---

## Resident Domain

Entities:

- Resident
- Owner
- TenantOccupancy
- ResidentDocument

---

## Accounting Domain

Entities:

- Expense
- ExpenseSplit
- ExpensePayment
- WalletTransaction
- DebitTransaction
- ManualDebitPayment
- FinancialPeriod
- JournalEntry
- LedgerAccount

---

## Maintenance Domain

Entities:

- Ticket
- WorkOrder
- MaintenanceAsset
- Technician
- Vendor

---

# 6. Accounting Blueprint

## Source of Truth

### Wallet

Calculated from:

```php
wallet_transactions.sum(amount)
```

### Debit

Calculated from:

```php
unpaid confirmed expense splits
+
manual debit transactions
```

---

# 7. Ledger Philosophy

## Never Delete Financial Records

Allowed:

- reversal
- cancellation
- compensating transactions

Forbidden:

- deleting payments
- editing historical ledgers

---

# 8. Payment Reversal Architecture

## Reversal Requirements

Every reversal must:

- create reversal wallet transaction
- create reversal debit transaction
- mark payment reversed
- preserve audit history
- preserve original transaction

---

# 9. Expense Allocation Workflow

## Workflow

1. Create expense.
2. Allocate splits.
3. Confirm split.
4. Residents can pay.
5. Allow reversals.
6. Close financial period.

---

# 10. Financial Period Blueprint

## Rules

- Period close locks accounting.
- Closed periods are immutable.
- Reopen requires elevated permission.
- Historical balances derived from ledgers.

---

# 11. Database Principles

## Required Columns

All tables:

```text
id
created_at
updated_at
```

Tenant tables:

```text
tenant_id
```

Auditable tables:

```text
created_by
updated_by
telegram_user_id
```

---

# 12. Important Tables

## tenants

Purpose:

- SaaS client instances.

---

## buildings

Purpose:

- Physical buildings.

---

## apartments

Purpose:

- Units.

Important:

```text
Do not store accounting truth here.
```

Optional cache fields only.

---

## wallet_transactions

Purpose:

- Wallet ledger.

---

## debit_transactions

Purpose:

- Manual debit ledger.

---

## expenses

Purpose:

- Expense headers.

---

## expense_splits

Purpose:

- Unit allocations.

---

## expense_payments

Purpose:

- Split payment history.

---

# 13. Indexing Strategy

Required indexes:

```text
tenant_id
apartment_id
building_id
is_paid
is_reversed
financial_period_id
```

Composite indexes:

```text
(apartment_id, is_paid)
(apartment_id, is_reversed)
(tenant_id, building_id)
```

---

# 14. Service Layer Blueprint

## Example Services

```text
ExpensePaymentService
WalletService
DebitService
FinancialPeriodService
SubscriptionService
MaintenanceService
```

Rules:

- Controllers remain thin.
- Business logic belongs in services.
- Models contain relationships/accessors only.

---

# 15. Event-Driven Design

## Important Events

- ExpenseCreated
- ExpenseSplitConfirmed
- ExpensePaid
- PaymentReversed
- PeriodClosed
- SubscriptionExpired

---

# 16. Queue Architecture

Use queues for:

- notifications
- emails
- telegram messages
- PDF generation
- report exports
- scheduled accounting jobs

---

# 17. API Blueprint

## Standards

- RESTful.
- JSON API.
- Versioned endpoints.
- Token authentication.

Example:

```text
/api/v1/
```

---

# 18. Frontend Blueprint

## Admin Panel

Initial:

- Blade + Tailwind.

Future:

- Vue.js or React.

---

# 19. Mobile Blueprint

Recommended:

```text
Flutter
```

Applications:

- Resident app.
- Staff app.
- Maintenance app.

---

# 20. Notification Architecture

Channels:

- Telegram
- Email
- SMS
- Push Notifications
- WhatsApp (future)

---

# 21. Subscription Architecture

## Subscription States

- active
- grace_period
- suspended
- expired
- cancelled

---

# 22. Feature Flags System

Each tenant can enable/disable:

- enterprise accounting
- maintenance module
- online payments
- resident app
- AI features

---

# 23. Security Blueprint

## Security Layers

- Role permissions.
- Tenant isolation.
- Rate limiting.
- Financial audit logs.
- IP/device tracking.
- 2FA.

---

# 24. Performance Blueprint

## Shared Hosting Optimization

Requirements:

- query optimization
- eager loading
- indexes
- lightweight queues
- cache strategy

Avoid:

- N+1 queries
- large synchronous jobs
- excessive joins

---

# 25. Caching Strategy

Cache:

- tenant settings
- permissions
- feature flags
- dashboard summaries

Never cache accounting truth permanently.

---

# 26. Audit Logging Blueprint

Track:

- payments
- reversals
- expense edits
- subscription changes
- login activity
- permission changes

---

# 27. AI Readiness Blueprint

Future AI modules:

- accounting assistant
- anomaly detection
- predictive maintenance
- collections assistant
- automated reports

---

# 28. Documentation Blueprint

Documentation types:

## Platform Owner Docs

- tenant creation
- billing
- subscriptions
- support
- analytics

## Accountant Docs

- expenses
- periods
- reversals
- reports

## Resident Docs

- wallet
- payments
- maintenance

## Maintenance Docs

- work orders
- assets
- technicians

---

# 29. Deployment Blueprint

## Initial Hosting

Hostinger Business Shared Plan.

Requirements:

- optimized Laravel config
- cron jobs
- queues
- backups

---

# 30. Future Infrastructure

Future migration path:

- VPS
- Docker
- Kubernetes
- AWS/GCP
- Horizontal scaling

---

# 31. Recommended Development Standards

## Naming Rules

Codebase language:

```text
English only.
```

UI language:

```text
Arabic + English.
```

---

## Recommended Standards

- PSR standards.
- Repository pattern.
- Service pattern.
- Feature tests.
- API tests.
- Financial integrity tests.

---

# 32. Testing Blueprint

Required test layers:

## Unit Tests

- services
- calculations
- accounting rules

## Feature Tests

- workflows
- APIs
- permissions
- subscriptions

## Regression Tests

- accounting integrity
- reversal integrity
- financial period rules

---

# 33. Final System Principles

The platform must always be:

- audit-safe
- financially correct
- modular
- scalable
- mobile-ready
- API-first
- tenant-isolated
- enterprise-ready
- Arabic-first

