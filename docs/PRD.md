# PRD.md

# Residential Buildings Management Platform
## Product Requirements Document (PRD)

Version: 1.0
Target Stack: Laravel + MySQL + REST API + Mobile Apps
Hosting Target: Hostinger Business Shared Plan (initial phase)
Development Workflow: Codex CLI-assisted development
Primary Language: Arabic + English UI
Codebase Language: English only

---

# 1. Executive Summary

The platform is a multi-tenant SaaS system designed to manage accounting, operations, maintenance, subscriptions, and residential community management workflows for multiple types of real-estate and residential-management clients.

The system evolves the accounting and workflow logic already proven inside the Telegram bot project into a scalable enterprise-grade web and mobile platform.

The platform serves:

- المطوّر العقاري
- اتحاد الشاغلين
- اتحاد ملاك
- شركة إدارة أملاك
- Community Management Companies
- Facility Management Companies
- برج مفرد / Compound / Multi-building communities
- Small residential unions
- Enterprise-grade property operators

The platform owner controls all client instances from a central administration layer.

---

# 2. Product Vision

Build the leading Arabic-first residential accounting and community management SaaS platform for the Middle East.

The platform should:

- Support simple and enterprise accounting modes.
- Support highly customizable client instances.
- Support full operational workflows.
- Support mobile and web interfaces.
- Support subscription lifecycle automation.
- Provide audit-safe accounting.
- Provide scalable multi-tenant architecture.
- Support future AI automation.

---

# 3. Core Objectives

## 3.1 Operational Objectives

- Manage residential buildings and communities.
- Manage apartments, units, owners, tenants.
- Manage maintenance and facilities.
- Manage expenses and allocations.
- Manage wallet/debit accounting.
- Manage subscriptions.
- Manage staff and permissions.
- Manage financial periods.
- Manage payment reversals.
- Manage audit logs.

## 3.2 Technical Objectives

- Multi-tenant SaaS architecture.
- Laravel-based modular backend.
- API-first architecture.
- Mobile-ready backend.
- Enterprise accounting support.
- Performance optimized for shared hosting initially.
- Cloud migration-ready.

---

# 4. Tenant Architecture

## 4.1 Architecture Type

Shared multi-tenant application.

Recommended:

- Single Laravel codebase.
- Shared database.
- tenant_id isolation model.

Each major table contains:

```text
tenant_id
```

---

# 5. Platform User Hierarchy

## 5.1 Platform Owner

The SaaS owner.

Capabilities:

- Create tenant instances.
- Suspend tenants.
- Customize features.
- Control subscriptions.
- Access analytics.
- Access audit logs.
- Manage support.
- Manage billing.
- Control modules.
- Control feature flags.

---

## 5.2 Tenant Owner

Client company or organization owner.

Capabilities:

- Manage organization.
- Manage buildings.
- Manage staff.
- Access accounting.
- Configure workflows.
- Access reports.

---

## 5.3 Roles

### Administrative Roles

- Owner
- Executive Manager
- Accountant
- Auditor
- Community Manager
- Property Manager
- Facility Manager
- Maintenance Supervisor
- Collector
- Customer Support
- Security Staff

### Resident Roles

- Apartment Owner
- Tenant
- Resident Viewer

---

# 6. Tenant Types

## 6.1 Small Residential Union

Simplified accounting.

Features:

- Wallet/debit.
- Expense allocations.
- Manual payments.
- Telegram integration.
- Simple reports.

---

## 6.2 Community Management Company

Advanced operational workflows.

Features:

- Multi-building.
- Maintenance workflows.
- Staff management.
- Full accounting.
- Vendor management.
- SLA tracking.
- Advanced reports.

---

## 6.3 Real Estate Developer

Features:

- Unit lifecycle.
- Installments.
- Ownership transfer.
- Sales accounting.
- Receivables.
- Contract management.

---

## 6.4 Facility Management Company

Features:

- Work orders.
- Technicians.
- Maintenance schedules.
- Inventory.
- Asset tracking.

---

# 7. Core Modules

# 7.1 Authentication & Identity

Features:

- Email/password.
- OTP login.
- Social login.
- 2FA.
- Mobile authentication.
- Device management.
- Session management.

---

# 7.2 Tenant Management

Features:

- Create tenant.
- Configure modules.
- Configure branding.
- Configure languages.
- Configure accounting mode.
- Configure subscriptions.
- Configure limits.

---

# 7.3 Buildings Module

Features:

- Buildings.
- Towers.
- Communities.
- Floors.
- Units.
- Parking.
- Facilities.
- Shared assets.

---

# 7.4 Residents Module

Features:

- Owners.
- Tenants.
- Occupancy.
- Unit ownership.
- Contact management.
- Emergency contacts.
- Resident documents.

---

# 7.5 Accounting Module

## Accounting Modes

### Simple Accounting

Telegram bot compatible logic.

Features:

- Wallet balance.
- Debit balance.
- Expense splits.
- Manual debit.
- Payment reversals.
- Financial periods.

### Enterprise Accounting

Features:

- Double-entry accounting.
- General ledger.
- Chart of accounts.
- Journal entries.
- Receivables.
- Payables.
- Vendors.
- Trial balance.
- Balance sheet.
- Profit & loss.
- Cash flow.
- Budgeting.
- Tax handling.

---

# 7.6 Expense Management

Features:

- Expense creation.
- Expense categories.
- Expense attachments.
- Allocation workflows.
- Approval workflows.
- Split confirmation.
- Payment tracking.
- Reversal workflows.

---

# 7.7 Wallet System

Features:

- Resident wallet.
- Credit/debit tracking.
- Deposits.
- Withdrawals.
- Refunds.
- Audit-safe ledger.

Source of truth:

```text
wallet_transactions
```

NOT apartment cached balances.

---

# 7.8 Debit System

Features:

- Manual debit.
- Expense debit.
- Outstanding balances.
- Aging reports.
- Installments.
- Penalties.

---

# 7.9 Payment System

## Manual Payments

- Cash.
- Bank transfer.
- Cheque.
- POS.

## Online Payments

Future integrations:

- Stripe.
- Paymob.
- Fawry.
- HyperPay.
- Apple Pay.
- Mada.
- STC Pay.

---

# 7.10 Maintenance Module

Features:

- Tickets.
- Work orders.
- Asset maintenance.
- Preventive maintenance.
- Technician assignments.
- SLA tracking.
- Vendor workflows.

---

# 7.11 Subscription & Billing Module

Features:

- Monthly subscriptions.
- Annual subscriptions.
- Trial periods.
- Auto suspension.
- Reminder notifications.
- Grace periods.
- Feature limitations.
- Billing history.
- Invoice generation.

Default behavior:

```text
Suspend tenant when subscription expires.
```

Configurable:

- Reminder-only mode.
- Grace period mode.
- Soft lock mode.

---

# 7.12 Notifications Module

Channels:

- Telegram.
- Email.
- SMS.
- Push notifications.
- In-app notifications.
- WhatsApp (future).

---

# 7.13 Reports Module

Reports:

- Wallet reports.
- Debit reports.
- Outstanding balances.
- Expense reports.
- Occupancy reports.
- Financial reports.
- Maintenance reports.
- Subscription reports.
- Executive dashboards.

---

# 7.14 Audit & Compliance

Features:

- Full audit logs.
- Action tracking.
- Reversal history.
- IP tracking.
- Device tracking.
- Immutable financial logs.

---

# 8. Accounting Rules

## 8.1 Source of Truth

Never rely on cached balances.

Accounting truth comes from:

- wallet_transactions
- debit_transactions
- expense_splits
- payment_ledgers

---

## 8.2 Reversals

Never delete payments.

Use reversal workflows:

- payment reversal transaction
- audit trail
- restore balances

---

## 8.3 Financial Periods

Features:

- Monthly periods.
- Quarterly periods.
- Annual periods.
- Period close.
- Reopen permissions.
- Locked accounting.

---

# 9. Mobile Applications

## Resident App

Features:

- View balances.
- Pay online.
- View expenses.
- Open tickets.
- Notifications.
- Documents.

## Staff App

Features:

- Work orders.
- Maintenance workflows.
- Collections.
- Expense management.

---

# 10. API Requirements

Architecture:

- REST API first.
- JWT/Sanctum.
- Mobile-ready.
- Third-party integrations.
- Webhook support.

---

# 11. Technical Stack

## Backend

- Laravel
- MySQL
- Redis (future)
- Queue workers
- Scheduler

## Frontend

- Blade (initial)
- Vue/React (future)
- Tailwind CSS

## Mobile

- Flutter preferred

---

# 12. Performance Strategy

Initial target:

- Hostinger Business Shared Hosting.

Optimization requirements:

- Query optimization.
- Proper indexes.
- Background jobs.
- Lazy loading avoidance.
- Cache strategy.
- Queue-based notifications.

Future migration:

- VPS
- Docker
- Kubernetes
- AWS/GCP

---

# 13. Security Requirements

Features:

- CSRF protection.
- XSS protection.
- Rate limiting.
- Tenant isolation.
- Permission-based access.
- Financial audit logs.
- Encrypted sensitive data.
- Activity monitoring.

---

# 14. Documentation Requirements

The platform must include:

## Platform Owner Documentation

- Tenant management.
- Subscription control.
- Financial administration.
- Support workflows.
- Billing workflows.

## Client Documentation

### Accountant Documentation

- Expense workflows.
- Payment workflows.
- Period closing.
- Reversals.

### Community Manager Documentation

- Building management.
- Resident workflows.
- Maintenance workflows.

### Resident Documentation

- Wallet.
- Payments.
- Maintenance requests.
- Notifications.

### Maintenance Documentation

- Work orders.
- Assets.
- Technician assignments.

---

# 15. AI Roadmap

Future features:

- AI accounting assistant.
- AI maintenance classification.
- AI report generation.
- AI anomaly detection.
- AI budgeting.
- AI collections assistant.

---

# 16. Development Roadmap

## Phase 1

- Authentication.
- Multi-tenancy.
- Buildings.
- Apartments.
- Wallet/debit accounting.
- Expense splits.
- Payment workflows.
- Telegram integration.

## Phase 2

- Subscription management.
- Resident portal.
- Maintenance module.
- Reports.

## Phase 3

- Enterprise accounting.
- Mobile apps.
- Online payments.
- Vendor integrations.

## Phase 4

- AI features.
- Automation.
- Predictive analytics.

---

# 17. Success Metrics

- Active tenants.
- Monthly recurring revenue.
- Payment collection rates.
- User retention.
- Support ticket reduction.
- Financial accuracy.
- Platform uptime.
- Mobile adoption.

---

# 18. Final Architecture Principles

- Modular.
- Audit-safe.
- Multi-tenant.
- API-first.
- Financially accurate.
- Mobile-ready.
- Enterprise-scalable.
- Arabic-first.
- SaaS-ready.

