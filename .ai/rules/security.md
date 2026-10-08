---
title: Security measures
glob: app/**,config/**,routes/**
---

# Security Measures

Mandatory for this project — a CA firm handles customer financial documents and payment
records. Apply all of these; do not treat any as optional polish.

## Authentication

- Two separate guards: `web` (SuperAdmin/Admin/Staff, `users` table) and `customer`
  (`customers` table). Never let a check for one accidentally pass for the other.
- Passwords hashed with Laravel's default (`bcrypt`/argon2 via `Hash::make`), never stored or
  logged in plaintext. Minimum policy: 8+ characters, not the user's own email/phone as the
  password (validate with a custom rule).
- Login throttling on every login form (`web` and `customer`) via Laravel's built-in
  `throttle` middleware — e.g. lock out after 5 failed attempts per email+IP for a cooldown
  window. Apply the same throttle to "forgot password" and OTP endpoints.
- Staff login credentials are emailed on creation: email a **one-time setup link**
  (signed, expiring URL) rather than a plaintext password in the email body. If a temporary
  password must be emailed instead, force a password change on first login.

## OTP (customer first login + forgot password)

- 6-digit numeric OTP, generated with a CSPRNG (`random_int`), never derived from anything
  guessable (no timestamp-based or sequential OTPs).
- Store only a hash of the OTP (`Hash::make`), never the raw code — verify with `Hash::check`.
- Expiry: short-lived (5–10 minutes). Single-use: mark `consumed_at` on success and reject any
  reuse of the same OTP.
- Attempt-limit the verify endpoint (e.g. 5 wrong attempts invalidates that OTP and requires a
  fresh one) and rate-limit the *request* endpoint (e.g. 1 OTP request per 60 seconds per
  account) to prevent SMS/email bombing and brute force.
- OTP delivery emails must not include any other sensitive account data.

## Authorization

- Every model that a role can act on gets a **Policy**; every controller action calls
  `$this->authorize(...)` (or `Gate`/policy equivalent) — role middleware alone is not enough
  (see [roles-permissions.md](roles-permissions.md)).
- Treat every ticket/document/payment/customer ID in a URL as attacker-controlled: always
  scope the query to the authenticated actor (`assigned_staff_id`, `customer_id`) *before*
  checking existence, so a guessed ID for someone else's record returns 403/404, never data
  (IDOR prevention). This applies especially to the customer portal, where every route must
  filter by the logged-in customer's own ID.
- SuperAdmin delete/deactivate protection is enforced in code (model guard clause or policy
  `before()` hook), not just hidden in the UI — reject the request even if it's sent directly.
- A closed ticket (Final Payment Received) is read-only at the policy layer: block
  update/upload/status-change authorization for every role except the Admin "reopen" action.

## Input validation

- All input goes through Form Request classes, never validated only in JavaScript.
- Phone: `regex:/^[6-9]\d{9}$/` after server-side normalization (strip `+91`, leading `0`,
  spaces) — normalize before validating and before the uniqueness check.
- Status-change comments: `max:500`.
- File uploads: validate both extension and MIME (`mimes:pdf,jpg,png` + Laravel's MIME
  sniffing, not just the client-reported extension) against that specific document type's
  configured allowed formats and max size (from the Documents master) — do not use a single
  global file-type rule for every upload.
- Never trust client-submitted computed values: price, GST, discount, and total are always
  recomputed server-side from trusted inputs (service default price, GST%, discount rule)
  before saving.
- Explicit `$fillable` (not `$guarded = []`) on every Eloquent model to prevent mass-assignment
  of fields like `role`, `status`, `assigned_staff_id`, or `current_status` via unexpected
  request keys.

## File storage

- Uploaded documents and deliverables are stored on a **private** disk
  (`storage/app/private/...` or a non-public S3 bucket), never under `public/`. They are only
  ever served through an authenticated, authorized download route — never a direct static URL.
- Store files under a generated name (e.g. ULID) rather than the user-supplied filename;
  keep the original filename only as metadata for display. This avoids path traversal and
  filename-collision/overwrite issues.
- Deliverable downloads use short-lived signed URLs (`URL::temporarySignedRoute`) so a leaked
  link can't be replayed indefinitely.

## Transport & session

- Force HTTPS in production (`URL::forceScheme('https')` / trusted-proxy config) and set the
  HSTS header.
- Session cookies: `secure`, `http_only`, `same_site=lax` (or `strict` if it doesn't break the
  OTP/email-link flows). Regenerate the session ID on every login (Laravel does this by
  default via `Auth::login` — don't bypass it).
- Add a security-headers middleware: `X-Content-Type-Options: nosniff`,
  `X-Frame-Options: DENY`, a `Content-Security-Policy`, `Referrer-Policy: same-origin`.

## Data protection

- CSRF protection stays on for every state-changing form (Laravel's default — never add routes
  to the CSRF-exempt list for this app).
- Blade's `{{ }}` escaping by default everywhere; `{!! !!}` is disallowed for anything derived
  from user input (comments, names, firm branding text) — this is the app's main XSS surface
  given free-text comments and editable email templates.
- All DB access through Eloquent/query builder bindings — no raw string-interpolated SQL, ever
  (SQL-injection prevention). If a raw expression is unavoidable, it must use parameter
  bindings.
- Payment reference numbers and any other sensitive-but-queryable fields use Laravel's
  `encrypted` Eloquent cast at rest.
- `.env` holds all credentials (DB, mail, app key) and is git-ignored; `APP_DEBUG=false` and a
  custom error view in production so stack traces never reach a browser.

## Auditing & operational

- `audit_logs` table (append-only, no update/delete from the app layer) captures: status
  changes, payment recording, document verify/reject, staff/customer create/deactivate,
  ticket reassignment, ticket reopen — actor, action, subject, before/after values, IP,
  timestamp. This is the SOW's required "audit log of key actions."
- `email_logs` records every outbound email (OTP, notifications) with delivery status, for
  traceability without re-exposing OTP codes in the log.
- Daily automated DB backup (SOW requirement) — e.g. `spatie/laravel-backup` scheduled via
  Laravel's task scheduler, stored off-server.
- Keep `laravel/framework` and all dependencies patched; run `composer audit` periodically for
  known CVEs in dependencies.

## Explicitly out of scope for now (flag, don't silently add)

- MFA/2FA beyond the OTP flows already specified.
- File antivirus/malware scanning on uploads — worth recommending to the client as a Phase 2
  add-on, but not built unless asked.
