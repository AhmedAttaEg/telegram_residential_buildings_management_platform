# Mobile API Reference

This document describes the mobile authentication flow and the current mobile-facing API patterns for app clients.

## Base URL

Versioned APIs use:

```text
/api/v1
```

## Authentication

Mobile authentication uses Sanctum bearer tokens.

Send the token in the `Authorization` header:

```http
Authorization: Bearer <token>
Accept: application/json
```

### Login

`POST /api/v1/mobile/auth/login`

Request body:

```json
{
  "email": "resident@example.com",
  "password": "password",
  "device_name": "iPhone 16",
  "device_platform": "ios",
  "app_version": "1.0.0",
  "push_token": "optional-push-token"
}
```

Success response:

```json
{
  "success": true,
  "message": "Authenticated successfully.",
  "data": {
    "token": "plain-text-token",
    "token_type": "Bearer",
    "expires_at": "2026-05-14T10:00:00Z",
    "user": {
      "id": 1,
      "tenant_id": 10,
      "resident_id": 4,
      "name": "Resident User",
      "email": "resident@example.com",
      "status": "active",
      "preferred_locale": "ar"
    },
    "tenant": {
      "id": 10,
      "name": "Alpha Towers",
      "slug": "alpha-towers",
      "status": "active"
    },
    "roles": ["resident"],
    "permissions": ["resident.access"],
    "device": {
      "token_id": 12,
      "device_name": "iPhone 16",
      "device_platform": "ios",
      "app_version": "1.0.0",
      "push_token": "optional-push-token",
      "last_used_ip": "127.0.0.1",
      "last_used_at": "2026-05-12T10:00:00Z"
    }
  }
}
```

### Refresh

`POST /api/v1/mobile/auth/refresh`

Requirements:

- The current bearer token must be valid.
- The endpoint rotates the token.
- The old token is revoked after the new one is issued.

Optional request body fields:

```json
{
  "device_name": "iPhone 16",
  "device_platform": "ios",
  "app_version": "1.0.1",
  "push_token": "updated-push-token"
}
```

Use the returned token for all subsequent requests. The previous token will no longer work.

### Logout

`POST /api/v1/mobile/auth/logout`

Revokes only the current bearer token.

### Logout All

`POST /api/v1/mobile/auth/logout-all`

Revokes all Sanctum tokens for the authenticated user across devices.

## Standard Errors

All API errors use the shared response envelope:

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

Common mobile auth cases:

- `422` invalid credentials or validation failures
- `401` missing, expired, revoked, or invalid bearer token
- `403` inactive user
- `429` auth throttling

## Pagination

List endpoints use the shared pagination format:

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": [],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42,
      "count": 15,
      "from": 1,
      "to": 15,
      "has_more_pages": true
    }
  }
}
```

Current resident endpoints that return paginated mobile-friendly data:

- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/wallet/history`
- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/debit/unpaid-splits`

## Related Resident APIs

Authenticated mobile clients can also use the resident portal APIs:

- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/wallet/summary`
- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/wallet/history`
- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/debit/summary`
- `GET /api/v1/t/{tenant:slug}/resident/apartments/{apartment}/debit/unpaid-splits`
