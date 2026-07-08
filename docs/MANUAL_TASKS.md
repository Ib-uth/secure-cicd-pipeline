# Manual Tasks — Secure CI/CD Pipeline

Steps that require your GitHub account, browser, or local credentials.
The automated build cannot perform these.

---

## 1. GitHub repository setup

- [ ] Ensure the repo is public: `github.com/ib-uth/secure-cicd-pipeline`
- [ ] Confirm the default branch is `main`
- [ ] Verify remote: `git remote -v` shows `git@github-personal:ib-uth/secure-cicd-pipeline.git`

---

## 2. GitHub Actions secrets

Go to **GitHub → secure-cicd-pipeline → Settings → Secrets and variables → Actions**

- [ ] Add secret `SEMGREP_APP_TOKEN`
  1. Sign up free at [semgrep.dev](https://semgrep.dev)
  2. Go to Settings → Tokens
  3. Create a token and paste it as the secret value

> `GITHUB_TOKEN` is provided automatically by Actions — no setup needed.

📸 **Screenshot:** `screenshots/02-github-secrets.png` — Secrets page with
`SEMGREP_APP_TOKEN` listed (blur the value).

---

## 3. Phase 3 — Push with vulnerabilities and capture failures

The repo currently contains intentional issues:

- `guzzlehttp/guzzle:6.5.5` in `app/composer.json`
- Fake AWS secret in `app/.env.example`

Push to trigger the pipeline:

```bash
git push origin main
```

Go to **GitHub → Actions** and watch the pipeline run. Expect failures on:

- **Unit Tests** or **Dependency Scan** — composer install fails on Guzzle pin mismatch
- **Secret Detection** — Gitleaks catches the fake AWS key in `.env.example`

📸 **Screenshots (critical):**

| File | What to capture |
|---|---|
| `screenshots/01-repo-structure.png` | VS Code / terminal showing repo layout before first push |
| `screenshots/03-pipeline-overview-failing.png` | Actions tab with red ✗ on failing jobs |
| `screenshots/04-gitleaks-failure.png` | Gitleaks job log showing detected secret with file path |
| `screenshots/05-dependency-failure.png` | Dependency scan log showing Guzzle issue |

---

## 4. Phase 4 — Fix vulnerabilities and capture green run

### Remove the fake secret

```bash
cd app
sed -i '' '/AWS_SECRET_ACCESS_KEY=wJalr/d' .env.example
```

### Update the vulnerable dependency

```bash
# Remove the intentional pin and require a patched version
composer remove guzzlehttp/guzzle --no-update
composer update guzzlehttp/guzzle --with-dependencies
```

Or edit `app/composer.json` manually: delete the `"guzzlehttp/guzzle": "6.5.5"` line,
then run `composer update guzzlehttp/guzzle`.

### Commit and push

```bash
git add app/composer.json app/composer.lock app/.env.example
git commit -m "fix: remove hardcoded secret, update vulnerable dependency"
git push origin main
```

📸 **Screenshots (critical):**

| File | What to capture |
|---|---|
| `screenshots/06-pipeline-overview-passing.png` | Actions tab — all jobs green ✓ |
| `screenshots/07-trivy-clean.png` | Trivy scan showing no CRITICAL/HIGH |
| `screenshots/08-deploy-job.png` | Deploy job log: "All security checks passed" |

---

## 5. Portfolio integration

- [ ] Add repo link to your portfolio site
- [ ] Embed `screenshots/06-pipeline-overview-passing.png` in README (after capture)
- [ ] Optionally convert `docs/decisions.md` insights into a blog post

---

## 6. Screenshot checklist summary

| # | Filename | Status |
|---|---|---|
| 1 | `01-repo-structure.png` | ☐ |
| 2 | `02-github-secrets.png` | ☐ |
| 3 | `03-pipeline-overview-failing.png` | ☐ |
| 4 | `04-gitleaks-failure.png` | ☐ |
| 5 | `05-dependency-failure.png` | ☐ |
| 6 | `06-pipeline-overview-passing.png` | ☐ |
| 7 | `07-trivy-clean.png` | ☐ |
| 8 | `08-deploy-job.png` | ☐ |

---

## Troubleshooting

### Pipeline fails on `composer install` everywhere

The Guzzle 6.5.5 pin is intentional. Complete Phase 4 to fix it.

### Semgrep job fails with auth error

Add `SEMGREP_APP_TOKEN` to GitHub Actions secrets (step 2).

### Trivy finds CRITICAL in base image

Alpine + PHP images occasionally have unpatchable OS CVEs. Review severity;
MEDIUM findings do not block deploy per our policy in `docs/decisions.md`.

### Tests pass locally but fail in CI

Ensure `composer.lock` is committed and consistent with `composer.json`.
Run `composer validate --strict` in `app/` before pushing.
