---
title: Roles and permissions
glob: app/Http/Controllers/**,app/Policies/**,routes/**,app/Http/Middleware/**
---

# Roles & Permissions

Four roles: **SuperAdmin, Admin, Staff, Customer**. SuperAdmin and Admin share the same
permission set everywhere in this document â€” SuperAdmin is not "more powerful than Admin",
it is Admin plus one extra protection: it can never be deleted or deactivated, and no other
role can delete or deactivate it. Enforce that specific rule at the model/policy layer (e.g.
a guard clause in the `User` model or a dedicated policy check), not only by hiding the button
in the UI â€” a direct request to the delete/deactivate endpoint must also be rejected.

## Permission matrix

| Action | SuperAdmin | Admin | Staff | Customer |
|---|---|---|---|---|
| Manage services / documents / designations masters | ✅ | ✅ | ❌ | ❌ |
| Manage staff | ✅ | ✅ | ❌ | ❌ |
| Delete/deactivate SuperAdmin | ❌ (no one can) | ❌ | ❌ | ❌ |
| Add/edit/search customers | ✅ | ✅ | ✅ | ❌ |
| Create enquiry / tickets | ✅ | ✅ | ✅ | ❌ |
| Change a service price on a ticket | ✅ | ✅ | ❌ | ❌ |
| Assign a ticket to staff or an Admin/Super Admin account | ✅ | ✅ | ✅ (staff accounts only; assigned to self by default) | ❌ |
| Reassign any ticket | ✅ | ✅ | ❌ | ❌ |
| Upload documents on a ticket | ✅ | ✅ | ✅ | ✅ (own tickets only, portal only) |
| Verify/reject a document | ✅ | ✅ | ✅ | ❌ |
| Change ticket status / add timeline comment | ✅ | ✅ | ✅ (own/assigned tickets) | ❌ |
| Mark Task Completed | ✅ | ✅ | ✅ (own/assigned tickets) | ❌ |
| Record Final Payment Received | ✅ | ✅ | ❌ | ❌ |
| Reopen a closed ticket | ✅ | ✅ | ❌ | ❌ |
| View all tickets/reports | ✅ | ✅ | ❌ (own only) | ❌ |
| View/download own tickets, documents, payments | â€” | â€” | â€” | ✅ (own only) |
| Edit firm profile / email templates | ✅ | ✅ | ❌ | ❌ |
| View audit log | ✅ | ✅ | ❌ | ❌ |

## Implementation notes

- Express this as Laravel **Policies** per model (`TicketPolicy`, `CustomerPolicy`,
  `StaffPolicy`, â€¦), not just route middleware. Middleware/role gates decide which *screens*
  a role can reach; policies decide whether *this* role can act on *this specific record*
  (e.g. "Staff can update this ticket only if `assigned_staff_id === auth()->id()`").
- A route-level `role:admin` middleware check alone is not sufficient â€” always pair it with a
  policy/authorization check inside the controller action, since ticket/document IDs are
  guessable and must not be reachable across staff/customers by ID alone (IDOR â€” see
  [security.md](security.md)).
- Staff never gets a "manage masters" or "all tickets" screen at all â€” this is a route/menu
  level restriction, not just a hidden button.
- Customer never authenticates against the `web` guard; use a dedicated `customer` guard so a
  customer session can never satisfy an `auth:web` / Admin-only check by accident.
