# 08. Employee lifecycle status

| | |
| --- | --- |
| **Identifier** | `core/08-employee-lifecycle-status` |
| **Status** | Specified |
| **Source** | Section 2.7 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy`, `03-employees`, `04-permissions-and-roles` |
| **Depended on by** | `11-occurrences`, `12-playbooks`, `19-playbook-integration`, the directory, every headcount figure |

## 1. Context / Overview

The status of an employee is explicit. It is not deduced from whether they have a
user account, or from whether their start date is in the past.

This is not a modelling preference, it is a prerequisite. Playbooks trigger on
transitions of status. With no explicit state there is nothing that can reliably
emit `employee.arrived` or `employee.departed`, and the entire playbook layer
loses its main source of triggers.

Five statuses are provided by the system to every company, and cannot be modified
or deleted:

| Status | Meaning |
| --- | --- |
| `candidate` | Not hired yet. The bridge towards a future recruiting module. |
| `upcoming` | Hired, before their first day. |
| `active` | Working. |
| `on_leave` | On leave: parental, long term illness, and so on. |
| `departed` | Gone, with their history kept. |

They are fixed so that the default playbook templates (onboarding, offboarding)
and the system behaviours (counting active people, keeping leavers out of the
directory) work from the moment a company is created, with no configuration.

A company may add statuses of its own on top, in the same way it adds teams or
job titles. There is an accepted trade off: an added status is not semantically
attached to any of the five system ones. The default templates do not know about
it until the company configures its own triggers for it.

## 2. User Stories & Requirements

### Stories

**As a People administrator**, I record somebody as hired before their first day,
and the onboarding playbook starts on its own.

**As a People administrator**, I mark somebody as departed, and they leave the
directory and stop being counted, without their record or their history
disappearing.

**As a People administrator**, I put somebody on leave and bring them back, and
the return triggers whatever the company wants to happen on a return.

**As an administrator**, I add a status my company uses, such as a probation
period or a sabbatical, and I accept that I have to say myself what should happen
when somebody enters it.

**As anybody**, the directory shows me the people who work here, not the people
who used to.

**As anybody**, I can read the history of somebody's status changes and see when
each one happened.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | Five system statuses exist for every company: candidate, upcoming, active, on leave, departed. |
| FR-02 | System statuses cannot be renamed, deleted or reassigned by a company. |
| FR-03 | A company may create additional statuses of its own. |
| FR-04 | A custom status carries no semantic link to any system status. |
| FR-05 | An employee has exactly one status at a time. |
| FR-06 | Status is recorded in an append only history table, never updated in place. |
| FR-07 | Every transition closes the current row and opens a new one, in one transaction. |
| FR-08 | The `status_id` column on the employee mirrors the active row, and is updated in the same transaction. |
| FR-09 | Every transition publishes `employee.status_changed`, carrying the previous and the new status. |
| FR-10 | Transitions between system statuses additionally publish a named event: `employee.arrived`, `employee.departed`, `employee.left_on_leave`, `employee.returned_from_leave`, `employee.hired`. |
| FR-11 | Headcount figures count employees whose status is active or on leave. |
| FR-12 | The directory excludes candidates and departed employees by default. |
| FR-13 | Deleting a custom status is refused while anybody holds it. |
| FR-14 | Changing the status of an employee requires `employee.update` covering that employee. Managing the list of statuses requires `company.manage`. |

## 3. Technical Specifications & Boundaries

### Data model

```
EmployeeStatus
- id
- company_id               null for a system status, set for a company one
- key                      candidate | upcoming | active | on_leave | departed, null for custom
- name
- is_system                boolean
- created_at, updated_at

EmployeeStatusHistory
- id
- employee_id
- status_id                a system status or a company one
- started_at
- ended_at                 nullable, null means this is the current status
```

### The append only history pattern

Same pattern as `05-locations`, where the full reasoning is written.

The denormalised `status_id` on the employee matters more here than in the other
five specs, because it is read on every directory query, every filter and every
headcount. It is written in the same transaction as the history row, never
separately.

### Which transitions publish which event

`employee.status_changed` is always published, whatever the transition, and
carries both statuses in its payload. It is the general purpose hook a company
configures its own triggers against.

On top of it, five transitions between system statuses publish a named event,
because the default templates should not have to inspect a payload to know what
happened.

| Transition | Named event |
| --- | --- |
| anything to `upcoming` | `employee.hired` |
| `upcoming` to `active` | `employee.arrived` |
| `active` to `on_leave` | `employee.left_on_leave` |
| `on_leave` to `active` | `employee.returned_from_leave` |
| anything to `departed` | `employee.departed` |

A transition involving a custom status publishes `employee.status_changed` alone.
That is the accepted trade off stated in the overview, and the company is
responsible for configuring what it wants to happen.

Note that `employee.departure_scheduled`, which the asset module reacts to (see
`19-playbook-integration`), is not a status transition. It is published
when a departure date is set on an employee who is still active, which is the
moment offboarding preparation should start. It is published from `03-employees`,
not from here.

### No state machine

Any status may follow any other. There is no table of permitted transitions and
no validation refusing an unusual one.

This is deliberate. Real companies produce sequences that a state machine written
in advance would reject: somebody who leaves and comes back, somebody who goes
straight from candidate to active because they started the same day, somebody
marked departed by mistake. A refused transition in those cases costs more than
it saves.

### Custom statuses

Created and deleted like teams or job titles, by somebody with `company.manage`.
Deleting one is refused while anybody holds it, since history rows would be left
pointing at nothing.

A custom status is not attached to a system one. Attaching it, for instance
saying that "sabbatical" behaves like "on leave", would mean asking the company a
question it cannot reliably answer, and quietly firing playbooks they did not
configure. The source document accepts this trade off explicitly and this spec
keeps it.

### Out of scope

- **Leave balances, requests and approvals.** That is PTO, in
  `backlog/20-pillar-operate`. The `on_leave` status says somebody is away. It
  does not track how much leave they have.
- **Notice periods and departure workflows.** Playbooks, in `12-playbooks`.
- **Rehire linking.** Somebody who leaves and returns is the same employee record
  with new history rows. There is no separate rehire concept.
- **Candidate management.** The `candidate` status reserves the space for the
  future recruiting module. Nothing in the first version produces candidates.

## 4. Acceptance Criteria

- [ ] AC-01. Every company has the five system statuses, available with no
      configuration.
- [ ] AC-02. A system status cannot be renamed or deleted.
- [ ] AC-03. A company can add a status of its own and delete it while nobody
      holds it.
- [ ] AC-04. Deleting a custom status that somebody holds is refused.
- [ ] AC-05. Setting the status of an employee creates a history row with a start
      date and no end date.
- [ ] AC-06. Every transition closes the previous row and opens a new one, and
      both survive.
- [ ] AC-07. An employee never has two active status rows.
- [ ] AC-08. The `status_id` on the employee matches the active row after every
      transition.
- [ ] AC-09. Every transition publishes `employee.status_changed` carrying both
      statuses.
- [ ] AC-10. Moving from upcoming to active publishes `employee.arrived`; moving
      to departed publishes `employee.departed`; the other three named
      transitions publish their event.
- [ ] AC-11. A transition involving a custom status publishes
      `employee.status_changed` and no named event.
- [ ] AC-12. Any status may follow any other, including departed back to active.
- [ ] AC-13. The directory excludes candidates and departed employees by default.
- [ ] AC-14. Headcount counts active and on leave employees only.
- [ ] AC-15. A user without `employee.update` covering an employee cannot change
      their status.

## 5. Implementation status

Nothing in this spec exists. There is no `employee_statuses` table, no
`employee_status_history` table and no `status_id` on the employee.

The employee table has `hired_at` and `departed_at` date columns, which is the
implicit deduction this spec exists to replace. They stay, because a date is
useful information, but they stop being how the state of somebody is determined.
`isEmployed()` on the `Employee` model currently derives employment from those
dates and becomes a read of `status_id`.

Three things are blocked on this spec.

- `12-playbooks` has almost no triggers without it.
- `19-playbook-integration` reacts to `employee.upcoming` and
  `employee.departure_scheduled`.
- The directory cannot correctly exclude leavers.

### Suggested build order

1. The two tables, the five system statuses seeded for every company, `status_id`
   on the employee, and the transition action. This makes the state explicit.
2. `isEmployed()` and the directory read the status instead of the dates.
3. Custom statuses and their administration screen.
4. The events, once `11-occurrences` exists.
