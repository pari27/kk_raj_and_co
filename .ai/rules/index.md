# Project Rules Index

Source of truth: `Task Management App – Scope of Work` (Sep 28, 2026), Phase 1, single CA firm.

These rules are committed, project-specific decisions. Read every file whose glob covers the
path(s) you're about to touch, before writing or editing code.

| File | Globs | Covers |
|---|---|---|
| [project-rules.md](project-rules.md) | `**/*` | **Authoritative.** Approval workflow (mandatory change-proposal before any change), tech stack, code structure, naming conventions, testing rule, Git workflow, definition of done. Wins over any conflict below. |
| [data-model.md](data-model.md) | `app/Models/**`, `database/migrations/**`, `database/factories/**`, `database/seeders/**` | Entities, fields, fixed enums, relationships |
| [business-rules.md](business-rules.md) | `app/Http/Controllers/**`, `app/Services/**`, `app/Actions/**`, `app/Rules/**` | Workflow logic: ticket lifecycle, pricing, assignment, documents, payment |
| [roles-permissions.md](roles-permissions.md) | `app/Http/Controllers/**`, `app/Policies/**`, `routes/**`, `app/Http/Middleware/**` | Who can do what: SuperAdmin, Admin, Staff, Customer |
| [security.md](security.md) | `app/**`, `config/**`, `routes/**` | Authn/authz, OTP, file uploads, data protection, auditing |

Run `grep -rin 'keyword' .ai/rules` in addition to the glob table above — some constraints
(e.g. "SuperAdmin cannot be deleted") apply across many unrelated paths.

## Known open item

[project-rules.md](project-rules.md) §5 says duplicate phone should "block and offer to open
the existing customer" (a UX detail). [business-rules.md](business-rules.md) currently only
says "blocked". Not changed pending confirmation — see chat.

## Status

Rules and security measures are recorded. **No application code (migrations, models,
controllers, views) has been written yet, and none will be, without an approved change
proposal per project-rules.md §Approval rule.**
