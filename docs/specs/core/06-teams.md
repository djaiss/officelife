# 06. Teams

| | |
| --- | --- |
| **Identifier** | `core/06-teams` |
| **Status** | Specified |
| **Source** | Section 2.5 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy`, `03-employees`, `04-permissions-and-roles` |
| **Depended on by** | `04-permissions-and-roles` (the `selected_teams` scope), `12-playbooks`, the organisation chart |

## 1. Context / Overview

There is one entity, `Team`, and it references itself. A department is not a
separate concept: it is a team with no parent. A sub team is a team whose parent
is another team.

That single decision handles zero, one or several levels of hierarchy with no
change of schema, which is what section 2.3 of the source document asks for.
A company of twenty people has a flat list of teams and never learns that a
parent exists. A company of two hundred nests them three deep and needs no
migration to get there.

The second decision is that belonging and collaborating are different things.
An employee has one hierarchical team they belong to, which is stable and
structures the organisation chart. They may also collaborate with other teams,
possibly several at once, possibly temporarily. A cross functional team formed
for one feature is a team like any other, usually with no parent. The difference
between belonging and collaborating is a property of the relationship, not of the
team.

## 2. User Stories & Requirements

### Stories

**As an administrator**, I create the teams of my company as a flat list, and I
never have to decide whether something is a department or a team.

**As an administrator of a larger company**, I nest a team under another one and
the organisation chart follows.

**As an administrator**, I name a team lead, and I can leave a team without one.

**As a People administrator**, I put somebody in their primary team, and later
move them to another one without losing the fact that they used to be somewhere
else.

**As a People administrator**, I add somebody to a cross functional team for the
duration of a project, and remove them from it afterwards, without touching the
team they belong to.

**As anybody**, I look at a colleague and see the team they belong to, plus a
secondary line saying who else they work with.

**As anybody**, I look at a team and see everybody who has ever been part of it,
with the periods, not just the people in it today.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | A team belongs to exactly one company. |
| FR-02 | A team may have a parent team of the same company. A team with no parent is what a company would call a department. |
| FR-03 | A team may have a lead, who is an employee of the same company. The lead is optional. |
| FR-04 | The parent chain may not contain a cycle. |
| FR-05 | The relationship between an employee and a team is many to many, through a pivot carrying a primary flag and a period. |
| FR-06 | An employee has exactly one primary team at a time, enforced in the application. |
| FR-07 | An employee may have any number of non primary team memberships at the same time. |
| FR-08 | Membership is recorded in an append only history table, never updated in place. |
| FR-09 | Changing the primary team closes the current primary row and opens a new one, in one transaction. |
| FR-10 | The history is readable from both ends: from the employee and from the team. |
| FR-11 | The `team_id` on the employee mirrors the active primary row. |
| FR-12 | The organisation chart and the directory are built from primary memberships only. |
| FR-13 | Non primary memberships appear on the profile of an employee as a secondary line, and never in the organisation chart. |
| FR-14 | Deleting a team is refused while anybody has an active membership of it. |
| FR-15 | Managing teams requires `company.manage` at company scope. |

## 3. Technical Specifications & Boundaries

### Data model

```
Team
- id, company_id
- name                     "Design", "Sega"
- parent_team_id           nullable, FK to Team
- lead_employee_id         nullable, FK to Employee
- created_at, updated_at

EmployeeTeam
- id
- employee_id, team_id
- is_primary               boolean
- started_at               nullable
- ended_at                 nullable, null means the membership is active
```

### The append only history pattern

Same pattern as `05-locations`, where the full reasoning is written. A membership
is never updated in place. Ending one closes the row with `ended_at`; starting one
inserts a row with `started_at`. The active rows are those where `ended_at` is
null.

Applied here it answers two questions that a simple `team_id` on the employee
cannot. From the employee: every team they have been part of, primary or cross
functional, with the exact periods. From the team: everybody who has ever been in
it, with their periods, not only the current members.

### Primary and non primary

The primary flag lives on the membership, not on the team. The same team can be
somebody's primary team and somebody else's cross functional collaboration at the
same time. Nothing about the team says which it is.

Exactly one primary membership per employee at a time, enforced in the
application rather than by a unique index, because the constraint is over the
subset of rows where `ended_at` is null and `is_primary` is true, and expressing
that portably as an index is more trouble than it is worth.

An employee can have no primary team at all, which is the normal state for
somebody who has just been created and not yet placed.

### Cycles

The parent chain is walked on every write that sets or changes a parent. A team
may not become its own ancestor. Checked in the application, not by a constraint.

### Effect on permissions and events

Everything that says "team" elsewhere in the specification means the primary team
unless it says otherwise. That covers the future `selected_teams` scope in
`04-permissions-and-roles` and the triggers in `11-domain-events`.

### Events published

| Event | When |
| --- | --- |
| `team.created` | A team is created. |
| `team.deleted` | A team is deleted. |
| `employee.team_changed` | The primary team of an employee changes. |
| `employee.joined_team` | A non primary membership starts. |
| `employee.left_team` | A non primary membership ends. |

`employee.team_changed` carries the previous and the new team, so that a transfer
playbook can react to a specific move.

### Out of scope

- **Team pages, team news, team calendars.** Those belong to
  `backlog/21-pillar-communicate`.
- **Permissions granted through a team.** The `selected_teams` scope is named in
  the source document and deferred; see `04-permissions-and-roles`.
- **More than one lead per team.**
- **Team budgets and headcount targets.** Finance candidates, see
  `backlog/23-module-candidates`.
- **Automatic membership rules.** No "everybody in Paris is in the Paris team".

## 4. Acceptance Criteria

- [ ] AC-01. A team can be created with a name alone, with no parent and no lead.
- [ ] AC-02. A team can be given a parent, and a team with no parent behaves as a
      top level department.
- [ ] AC-03. Setting a parent that would create a cycle is refused, including a
      cycle several levels deep.
- [ ] AC-04. Giving an employee a primary team creates a membership row with the
      primary flag, a start date and no end date.
- [ ] AC-05. Moving an employee to another primary team closes the previous row
      and opens a new one, and both survive.
- [ ] AC-06. An employee cannot end up with two active primary memberships.
- [ ] AC-07. An employee can hold several active non primary memberships at once.
- [ ] AC-08. Ending a non primary membership sets its end date and leaves the
      primary membership untouched.
- [ ] AC-09. Asking an employee for their teams returns every membership with its
      period. Asking a team for its members returns everybody who has been in it
      with their periods.
- [ ] AC-10. The `team_id` on the employee matches the active primary membership
      after every change.
- [ ] AC-11. The organisation chart shows primary memberships only.
- [ ] AC-12. The profile of an employee shows non primary memberships on a
      separate line.
- [ ] AC-13. Deleting a team with active members is refused.
- [ ] AC-14. A user without `company.manage` cannot create, change or delete a
      team.
- [ ] AC-15. Changing a primary team publishes `employee.team_changed` carrying
      the previous and the new team.

## 5. Implementation status

Nothing in this spec exists in the codebase. There is no `teams` table, no
`Team` model, no `employee_teams` table and no `team_id` on the employee.

Two things elsewhere are waiting on it.

- `ScopeEnum` in `04-permissions-and-roles` deliberately omits any team scope,
  with a comment saying a scope nothing can evaluate quietly allows too much.
- The employee table has none of its denormalised columns, `team_id` included.

### Suggested build order

1. `teams` table, `Team` model, create, update and delete actions, the
   administration screen. This alone is useful and shippable.
2. `employee_teams` table and the primary membership, plus `team_id` on the
   employee.
3. Non primary memberships and the secondary line on the profile.
4. The organisation chart, which also needs `09-managers`.
