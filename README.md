# Secure CI/CD Pipeline

A complete DevSecOps pipeline for a Laravel API application with Sanctum
authentication and API key management.

## What it does

Every push triggers: unit tests → SAST (Semgrep) → dependency scanning →
secret detection (Gitleaks) → Docker image scanning (Trivy) → deploy.

The build **fails** if any check finds a critical or high severity issue.
Nothing ships until everything passes.

## Application API

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/api/register` | — | Create account, receive Bearer token |
| POST | `/api/login` | — | Authenticate, receive Bearer token |
| GET | `/api/user` | Bearer | Current user profile |
| POST | `/api/logout` | Bearer | Revoke current token |
| GET | `/api/api-keys` | Bearer | List your API keys |
| POST | `/api/api-keys` | Bearer | Create key (plaintext shown once) |
| GET | `/api/api-keys/{id}` | Bearer | Show key metadata |
| PUT | `/api/api-keys/{id}` | Bearer | Update name/scopes |
| DELETE | `/api/api-keys/{id}` | Bearer | Revoke and delete key |

## Pipeline stages

| Stage | Tool | Blocks deploy |
|---|---|---|
| Unit tests | PHPUnit | Yes |
| SAST | Semgrep | Yes |
| Dependency scan | Composer Audit | Yes |
| Secret detection | Gitleaks | Yes |
| Image scan | Trivy | Yes |

## Key decisions

See [docs/decisions.md](docs/decisions.md)

## Architecture

See [docs/architecture.md](docs/architecture.md)

## Manual setup steps

Some steps require your GitHub account and cannot be automated. See
[docs/MANUAL_TASKS.md](docs/MANUAL_TASKS.md).

## Screenshots

Pipeline evidence captures go in `screenshots/`. See the manual tasks doc for
the full checklist.

## Local development

```bash
cd app
cp .env.example .env
php artisan key:generate
composer install
php artisan migrate
php artisan test
php artisan serve
```

## Intentional vulnerabilities (for demo)

This repo ships with deliberate issues so the pipeline can be demonstrated
failing and then passing:

1. `guzzlehttp/guzzle:6.5.5` pinned in `app/composer.json` (known CVEs)
2. Fake AWS secret in `app/.env.example` (Gitleaks detection)

Remove both before expecting a green pipeline run. Steps in
[docs/MANUAL_TASKS.md](docs/MANUAL_TASKS.md).
