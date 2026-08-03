# 18. Assets: self service, reservations and depreciation

| | |
| --- | --- |
| **Identifier** | `modules/assets/18-self-service` |
| **Status** | Specified |
| **Module** | Assets |
| **Level** | 3, advanced parity, no timetable |
| **Source** | Sections 7.1.3 and 7.1.8 of the monolithic specification |
| **Depends on** | `14-catalogue-and-inventory`, `17-lifecycle-operations`, `09-managers`, `12-playbooks` |
| **Depended on by** | `19-playbook-integration` |

## 1. Context / Overview

Level 3 is the point where the module stops being an inventory kept by IT and
becomes something the rest of the company touches.

Four features, and they divide cleanly into two halves.

**Things employees do.** Requesting equipment, and accepting the terms attached to
what they are given. These change who uses the module.

**Things IT does ahead of time.** Reserving equipment for somebody who has not
arrived yet, and tracking what everything is worth. These change when the module
is used.

The reservation feature is the one that matters most for the product as a whole,
because it is what makes the `upcoming` status in `08-employee-lifecycle-status`
useful. A laptop reserved the day somebody is hired, rather than found the
morning they arrive, is the difference the playbook layer is supposed to make.

## 2. User Stories & Requirements

### Stories

**As an employee**, I ask for a second monitor without knowing who to email.

**As a manager**, I approve or refuse what my reports ask for.

**As an IT administrator**, I see approved requests and fulfil them from stock.

**As an IT administrator**, I reserve a specific laptop for somebody starting in
three weeks, and nobody else can be given it in the meantime.

**As an employee receiving equipment**, I am shown the terms and I accept them,
and what I accepted is kept as it was worded that day.

**As a finance minded administrator**, I can see what the equipment we own is
worth today rather than what we paid for it.

**As an IT administrator**, I run an inventory confirmation campaign asking
everybody to confirm what they hold, and I chase the people who have not replied.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | An employee may request equipment, choosing an asset model marked requestable. |
| FR-02 | A request has a status: pending, approved, refused, fulfilled, cancelled. |
| FR-03 | A request is routed to the primary manager of the requester for approval. |
| FR-04 | An approved request is fulfilled by somebody with `asset.checkout`, which performs a normal checkout. |
| FR-05 | A reservation blocks a specific asset for a period, for a specific employee. |
| FR-06 | A reserved asset cannot be checked out to anybody else during the reservation. |
| FR-07 | A reservation is converted into an assignment when the asset is handed over. |
| FR-08 | A reservation that passes its end date without being converted is released and an event is published. |
| FR-09 | When a category requires acceptance, checkout creates an acceptance request. |
| FR-10 | An acceptance stores a snapshot of the terms as worded at that moment. Terms edited later never change a recorded acceptance. |
| FR-11 | An acceptance records who accepted, when, and from where. |
| FR-12 | An asset may be depreciated using a method attached to its model. |
| FR-13 | The current value of an asset is derived from its purchase cost, its purchase date and its method. |
| FR-14 | An inventory confirmation campaign asks a set of employees to confirm what they hold, tracks who has answered, and chases the rest. |

## 3. Technical Specifications & Boundaries

### Data model

```
AssetRequest
- id, company_id
- requested_by_employee_id
- asset_model_id           what they asked for
- asset_id                 nullable, set when fulfilled
- reason
- status                   pending | approved | refused | fulfilled | cancelled
- decided_by_user_id, decided_at, decision_notes
- fulfilled_by_user_id, fulfilled_at

AssetReservation
- id, asset_id
- employee_id              who it is being held for
- reserved_by_user_id
- starts_at, ends_at
- status                   held | converted | released | expired
- notes

AssetAcceptance
- id, asset_id
- assignment_id            the assignment it belongs to
- employee_id
- eula_snapshot            the terms as worded at that moment
- status                   pending | accepted | declined
- responded_at
- ip_address
- signature_path           nullable

DepreciationMethod
- id, company_id
- name                     "Straight line, 36 months"
- type                     straight_line | declining_balance
- duration_months
- residual_percentage

InventoryCampaign
- id, company_id
- name
- starts_at, ends_at
- status

InventoryCampaignResponse
- id, inventory_campaign_id
- employee_id
- status                   pending | confirmed | discrepancy_reported
- responded_at
- notes
```

### Requests and the approval chain

A request goes to the primary manager of the requester, resolved through
`09-managers`. That is why this spec depends on it.

Two edge cases resolve the same way: a requester with no manager, and a requester
whose manager has no user account. Both route to the holders of the role with
`asset.manage`. A request that cannot be approved by anybody is worse than one
approved by IT.

Approval and fulfilment are separate acts by separate people. A manager saying
yes does not hand out equipment; somebody with `asset.checkout` still performs a
normal checkout, with every validation from `14-catalogue-and-inventory` intact.
That is the same principle as `12-playbooks`: an approval grants no permission.

### Reservations

A reservation blocks a specific asset, not a model. Reserving "a laptop" rather
than "this laptop" would mean maintaining a count of unreserved assets per model,
which is a different and more complicated feature.

Checkout in `14-catalogue-and-inventory` gains one validation: an asset with a
held reservation for somebody else cannot be checked out. Checking it out to the
person it is reserved for converts the reservation.

A reservation past its end date is released by a scheduled check rather than
lingering, so that an arrival that never happened does not tie up a machine
forever.

### Acceptance snapshots

The terms are snapshotted onto the acceptance row, not referenced.

The source document is explicit about this and it is worth restating why:
recording a reference to the terms means that editing the terms retroactively
changes what everybody in the past agreed to. An acceptance is a statement about
a moment. It is stored as one.

### Depreciation

Derived, never stored as a column that has to be recalculated.

```
straight line       cost - (cost - residual) * elapsed_months / duration_months
declining balance   applied per period against the remaining value
```

The value of an asset is a function of three stored facts and today's date. A
`current_value` column would be wrong on every day nobody recalculated it.

This is the one feature in the module that touches money, and it stays
deliberately shallow: a value for reporting, not accounting. Cost centres,
capitalisation and asset registers are out of scope everywhere.

### Inventory campaigns

A campaign asks a set of employees to confirm the equipment assigned to them.
Each response is confirmed, or reports a discrepancy.

The chasing is a playbook, not a feature of this spec. A campaign publishes
events and a template reacts to them, which is the pattern
`19-playbook-integration` describes. Building a second reminder mechanism inside
this module would duplicate what the playbook layer exists to do.

### Events published

| Event | When |
| --- | --- |
| `asset_request.created` | An employee asks for something. |
| `asset_request.approved` / `asset_request.refused` | A decision is made. |
| `asset_request.fulfilled` | Equipment is handed over against a request. |
| `asset.reserved` | A reservation is created. |
| `asset.reservation_expired` | A reservation passes its end date unconverted. |
| `asset.acceptance_requested` | An acceptance is created. |
| `asset.accepted` / `asset.declined` | Somebody responds. |
| `inventory_campaign.started` | A campaign opens. |
| `inventory_campaign.response_missing` | Somebody has not answered by a deadline. |

### Out of scope

- **Multi step approval.** One approver. No chain, no threshold above which
  somebody else must also approve.
- **Budgets and spending limits per team.** A finance candidate.
- **Legally binding electronic signatures.** The acceptance records a click, an
  address and a time. The `signature_path` column reserves space for a drawn
  signature and nothing certifies it.
- **Accounting integration.** Depreciation produces a number for a screen, not a
  journal entry.
- **Requesting accessories and consumables.** Requests cover serialised assets
  only in this version.

## 4. Acceptance Criteria

- [ ] AC-01. An employee can request a requestable asset model and cannot request
      one that is not.
- [ ] AC-02. A request is routed to the primary manager of the requester.
- [ ] AC-03. A requester with no manager routes to the holders of `asset.manage`.
- [ ] AC-04. Approving a request does not assign anything; fulfilment is a
      separate act by somebody with `asset.checkout`.
- [ ] AC-05. A refused request records who refused it and why.
- [ ] AC-06. A reservation prevents checkout to anybody but the person it is held
      for.
- [ ] AC-07. Checking out to the person it is held for converts the reservation.
- [ ] AC-08. A reservation past its end date is released and publishes
      `asset.reservation_expired`.
- [ ] AC-09. Checking out an asset in a category requiring acceptance creates a
      pending acceptance.
- [ ] AC-10. The terms are snapshotted, and editing the category terms afterwards
      leaves recorded acceptances unchanged.
- [ ] AC-11. An acceptance records the responder, the time and the address.
- [ ] AC-12. The current value of an asset is computed from its cost, its date
      and its method, and no column stores it.
- [ ] AC-13. An asset with no depreciation method reports no current value rather
      than reporting its purchase cost.
- [ ] AC-14. A campaign creates one response row per employee holding equipment.
- [ ] AC-15. An unanswered response publishes `inventory_campaign.response_missing`
      after the deadline.

## 5. Implementation status

Nothing in this spec exists. It is level 3, the furthest from the committed
scope, and it depends on more of the product than any other module spec:
`09-managers` for approval routing, `12-playbooks` for campaign chasing, and both
level 1 and level 2 of the module itself.

It is listed at all because two of its features (reservations and acceptance) are
what `19-playbook-integration` uses to make onboarding work properly, which means
level 3 is not purely a parity exercise.
