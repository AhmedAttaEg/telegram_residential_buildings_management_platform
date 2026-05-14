# Resident Guide

This guide documents the resident-facing flows currently exposed by the platform and mobile-ready APIs.

## Audience

Use this guide if you are a resident, apartment owner, or tenant who needs to view balances, understand charges, or follow support-related workflows.

## Access Requirements

Resident features require all of the following:

- valid authentication
- an active tenant
- the tenant `resident_app` feature enabled
- a user account with the `resident.access` permission
- a linked resident profile
- active occupancy access to the target apartment

If any of these requirements fails, the platform blocks access to resident apartment routes.

## Wallet System

The wallet represents resident-held funds available for tenant-scoped workflows.

### What Residents Can Currently Do

Residents can currently:

- view wallet summary
- view wallet transaction history

Current resident wallet endpoints:

- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/wallet/summary`
- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/wallet/history`

### Wallet Balance Rules

Wallet balance is calculated from the wallet ledger, not from cached apartment fields.

Balance behavior:

- credits increase the balance
- debits reduce the balance
- reversals create compensating entries rather than deleting history

### Wallet History

Wallet history returns paginated transaction records that include:

- transaction type
- direction
- amount and currency
- linked reference details when present
- reversal information
- timestamps

Use transaction history to review deposits, deductions, adjustments, payments, and reversals.

## Debit System

The debit view shows what the apartment still owes within the tenant accounting system.

### What Residents Can Currently Do

Residents can currently:

- view debit summary
- list unpaid splits

Current resident debit endpoints:

- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/debit/summary`
- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/debit/unpaid-splits`

### Debit Balance Rules

Debit balance is derived from:

- outstanding confirmed expense splits
- manual debit ledger entries
- debit payment transactions
- reversal adjustments

This means the amount due may change when:

- a new expense split is confirmed
- a payment is recorded
- a prior payment is reversed

### Unpaid Split Listing

Residents can filter unpaid splits by:

- `building_id`
- `financial_period_id`
- `per_page`

Each result includes:

- split amount and currency
- payment and confirmation state
- linked expense title and date
- linked financial period name

## Payments

The current resident-facing documentation scope is balance visibility and charge visibility. The existing resident API surface in this repository does not yet expose a self-service payment submission endpoint.

### Current Payment Behavior

At the accounting layer, expense split collection works by:

- deducting the resident wallet ledger
- recording a debit payment
- linking both records to an expense payment entry

From a resident support perspective, this means:

- wallet balance decreases when a split is collected
- debit balance decreases when the payment is applied
- history remains auditable if a payment is later reversed

For authentication, token refresh, and mobile request format, see `docs/MOBILE_API.md`.

## Maintenance Requests

Planned workflow only: resident self-service maintenance request flows are not implemented yet in the current resident API surface.

### Planned Resident Experience

When this workflow is implemented, the intended resident flow is:

1. Open a maintenance request for the apartment or unit issue.
2. Submit issue details and priority context.
3. Receive ticket status updates as staff triage the request.
4. Track resulting work orders until resolution.

### Current Platform State

The current codebase already includes maintenance domain models for:

- tickets
- work orders
- apartment-linked maintenance records
- resident-linked maintenance records

Until resident self-service routes are added, maintenance requests should be treated as a planned capability rather than a currently available resident feature.

## Common Access Outcomes

Residents may see access failures when:

- the tenant is suspended
- the tenant subscription is inactive
- the `resident_app` feature is disabled
- the user does not have resident permission
- the user is not linked to a resident profile
- the resident does not have active occupancy for the apartment

## Related References

- `docs/MOBILE_API.md`
- `docs/PRD.md`
- `docs/BLUEPRINT.md`
