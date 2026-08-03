# 09. Managers

| | |
| --- | --- |
| **Identifier** | `core/09-managers` |
| **Status** | Specified |
| **Source** | Section 2.8 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy`, `03-employees`, `04-permissions-and-roles` |
| **Depended on by** | `04-permissions-and-roles` (the `direct_reports` scope and the Manager role), `12-playbooks`, `18-self-service`, `backlog/22-pillar-grow`, the organisation chart |

## 1. Context / Overview

The reporting relationship is between two employees, it is many to many, and it
is historised. Same pattern as team membership in `06-teams`, for the same
reasons.

One manager is the primary one. That relationship is what feeds the organisation
chart and, more importantly, the permission scopes. Other managers can exist
alongside: a functional manager, a cross functional lead, somebody covering an
absence. They appear on the profile of an employee as secondary information and
never in the main organisation chart, exactly as cross functional team
memberships do.

This spec is a blocker for more of the product than its size suggests. Three
things named elsewhere in the specification cannot exist without it: the
`direct_reports` permission scope, the Manager default role, and any approval flow
that routes to somebody's manager.

## 2. User Stories & Requirements

### Stories

**As a People administrator**, I say who somebody reports to, and changing it
later keeps the record of who they used to report to.

**As a People administrator**, I record a second manager for somebody who works
across two parts of the company, without disturbing the organisation chart.

**As a manager**, I see the people who report to me and I can update their
records, without that giving me access to everybody in the company.

**As a manager going on holiday**, somebody can be recorded as covering for me,
and the record of that ends when I come back.

**As anybody**, I can ask an employee who has managed them and when, and ask a
manager who they have managed and for how long.

**As an administrator**, the system refuses to let me create a reporting loop.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | The reporting relationship links two employees of the same company. |
| FR-02 | The relationship is many to many and carries a primary flag. |
| FR-03 | An employee has at most one primary manager at a time, and may have none. |
| FR-04 | An employee may have any number of non primary managers at the same time. |
| FR-05 | The relationship is recorded in an append only history table, never updated in place. |
| FR-06 | Changing the primary manager closes the current row and opens a new one, in one transaction. |
| FR-07 | The history is readable from both ends: from the managed employee and from the manager. |
| FR-08 | The `manager_employee_id` column on the employee mirrors the active primary row. |
| FR-09 | No cycles. An employee may not become, directly or indirectly, the manager of their own manager. Checked in the application on every write of an active relationship, not by a database constraint. |
| FR-10 | An employee may not be their own manager. |
| FR-11 | The organisation chart is built from primary relationships only. |
| FR-12 | Non primary managers appear on the profile of an employee as secondary information. |
| FR-13 | The `direct_reports` scope resolves to the employees whose active primary manager is the employee linked to the acting user. |
| FR-14 | A user with no linked employee resolves `direct_reports` to nobody, never to everybody. |
| FR-15 | Setting the manager of an employee requires `employee.update` covering that employee. |

## 3. Technical Specifications & Boundaries

### Data model

```
EmployeeManager
- id
- employee_id              the person being managed
- manager_employee_id      the manager
- is_primary               boolean
- started_at
- ended_at                 nullable, null means the relationship is active
```

### The append only history pattern

Same pattern as `05-locations`, where the full reasoning is written. Changing who
somebody reports to closes the current row and opens a new one. Nothing is
overwritten.

Read from both ends. From the employee: who has managed me, and when. From the
manager: who have I managed, and for how long. The second direction is the one a
simple `manager_employee_id` column cannot answer at all.

### No cycles

A cycle is checked by walking the chain of active primary relationships upward
from the proposed manager. If the employee being managed appears in that chain,
the write is refused. The check runs on every creation or modification of an
active relationship.

It is an application check rather than a database constraint because the
condition is recursive over a subset of rows, which no portable constraint
expresses.

Two clarifications. Non primary relationships are excluded from the cycle check,
because a functional manager who also reports to the person they advise is a real
and acceptable arrangement. And an ended relationship is excluded, because a
historical loop across time is not a loop.

### The `direct_reports` scope

This spec unblocks the scope that `04-permissions-and-roles` deliberately left
out. When it ships, `ScopeEnum` gains a `direct_reports` case and the resolution
is:

```
the employees whose active primary EmployeeManager row
points at the employee linked to the acting user
```

Two edge cases, both resolving to nobody rather than to everybody.

- A user with no linked employee. An administrative account manages nobody.
- An employee who manages nobody. Empty set, not an error.

The rule is the one already written into `ScopeEnum`: a scope that cannot be
evaluated must deny, never allow.

Only the primary relationship feeds the scope. A non primary manager sees the
profile of somebody they advise through whatever company wide permission they
hold, not through this scope. Widening it to non primary relationships would make
the reach of a permission depend on an arrangement nobody thinks of as an access
grant.

### The Manager default role

Once the scope exists, a Manager role joins the defaults created with every
company in `04-permissions-and-roles`:

| Permission | Scope |
| --- | --- |
| `employee.view` | company |
| `employee.update` | direct_reports |
| `employee.view_private` | direct_reports |

Whether a manager should see the private details of their reports is a real
question and companies disagree about it. The default above grants it, and the
role is editable, which is the point of making roles editable.

### Events published

| Event | When |
| --- | --- |
| `employee.manager_changed` | The primary manager of an employee changes. |
| `employee.gained_manager` | A non primary relationship starts. |
| `employee.lost_manager` | A non primary relationship ends. |

`employee.manager_changed` carries the previous and the new manager. It is one of
the transitions the source document names as a playbook trigger.

### Out of scope

- **Approval routing.** Which approvals go to a manager belongs to the modules
  that have approvals, such as PTO and asset requests.
- **Dotted line reporting as a distinct concept.** A non primary relationship
  covers it. No separate type field.
- **Skip level relationships.** They are derived by walking the chain, not
  stored.
- **The `reporting_line` scope**, meaning the whole chain below somebody rather
  than direct reports only. Named in the source document as a later addition.
  It is a natural extension of this model and is deliberately not in the first
  version.
- **Team leads.** A team lead is recorded on the team in `06-teams` and is not
  automatically a manager of anybody.

## 4. Acceptance Criteria

- [ ] AC-01. Setting a primary manager creates a row with the primary flag, a
      start date and no end date.
- [ ] AC-02. Changing the primary manager closes the previous row and opens a new
      one, and both survive.
- [ ] AC-03. An employee never has two active primary managers.
- [ ] AC-04. An employee can have several active non primary managers.
- [ ] AC-05. Making somebody their own manager is refused.
- [ ] AC-06. A direct cycle of two people is refused.
- [ ] AC-07. A cycle three or more levels deep is refused.
- [ ] AC-08. A non primary relationship that would form a loop is allowed.
- [ ] AC-09. Asking an employee for their managers returns every relationship
      with its period. Asking a manager for their reports returns everybody they
      have managed with the periods.
- [ ] AC-10. The `manager_employee_id` on the employee matches the active primary
      row.
- [ ] AC-11. The organisation chart shows primary relationships only.
- [ ] AC-12. A permission granted at `direct_reports` covers the employees whose
      active primary manager is the acting user, and nobody else.
- [ ] AC-13. A user with no linked employee is denied by a `direct_reports`
      grant, for every target.
- [ ] AC-14. A manager holding only `employee.update` at `direct_reports` cannot
      update somebody who does not report to them.
- [ ] AC-15. Changing the primary manager publishes `employee.manager_changed`
      carrying both managers.

## 5. Implementation status

Nothing in this spec exists. There is no `employee_managers` table and no
`manager_employee_id` on the employee.

Three things elsewhere are explicitly waiting on it.

- `ScopeEnum` has no `direct_reports` case, with a comment stating that scopes
  for reporting lines and teams belong there once those exist, and not before.
- `CreateDefaultRoles` creates three roles rather than the five the source
  document lists. Manager is one of the two missing.
- `03-employees` has none of its denormalised columns.

### Suggested build order

1. The table, the model, the cycle check, and the action that sets a primary
   manager. Plus `manager_employee_id` on the employee.
2. Non primary relationships and the secondary line on the profile.
3. `direct_reports` in `ScopeEnum`, and the resolution in
   `PendingPermissionCheck`. This is the step that has to be tested hardest,
   since getting it wrong widens access silently.
4. The Manager default role, added to existing companies through a migration, as
   the comment in `CreateDefaultRoles` describes.
5. The organisation chart, which also needs `06-teams`.
