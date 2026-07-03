# Secure CI/CD Pipeline

A complete DevSecOps pipeline for a Laravel application.

## What it does

Every push triggers: unit tests → SAST (Semgrep) → dependency scanning → secret detection (Gitleaks) → Docker image scanning (Trivy) → deploy.

The build **fails** if any check finds a critical or high severity issue. Nothing ships until everything passes.

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

## Documentation

- [Architecture](docs/architecture.md)
- [Decision Log](docs/decisions.md)
