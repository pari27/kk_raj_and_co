---
title: Project rules (authoritative — approval workflow, stack, conventions)
glob: **/*
---

# Task Management App – Project Rules

These rules apply to all work on this project. Follow them for every module, every change and
every bug fix. This file is the authoritative source; if anything in the other files under
`.ai/rules/` conflicts with this one, this one wins.

## Approval rule (highest priority)

Do not change anything without approval. This includes code, files, database, configuration,
packages and Git.

Before any change, first send a change proposal and wait for a clear "yes":

1. **What and why:** what is planned and the reason.
2. **Files:** the full list of every file that will be created, changed or deleted, with a
   short note on what changes in each.
3. **Database:** any migration, new table or column, or change to existing data.
4. **Consequences:** what this change affects, including other modules, existing data,
   screens, routes, tests and anything that could break.
5. **Risks and rollback:** what could go wrong and how to undo the change.
6. **Better alternatives:** if there is a better or simpler way, suggest it here, with pros
   and cons, before implementing anything.

Rules for approval:

- Implement only what was approved. If something extra turns out to be needed while working,
  stop and send a new proposal.
- Approval for one change does not cover the next change.
- Running read-only commands (viewing files, `git status`, running tests) is allowed without
  approval.

## 1. Project context

- Mobile-responsive web application for a single CA firm.
- Roles: Super Admin, Admin (same access in Phase 1), Staff, Customer.
- Phase 1 modules: Login and roles, Dashboards, Services master, Documents master, Staff
  master, Employee designations master, Ticket statuses (fixed list), Customer management,
  Enquiries and tickets, Ticket assignment, Document upload and tracking, Status updates and
  payment, Customer portal, Email notifications, Reports.
- Reference documents: Project Brief for Approval, Scope of Work, UI design canvas.
- Not in Phase 1: invoices, SMS/WhatsApp, payment gateway, native mobile apps. Do not build
  these.

## 2. Tech stack

- PHP with Laravel (the version installed in this project), MySQL.
- Frontend: Blade templates with Bootstrap 5. Do not use Tailwind for app screens.
- Email: Laravel Mail over SMTP, sent through queues.
- File storage: Laravel Storage on the private disk. Uploaded documents must never be
  publicly accessible.
- Do not install a new package without asking first and explaining why.

## 3. Code structure

- Follow standard Laravel MVC.
- Keep controllers thin. Put business logic in Service classes under `app/Services`.
- Validate every form with a Form Request class under `app/Http/Requests`.
- Check permissions with middleware and Policies, never only by hiding buttons.
- Change the database only through migrations. Never edit tables by hand.
- Seed fixed data (ticket statuses, Super Admin account, sample designations) with seeders.
- Use named routes, grouped by role (for example `admin.*`, `staff.*`, `customer.*`).
- Use Blade layouts and components for shared parts (sidebar, top bar, status badges, form
  fields).

## 4. Naming conventions

- Tables: plural snake_case (`customers`, `enquiries`, `tickets`, `employee_designations`).
- Models: singular PascalCase (`Customer`, `Enquiry`, `Ticket`).
- Controllers: `<Model>Controller`.
- Routes and views: kebab-case or dot notation matching the module (`admin.customers.index`).
- Branches: `feature/<module-name>`, `fix/<short-description>`.

## 5. Business rules (always enforce)

- Customer phone: exactly 10 digits, starts with 6–9, unique. Strip `+91`, a leading `0` and
  spaces before saving or checking.
- Duplicate phone: block and offer to open the existing customer. Duplicate email: show a
  warning and allow saving.
- One enquiry can have many services; create one ticket per service, each with its own ticket
  number.
- Enquiry stores price, GST, discount and total. Only Admin can change a service price.
- Tickets created by Admin must be assigned by Admin. Tickets created by Staff are assigned to
  that staff member automatically. Admin can reassign.
- Ticket statuses are a fixed list. Task Completed and Final Payment Received are always the
  last two.
- Every status change needs a comment and is saved permanently in the status history.
- Task Completed needs a mandatory comment. Final Payment Received is Admin only and closes
  the ticket (read-only; Admin can reopen).
- Document uploads by Admin, Staff and Customer are versioned. Never overwrite or delete
  document history.
- Customers must log in to upload documents. First login and forgot password use a
  single-use email OTP that expires.
- Customers see only their own data. Staff see only their assigned tickets and customers.

## 6. Security

- Hash all passwords. Never store or log OTPs, passwords or tokens in plain text.
- Keep CSRF protection on all forms.
- Rate-limit login and OTP requests.
- Validate uploaded file type and size against the Documents master.
- Never commit `.env` or any credentials.
- Record key actions in the audit log.

## 7. Testing rule: one round of testing for every item

No module, feature or bug fix is complete until it has passed one full round of testing.

For every item:

1. **Automated tests.** Write feature tests for the item and run `php artisan test`. Cover:
   - the normal (happy) path,
   - validation errors (wrong or missing input),
   - permissions for each role (allowed and blocked),
   - the business rules from section 5 that apply to it.
2. **All tests must pass**, including tests from earlier modules, before moving on.
3. **Manual test round.** Test the screen in the browser on desktop and on a mobile-size
   screen:
   - forms submit and show the right messages,
   - each role sees only what it should,
   - emails are sent (check the mail log or a test inbox),
   - the layout matches the UI design.
4. **Report the result.** List what was tested, what passed and anything that failed or needs
   a decision.
5. If anything fails, fix it and repeat the test round for that item.

## 8. Working rules for Claude

- Work on one module at a time, in the order of the Scope of Work unless told otherwise.
- Before writing code, send the change proposal described in the Approval rule and wait for
  approval.
- Never change the database design, add a package, or touch anything outside the approved
  change.
- Do not change unrelated files, even for small clean-ups; suggest them in a proposal instead.
- After coding, run the tests and report the results. Never say a task is done if tests are
  failing or were not run.
- Keep explanations short and clear.

## 9. Git workflow

- Create a branch for each module: `feature/<module-name>`.
- Commit small, clear steps with messages like `Add customer duplicate phone check`.
- Merge into the main development branch only after the module passes its test round.
- Never commit `.env`, `vendor/`, `node_modules/` or uploaded files.

## 10. Definition of done

An item is done only when:

- it matches the Scope of Work and the UI design,
- all automated tests pass,
- the manual test round on desktop and mobile is complete,
- the test result has been reported,
- the code is committed on its feature branch.
