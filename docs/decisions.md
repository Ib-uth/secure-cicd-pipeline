# Security Decision Log

## Why Semgrep over PHPStan alone

PHPStan catches type errors. Semgrep catches security patterns: SQL injection,
XSS, hardcoded credentials, insecure deserialization. Both run in the pipeline.

## Why the build fails on CRITICAL and HIGH only (not MEDIUM)

Medium vulnerabilities in OS packages frequently have no patch available and
would block all deployments. CRITICAL and HIGH have patches; blocking on these
is actionable. Medium findings are reported but don't block.

## Why secret scanning runs before image scanning

No point building and scanning a Docker image that contains a leaked secret.
Fast fail early.

## Why the deploy job has `needs: [image-scan]`

This enforces that deployment is impossible unless every upstream security
check has passed. It is not a convention — it is an architectural constraint.

## Why Guzzle 6.5.5 is pinned intentionally

The pipeline includes a deliberate `guzzlehttp/guzzle:6.5.5` pin in
`composer.json` to demonstrate dependency scanning. Composer 2.x blocks
installation of packages with known security advisories, and the lock file
mismatch causes `composer install` to fail until the pin is removed and
dependencies are updated to `^7.8`.

## Why API keys store only a hash

Plaintext API keys are returned once at creation and never persisted. Only a
SHA-256 hash and a short prefix are stored, so a database leak does not expose
usable credentials.

## Why Sanctum for authentication

Laravel Sanctum provides token-based API authentication without the overhead
of OAuth2 server infrastructure. Personal access tokens cover user sessions;
named API keys cover service-to-service access with scoped permissions.
