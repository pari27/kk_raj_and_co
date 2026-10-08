---
title: Business / workflow rules
glob: app/Http/Controllers/**,app/Services/**,app/Actions/**,app/Rules/**
---

# Business Rules

These follow the SOW's "proposed option" defaults where the SOW flags a point still open for
client confirmation. If the client later changes one at approval, update this file first,
then the code.

## Customers

- Phone is the uniqueness key: exactly 10 digits, first digit 6–9 (`^[6-9]\d{9}$`).
  Duplicate phone is **blocked** (validation error, not a warning).
- Before validating/storing, strip a leading `+91`, a leading `0`, and all spaces from the
  submitted phone — do this server-side in a form request / cast, not only in JS.
- Duplicate email is blocked on the dedicated Add/Edit Client forms with inline availability feedback. The inline new-client flow from Add Enquiry retains its existing non-blocking behavior.

## Enquiries and tickets

- One enquiry can include one or more services; the system creates exactly one ticket per
  service, each with its own ticket number.
- `enquiry.price` = sum of its tickets' `price`. `enquiry.total` is derived from
  price + GST − discount. **Recompute this server-side on every save** — never persist a
  total sent from the client.
- Only **Admin** (and SuperAdmin, who has Admin's access) may change a service's price on an
  enquiry/ticket line. Staff use the service's default price as-is.

## Assignment

- A ticket created by Admin starts unassigned; Admin can assign it to an active Staff, Admin,
  or SuperAdmin account.
- A ticket created by Staff is auto-assigned to that Staff member (the creator) � no
  additional assignment step. Staff can assign tickets only to active Staff accounts.
- Admin (and SuperAdmin) can reassign any ticket at any time to an active Staff, Admin, or
  SuperAdmin account; reassignment is a status-history / audit-logged event and triggers the
  "ticket reassigned" notification to the new assignee (see SOW module 14).
## Documents

- Admin and Staff can upload documents directly on a ticket. Customers can only upload after
  authenticating in the customer portal — **there is no unauthenticated upload path**, by
  explicit SOW assumption.
- A re-upload never overwrites a prior file: it is stored as a new version of the same
  ticket+document_type pair, and the full history (who uploaded, who verified/rejected, via
  which channel, with what reason, and when) is retained permanently.
- Verifying or rejecting a document requires a reviewer (Admin/Staff) and, for rejection, a
  reason.

## Status updates

- Every status change requires a comment (max 500 characters) and is appended to the ticket's
  permanent timeline (`ticket_status_history`) — the timeline is never edited or truncated.
- Moving a ticket to **Task Completed** additionally requires a mandatory comment *and* a
  deliverable file upload in the same action — do not allow the status change without both.
- Only Admin (and SuperAdmin) can record **Final Payment Received**. It requires amount,
  mode, date, and reference.
  - If the recorded amount is less than the ticket's outstanding total, show a
    partial-payment warning; whether a partial payment is still allowed to close the ticket
    follows the client's confirmed answer — until confirmed, treat a partial payment as
    **not** sufficient to close the ticket (require the full outstanding amount before the
    status can move to Final Payment Received).
  - On full final payment, the ticket **closes and becomes read-only**: no further status
    changes, document uploads, or edits are accepted from any role except the specific
    "reopen" action.
  - Only Admin (and SuperAdmin) can **reopen** a closed ticket. Reopening is audit-logged and
    should itself go through `ticket_status_history` (e.g. back to the prior open status).

## Customer portal

- The customer account is provisioned automatically the moment a customer record is created
  by Admin/Staff — the customer never self-registers.
- First login and forgot-password both go through email OTP; only after OTP verification does
  the customer set/reset their password.
- A customer's queries must always be scoped to their own `customer_id` — see
  [security.md](security.md) for the IDOR-prevention rule this implies.

## Reports

- Admin (and SuperAdmin) see all reports; Staff see only their own tickets/work in the same
  report screens (row-level filter by `assigned_staff_id`, not a separate report set).
