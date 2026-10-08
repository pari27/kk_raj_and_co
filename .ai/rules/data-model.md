---
title: Core data model
glob: app/Models/**,database/migrations/**,database/factories/**,database/seeders/**
---

# Core Data Model

Three core tables per the SOW: **customers**, **enquiries**, **tickets**. One enquiry holds
one ticket per service. Everything else is a master or a supporting/history table.

## Users vs Customers — two separate identities

Internal staff (SuperAdmin, Admin, Staff) and Customers are **not the same table and not the
same guard**. Mixing them risks privilege confusion (a customer session being treated as a
staff session, or vice versa).

- `users` — SuperAdmin, Admin, Staff. Standard Laravel auth guard (`web`).
- `customers` — portal accounts, separate guard (`customer`). Created automatically when a
  customer record is added by Admin/Staff; the customer sets their own password after
  first-login OTP.

## Masters

- `services`: name, description, default_price, gst_percent, estimated_time, active/inactive.
- `document_types` ("Documents master"): name, instructions, allowed_formats (subset of
  pdf/jpg/png), max_file_size_kb.
- `service_document_types` (pivot): service_id, document_type_id, is_mandatory.
- `employee_designations`: name, active/inactive. Only active designations populate the staff
  designation dropdown.
- `staff`: name, gender, mobile (10 digits), email, employee_designation_id, photo (nullable),
  active/inactive. Tied 1:1 to a `users` row (the login). Login credentials are emailed on
  creation — see [security.md](security.md) for how (never emailed in plaintext at rest).

## Ticket statuses — fixed enum, no master screen

Do **not** build a CRUD/master screen for statuses. This is a fixed, ordered list baked into
the app (e.g. a PHP backed enum), per the SOW:

1. Documents Pending
2. Documents Received
3. Under Verification
4. Additional Documents Required
5. Work In Progress
6. Submitted to Department
7. On Hold
8. Task Completed
9. Final Payment Received

## Core tables

- `customers`: name, phone (unique, 10 digits, normalized), email, status (active/inactive).
- `enquiries`: customer_id, price, gst, discount, total, created_by (user_id).
  - `price` = sum of its tickets' prices (derived, recompute server-side — never trust a
    client-submitted total; see [business-rules.md](business-rules.md)).
- `tickets`: enquiry_id, service_id, price, gst, ticket_number (unique, generated), 
  assigned_staff_id (nullable until assigned), current_status, created_by (user_id).

## Supporting / history tables

- `ticket_status_history`: ticket_id, from_status, to_status, comment (≤500 chars),
  changed_by (user_id, nullable), changed_at. Append-only — never update or delete rows.
- `ticket_documents`: ticket_id, document_type_id, version (int, incrementing per
  ticket+document_type), file_path (private disk, not public), original_filename,
  uploaded_by_type (`user`|`customer`), uploaded_by_id, channel, uploaded_at.
- `ticket_document_reviews`: ticket_document_id, status (`verified`|`rejected`), reason
  (nullable, required when rejected), reviewed_by (user_id), reviewed_at.
- `ticket_payments`: ticket_id, amount, mode, paid_on, reference, received_by (user_id),
  is_partial (bool, computed at insert time against ticket total), created_at.
  Recording a payment here with the full remaining balance is what triggers the
  "Final Payment Received" status transition — see [business-rules.md](business-rules.md).
- `otps`: owner_type (`user`|`customer`), owner_id, purpose (`login`|`forgot_password`),
  code_hash (never store the raw OTP), expires_at, consumed_at (nullable), attempts.
- `email_logs`: to_address, template_key, subject, related_type, related_id, status
  (`sent`|`failed`), error (nullable), sent_at.
- `email_templates`: key, subject, body (editable by Admin), updated_by.
- `audit_logs`: actor_type, actor_id, action, subject_type, subject_id, old_values (json),
  new_values (json), ip_address, created_at. Append-only.
- `firm_settings`: single-row (or key/value) table for name, logo, address, branding.

## Conventions

- Every master and every staff/customer record uses an `active`/`inactive` **status flag**,
  not soft deletes and not hard deletes. Nothing in this domain is ever hard-deleted except
  where explicitly stated (there is no such case in Phase 1).
- `SuperAdmin` is a role value on `users`, not a separate table. It must be impossible to
  delete or deactivate the SuperAdmin row — enforce this in the model/policy layer, not only
  in the UI (see [roles-permissions.md](roles-permissions.md)).
