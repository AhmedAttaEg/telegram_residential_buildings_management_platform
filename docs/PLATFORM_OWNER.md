# Platform Owner Guide

This guide documents the platform owner workflows currently implemented in the platform administration layer.

## Audience

Use this guide if you manage tenant lifecycle, subscriptions, feature availability, and suspension decisions across the SaaS platform.

## Tenant Management

Platform owner APIs live under `/api/v1/owner` and require a user with the `platform_owner` role. Tenant-bound users cannot use owner administration routes even if they were assigned that role.

Current tenant management capabilities:

- List tenants with pagination.
- Filter by `status`, `subscription_status`, and `subscription_plan`.
- Search by tenant `name` or `slug`.
- Create tenants.
- View a single tenant.
- Update tenant settings and branding.
- Delete tenants.

### Tenant Creation Defaults

When a tenant is created through the owner API:

- `brand_name` defaults to the tenant name when omitted.
- `status` defaults to `active`.
- `subscription_status` defaults to `trial`.
- `feature_flags` are merged with the platform default feature set so omitted flags still receive explicit values.

### Recommended Review Checklist

For each tenant, verify:

- `name` and `slug` are correct.
- subscription plan and status reflect the intended lifecycle state.
- feature flags match the tenant contract.
- branding values are complete if a custom identity is required.

## Subscriptions

The platform stores subscription lifecycle data in dedicated subscription records and mirrors the current lifecycle state onto the tenant record for fast enforcement.

Implemented subscription states:

- `trial`
- `active`
- `grace`
- `suspended`
- `expired`
- `cancelled`

### Plan Attachment and Lifecycle

Subscription plans define billing cycle, pricing, feature limits, and optional trial days. Attaching a new plan creates a new lifecycle record and expires the previously current lifecycle record for that tenant.

Current lifecycle behavior:

- trial subscriptions set `trial_ends_at` when the plan has trial days.
- active subscriptions set renewal and end dates based on the billing cycle.
- grace mode keeps the tenant active until `grace_ends_at`.
- suspension records `suspended_at` and blocks tenant access.
- reminder dispatches record `reminder_sent_at`.

### Reminder Operations

Queued reminder notifications are sent to tenant owners with:

- expiration reminders
- grace-period reminders
- suspension reminders

The scheduler-facing command is:

```bash
php artisan subscriptions:send-reminders
```

Use the operations runbook for queue and scheduler deployment details.

## Feature Flags

Feature flags are stored on the tenant and are used to enable or disable tenant-scoped modules.

Current documented flags in the codebase include:

- `maintenance`
- `resident_app`
- `online_payments`
- `enterprise_accounting`
- `ai_features`

### Operational Notes

- Feature flags are merged with platform defaults during tenant create and update flows.
- Middleware enforces feature availability on tenant routes.
- Disabling a feature blocks access to the matching tenant module without changing unrelated tenant data.

Use feature flags to align tenant capabilities with the subscribed plan and contract scope.

## Suspension Workflows

Tenant access enforcement happens in tenant middleware. A tenant can lose access because it is suspended directly or because its subscription lifecycle is no longer active.

### Supported Actions

Owner status updates currently support these actions:

- `activate`
- `grace`
- `suspend`
- `remind`

### Grace Workflow

Use grace mode when payment is overdue but temporary access should remain available.

Current behavior:

- tenant `status` stays `active`
- `subscription_status` becomes `grace`
- `grace_ends_at` is set explicitly or defaults to seven days ahead
- `suspension_reason` may store the business reason

Once the grace window passes, tenant middleware blocks access with an inactive subscription response.

### Suspension Workflow

Use suspension when access must be stopped immediately.

Current behavior:

- tenant `status` becomes `suspended`
- `subscription_status` becomes `suspended`
- `suspended_at` is recorded
- `suspension_reason` may be recorded

Suspended tenants cannot use protected tenant routes until reactivated.

### Reactivation Workflow

Activation clears the suspension markers and restores tenant `status` to `active`. If the mirrored tenant subscription state was `suspended`, activation changes it back to `active`.

## Enforcement Outcomes

Current tenant access rules produce these operational outcomes:

- suspended tenants receive `Tenant is suspended.`
- expired or lapsed grace tenants receive `Tenant subscription is inactive.`
- disabled tenant features return a feature-disabled response on protected routes

## Related References

- `docs/PRD.md`
- `docs/BLUEPRINT.md`
- `docs/OPERATIONS.md`
