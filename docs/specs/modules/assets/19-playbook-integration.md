# 19. Assets: playbook integration

| | |
| --- | --- |
| **Identifier** | `modules/assets/19-playbook-integration` |
| **Status** | Specified |
| **Module** | Assets |
| **Level** | Spans levels 1 to 3 |
| **Source** | Section 7.1.7 of the monolithic specification |
| **Depends on** | `08-employee-lifecycle-status`, `11-domain-events`, `12-playbooks`, `14-catalogue-and-inventory` |
| **Depended on by** | Nothing |

## 1. Context / Overview

This is what distinguishes the module from a copy of Snipe-IT.

An inventory tool records what the company owns and who has it. This module does
that and is wired into the employee life cycle, so that equipment is prepared
before somebody arrives and recovered when they leave, without anybody
remembering to start the process.

Nothing in this spec is a new table. It is the set of event types the module
publishes, the set of playbook step types it contributes, and the default
templates that use them. It is the seam between `modules/assets/` and
`core/12-playbooks`, and it is specified separately because that seam is the
reason the module exists in this product rather than being bought elsewhere.

## 2. User Stories & Requirements

### Stories

**As a People administrator**, when I record somebody as hired, a laptop is
reserved for them, the licences they will need are lined up, and somebody is
given the task of shipping it, without me starting any of that.

**As a new joiner**, the equipment is waiting for me on my first day, and I accept
the terms once rather than being chased for a signature three weeks later.

**As a People administrator**, when I set a departure date, a list of everything
that person holds is drawn up and the tasks to recover it are created.

**As an IT administrator**, people who have not returned equipment are chased
automatically rather than by me remembering.

**As an IT administrator**, equipment that comes back goes into stock after
inspection, rather than sitting in a drawer marked as still assigned.

**As an IT administrator**, I am told when a warranty is about to lapse, when
something is due for audit, and when stock is running low, without checking.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | The module declares its event types, which join the catalogue in `11-domain-events`. |
| FR-02 | The module declares playbook step types, so a playbook step can act on the inventory. |
| FR-03 | An onboarding playbook triggered by `employee.hired` can reserve equipment, line up licences, and create human tasks. |
| FR-04 | An offboarding playbook triggered by `employee.departure_scheduled` can list what somebody holds, revoke their licence seats, and create recovery tasks. |
| FR-05 | Every step type has a manual equivalent, so a playbook works with no automation. |
| FR-06 | Overdue returns are chased by a playbook rather than by a mechanism inside the module. |
| FR-07 | Date driven events (warranty, end of life, audits) are published by a scheduled check. |
| FR-08 | Every step type of the module is unavailable while the module is disabled, and pending steps of that type in running playbooks are cancelled. |

## 3. Technical Specifications & Boundaries

### The two flows

**On `employee.hired`,** meaning somebody has moved to `upcoming` in
`08-employee-lifecycle-status`:

```
reserve a laptop                          AssetReservation, 18
line up the licence seats they will need  16
prepare the shipment                      a human task, 12
request acceptance on arrival             AssetAcceptance, 18
```

**On `employee.departure_scheduled`,** meaning a departure date has been set on
somebody still active:

```
list everything assigned to them          the active AssetAssignment rows, 14
revoke their licence seats                16
create the return tasks                   human tasks, 12
chase whoever has not responded           a playbook reminder, 12
put the equipment back in stock           after inspection, a checkin, 14
```

The second flow is the one companies get wrong, and the reason is always the
same: nobody draws up the list until after the person has gone. Triggering on the
departure being scheduled rather than on the departure happening is the whole
point.

`employee.departure_scheduled` is deliberately not a status transition. Somebody
serving notice is still active. See `08-employee-lifecycle-status`, where the
event is described and where it is published from.

### Step types declared by the module

Per `13-module-system`, a module declares the playbook step types it contributes.

| Step type | Level |
| --- | --- |
| `assets.assign_model` | 1. Check out an available asset of a given model to the subject. |
| `assets.recover_all` | 1. Create a return task for every asset the subject holds. |
| `assets.reserve_model` | 3. Reserve an asset of a given model for the subject. |
| `assets.request_acceptance` | 3. Ask the subject to accept the terms for what they hold. |
| `assets.revoke_licences` | 2. Revoke every licence seat held by the subject. |
| `assets.list_assigned` | 1. Produce the list of what the subject holds, as a task for somebody. |

Every one of them has a manual equivalent. A company with no automation at all
writes a human task saying "give them a laptop" and the playbook works. That is
founding principle 6 from `constitution.md`, and it is why `assets.assign_model`
is a convenience rather than a prerequisite.

A step that cannot complete, because no asset of that model is available, fails
loudly and is surfaced as blocked. It does not silently skip. An onboarding that
quietly did not assign a laptop is worse than one that says it could not.

### The full event catalogue of the module

Declared here, joining `11-domain-events` when the module is enabled.

| Event | Published by |
| --- | --- |
| `asset.checked_out`, `asset.checked_in` | `14-catalogue-and-inventory` |
| `asset.return_overdue` | `14-catalogue-and-inventory`, scheduled |
| `asset.reported_lost` | `14-catalogue-and-inventory` |
| `asset.warranty_expiring`, `asset.warranty_expired` | scheduled |
| `asset.end_of_life_approaching`, `asset.end_of_life_reached` | scheduled |
| `asset.audit_due`, `asset.audited`, `asset.not_found` | `17-lifecycle-operations` |
| `asset.maintenance_started`, `asset.maintenance_completed` | `17-lifecycle-operations` |
| `accessory.stock_low`, `consumable.stock_low`, `component.stock_low` | `15-quantitative-inventory` |
| `licence.seats_low`, `licence.expiring`, `licence.expired` | `16-software-licences` |
| `licence.seat_assigned`, `licence.seat_revoked` | `16-software-licences` |
| `asset_request.*`, `asset.reserved`, `asset.accepted` and the rest | `18-self-service` |

### The scheduled check

Six of these events are date driven rather than caused by somebody acting:
warranty expiry and its warning, end of life and its warning, audits falling due,
and overdue returns.

One scheduled job publishes all of them, once per day, and each is published once
per crossing rather than every day the condition holds. An asset whose warranty
lapsed four months ago must not publish `asset.warranty_expired` a hundred and
twenty times.

That means recording which date driven events have already been published per
asset. It is the least interesting part of this spec and the part most likely to
produce a mailbox full of duplicates if it is skipped.

### Chasing is a playbook, not a feature

`asset.return_overdue` publishes an event. What happens next is a playbook a
company configures: a reminder to the person, then to their manager, then a task
for IT.

Building the escalation inside the module would duplicate `12-playbooks` and
would not be configurable. The same reasoning applies to inventory campaign
chasing in `18-self-service`.

### Out of scope

- **Default playbook templates using these steps.** The five default templates
  are specified in `12-playbooks` and are written against core steps only, since
  a default template cannot depend on a module a company may have turned off.
  A company enabling Assets adds these steps to its own templates.
- **Automatic provisioning at vendors.** Revoking a licence seat here does not
  revoke it at Figma.
- **Shipping and logistics.** "Prepare the shipment" is a human task. No carrier
  integration, no tracking number.

## 4. Acceptance Criteria

- [ ] AC-01. The event types of the module appear in the catalogue while it is
      enabled and disappear while it is not.
- [ ] AC-02. The step types of the module are offered in the playbook editor only
      while it is enabled.
- [ ] AC-03. Disabling the module cancels pending steps of its types in running
      playbooks.
- [ ] AC-04. A trigger on `employee.hired` starts a template whose steps reserve
      equipment and create the shipping task.
- [ ] AC-05. A trigger on `employee.departure_scheduled` starts a template that
      lists what the subject holds.
- [ ] AC-06. `assets.recover_all` creates one task per active assignment.
- [ ] AC-07. `assets.revoke_licences` closes every seat held by the subject.
- [ ] AC-08. A step that cannot complete is surfaced as blocked and does not
      silently skip.
- [ ] AC-09. Each date driven event is published once per crossing, not once per
      day the condition holds.
- [ ] AC-10. An overdue return publishes `asset.return_overdue` and the module
      itself sends no reminder.
- [ ] AC-11. Every flow in this spec can be carried out with human task steps
      alone, with no module step type used.

## 5. Implementation status

Nothing in this spec exists, and it is the last piece of the module to build
rather than the first. It needs `11-domain-events`, `12-playbooks`,
`08-employee-lifecycle-status` and at least level 1 of the module.

It is specified now because it is the reason for building the module at all. A
level 1 inventory with no playbook integration is a worse Snipe-IT. The value
appears at this seam, and knowing that should shape what level 1 records: the
expected return date, the current location, the category acceptance flag and the
requestable flag all exist so that this spec has something to work with.
