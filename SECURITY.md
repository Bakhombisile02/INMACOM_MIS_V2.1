# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.1.x   | ✅ Active support  |
| 2.0.x   | ⚠️ Critical fixes only |
| < 2.0   | ❌ No longer supported |

## Reporting a Vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Please report security vulnerabilities through one of the following private channels:

1. **GitHub Private Advisory** — open a [Security Advisory](https://github.com/Bakhombisile02/INMACOM_MIS_V2.1/security/advisories/new) on this repository.
2. **Email** — send details to `security@inmacom.co.za` with the subject line `[SECURITY] <brief description>`.

### Response SLA

| Milestone                          | Target            |
| ---------------------------------- | ----------------- |
| Acknowledgement of report          | Within 48 hours   |
| Triage and severity classification | Within 5 days     |
| Patch released (critical/high)     | Within 14 days    |
| Patch released (medium/low)        | Within 30 days    |

We will notify you when the issue is resolved and credit you in the release notes (unless you prefer to remain anonymous).

---

## Authentication Security

### Firebase Token Verification
All authentication is handled via Firebase ID tokens verified **server-side** in `FirebaseAuthController::authenticate()`. The `kreait/laravel-firebase` Admin SDK calls `verifyIdToken()` on every login request — the token is never trusted client-side alone.

### Rate Limiting
All Firebase and PIN endpoints use the `throttle:firebase-auth` middleware to prevent brute-force and credential-stuffing attacks:

```
POST /auth/firebase
POST /register/pin/verify
POST /register/pin/reserve
```

### Password Hashing
User passwords (where applicable) are hashed with **argon2id** (`HASH_DRIVER=argon2id` in `.env`), with `BCRYPT_ROUNDS=12` as the fallback.

### Session Encryption
Sessions are stored in the database (`SESSION_DRIVER=database`). Enable `SESSION_ENCRYPT=true` in production environments to encrypt session payloads at rest.

---

## Known Security-Sensitive Areas

### PIN-Gated Registration
New user registration requires a valid one-time PIN issued by an admin. PINs are single-use and expire. Admins manage them via the `/invites` endpoints. Ensure PINs are delivered only through trusted channels.

### Seeder Route (Remove Before Production)
The route `/run-seed-staging-982347102` in `routes/web.php` triggers a full database seed. **This route must be removed or protected before any production deployment.** It is not protected by authentication middleware.

### Firebase Service Account Credentials
The Firebase Admin SDK requires a service account JSON file. In production:
- Store the file content as a **Secret Manager secret** (Google Cloud) or equivalent.
- Never commit `storage/app/firebase-service-account.json` to version control — it is in `.gitignore`.
- The path is configured via the `FIREBASE_CREDENTIALS` environment variable.

### File Upload Validation
`DocumentUploadController` handles file uploads to the private document library. File type and size are validated server-side. Review allowed MIME types periodically and ensure uploaded files are stored outside the public web root.

### Environment Variables
Sensitive values (`APP_KEY`, `DB_PASSWORD`, Firebase keys, etc.) must **never** be committed. Use `.env.example` as a template without real values.

---

## Dependency Scanning

Keep dependencies up to date and scan for known vulnerabilities regularly:

```bash
# PHP dependencies
composer audit

# Node.js dependencies
npm audit

# Automated scanning
```

Enable [GitHub Dependabot](https://docs.github.com/en/code-security/dependabot) on this repository for automated pull requests when vulnerabilities are detected in `composer.lock` or `package-lock.json`.
