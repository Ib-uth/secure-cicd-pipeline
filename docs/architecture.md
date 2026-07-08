# Architecture — Secure CI/CD Pipeline

## Overview

This project demonstrates a DevSecOps pipeline that treats security as a build
constraint. A Laravel API application lives in `app/`; GitHub Actions runs six
sequential security gates before any deployment is allowed.

## Repository layout

```
01-cicd-pipeline/
├── app/                          # Laravel 13 API (Sanctum auth + API keys)
│   ├── app/
│   │   ├── Http/Controllers/Api/ # AuthController, ApiKeyController
│   │   ├── Http/Requests/        # Form request validation
│   │   ├── Http/Resources/       # API response transformers
│   │   └── Models/               # User, ApiKey
│   ├── database/migrations/      # users, api_keys, sanctum tokens
│   ├── routes/api.php            # /register, /login, /api-keys CRUD
│   ├── tests/Feature/            # AuthTest, ApiKeyTest (18 tests)
│   ├── Dockerfile                # php:8.2-fpm-alpine production image
│   └── .dockerignore
├── .github/workflows/
│   └── security-pipeline.yml     # 6-job pipeline
├── docs/
│   ├── architecture.md           # this file
│   ├── decisions.md              # security decision log
│   └── MANUAL_TASKS.md           # user-only steps + screenshot checklist
└── screenshots/                  # pipeline evidence captures
```

## Application architecture

```
Client
  │
  ├─ POST /api/register  ──► AuthController::register  ──► Sanctum token
  ├─ POST /api/login     ──► AuthController::login     ──► Sanctum token
  │
  └─ Bearer token ──► auth:sanctum middleware
        │
        ├─ GET  /api/user
        ├─ POST /api/logout
        └─ /api/api-keys  ──► ApiKeyController (CRUD)
              │
              └─ ApiKey model (hash-only storage)
```

### Authentication flow

1. User registers or logs in via JSON API.
2. Server issues a Sanctum personal access token (Bearer).
3. Token is sent on subsequent requests in the `Authorization` header.
4. Middleware `auth:sanctum` resolves the user before any protected route runs.

### API key flow

1. Authenticated user creates a named key with optional scopes (`read`, `write`, `delete`).
2. Server generates `sk_<prefix>_<random>`, returns plaintext once.
3. Only SHA-256 hash and prefix are stored in `api_keys` table.
4. Keys are scoped per user; cross-user access returns 404 (no information leak).

## Pipeline architecture

```
push / pull_request
        │
        ▼
   ┌─────────┐
   │  test   │  PHPUnit — 18 feature tests
   └────┬────┘
        │
   ┌────┴────────────────┐
   │                     │
   ▼                     ▼
┌──────┐          ┌──────────────┐
│ sast │          │ dependency-  │
│      │          │ scan         │
└──┬───┘          └──────┬───────┘
   │                     │
   │              ┌──────┴───────┐
   │              │ secret-scan  │
   │              │ (Gitleaks)   │
   │              └──────┬───────┘
   │                     │
   └──────────┬──────────┘
              ▼
        ┌───────────┐
        │image-scan │  Docker build + Trivy (CRITICAL/HIGH)
        └─────┬─────┘
              ▼
        ┌───────────┐
        │  deploy   │  only on main push, only if all gates pass
        └───────────┘
```

| Stage | Tool | Working directory | Blocks deploy |
|---|---|---|---|
| Unit tests | PHPUnit / `php artisan test` | `app/` | Yes |
| SAST | Semgrep (`p/php`, OWASP, SQLi) | repo root | Yes |
| Dependency scan | Composer Audit | `app/` | Yes |
| Secret detection | Gitleaks | repo root | Yes |
| Image scan | Trivy on Docker image | `app/` | Yes |
| Deploy | echo placeholder | — | Gated by `needs` |

## Data model

| Table | Purpose |
|---|---|
| `users` | name, email, hashed password |
| `personal_access_tokens` | Sanctum bearer tokens |
| `api_keys` | name, key_prefix, key_hash, scopes, revoked_at |

## Security controls in the application

- Password policy: minimum 8 chars, mixed case, numbers (Laravel `Password` rule)
- Rate limiting: 6 requests/minute on register and login
- Mass-assignment protection: secret key material set explicitly, never from input
- Ownership checks: API key routes return 404 for other users' resources
- JSON-only error responses on `/api/*` routes
