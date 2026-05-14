# Accountant Guide

This guide documents the accounting workflows currently implemented in the platform.

## Audience

Use this guide if you manage expenses, split payments, reversals, or accounting periods for a tenant.

## Core Accounting Rules

The platform is ledger-driven. Accounting truth does not come from cached balances on apartments or other summary fields.

Current sources of truth:

- `wallet_transactions`
- `debit_transactions`
- outstanding confirmed `expense_splits`
- `expense_payments`

Operational rules:

- never delete financial history
- use reversals instead of destructive edits
- keep all accounting-critical operations inside database transactions

## Financial Periods

Financial periods organize accounting activity by time window and tenant.

Current period fields support:

- period name
- period type
- start and end dates
- status
- lock timestamp
- locking user

### Working Rule

Accounting operations may proceed only while the related financial period is writable.

A period is treated as not writable when:

- `status` is `locked`, or
- `locked_at` has a value

When a period is locked, wallet operations, debit operations, and payment reversals fail rather than changing historical ledgers.

## Expense Workflows

The implemented expense workflow is:

1. Create the expense header for the tenant, building, and financial period.
2. Allocate expense splits to apartments.
3. Confirm splits before collecting payment.
4. Collect split payments from resident wallet balance.
5. Use reversals when a collected payment must be undone.

### Expense Structure

The current data model supports:

- expense header details such as title, date, amount, and approval state
- split records per apartment and period
- payment records linked to both wallet and debit ledgers

### Split Preconditions

An expense split must satisfy all of the following before payment:

- it is confirmed
- it is not reversed
- it is not already paid
- it does not already have an unreversed payment record

## Payment Workflows

Expense split payment is a coordinated accounting workflow, not a single field update.

When a split is paid:

- the wallet ledger records a debit transaction
- the debit ledger records a payment transaction
- an `expense_payments` record links the operation together
- the split is marked as paid

### Payment Preconditions

Before collecting payment, the system enforces:

- actor and tenant consistency
- writable financial period
- sufficient wallet balance
- payable split state

### Balance Interpretation

Wallet balance:

- calculated from wallet ledger credits minus debits

Debit balance:

- calculated from outstanding confirmed expense splits
- plus manual debit ledger entries
- minus debit payment transactions
- adjusted by reversal records where applicable

## Reversals

Reversal is the only supported way to undo an expense payment.

When a payment is reversed:

- the original wallet transaction is marked reversed
- a compensating wallet reversal transaction is created
- the original debit transaction is marked reversed
- a compensating debit reversal transaction is created when a debit payment exists
- the original payment stores reversal actor, timestamp, and reason
- a new reversal payment record is created and linked to the original payment
- the split is reopened by setting `is_paid` back to `false`
- an audit log entry is written to the `audit` channel

### Reversal Constraints

- a payment cannot be reversed twice
- reversal requires a writable financial period
- reversal does not delete the original payment or its ledgers

## Manual Debit Considerations

The debit ledger also supports manual debit and debit-payment entries outside the expense split flow. These transactions still respect tenant matching and period writability rules.

## Review Checklist

When reviewing tenant accounting operations, verify:

- every transaction belongs to the correct tenant and period
- split payment status matches the linked ledgers
- reversals include an explicit reason and actor
- locked periods remain unchanged after close

## Related References

- `docs/BLUEPRINT.md`
- `docs/PRD.md`
