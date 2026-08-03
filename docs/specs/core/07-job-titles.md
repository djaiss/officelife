# 07. Job titles

| | |
| --- | --- |
| **Identifier** | `core/07-job-titles` |
| **Status** | Partially implemented |
| **Source** | Section 2.6 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy`, `03-employees`, `04-permissions-and-roles` |
| **Depended on by** | `backlog/22-pillar-grow` (skills and career conversations) |

## 1. Context / Overview

This spec is deliberately light, and the restraint is the point.

Managing job families properly (level matrices, competency grids per rung, salary
bands attached to each) is a whole HR discipline. It is out of scope, consistently
with compensation being out of scope in `03-employees`. What is in scope is enough
structure to classify people, report on them, and later hang skills and career
conversations off a real history.

Three concepts, in decreasing order of importance.

**Job title.** A predefined list owned by the company, like teams. Not a free text
field, because free text cannot be counted, filtered or scoped against.

**Job family.** An optional, purely descriptive grouping of job titles
("Engineering", "Design", "Sales"). No hierarchy of levels, no numeric grade.

**Custom title.** What an employee wants to be called. Somebody may show up as
"Growth Wizard" rather than "Marketing Manager". It does not replace the official
job title; when a custom title exists the system shows both.

Recording the job title of an employee over time gives the career history of that
person for free, which is what the Grow pillar needs, without building a career
progression engine.

## 2. User Stories & Requirements

### Stories

**As an administrator**, I define the job titles my company uses, so that nobody
invents a new one by typing it into a field.

**As an administrator**, I optionally group those titles into families, and I can
ignore families entirely.

**As a People administrator**, I change somebody's job title and the previous one
is kept with its dates, giving me their career history at this company without
having recorded it separately.

**As an employee**, I set the title I want to be shown as, and my official job
title stays visible next to it.

**As anybody**, I look at a colleague and see what they do.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | A job title belongs to exactly one company and has a name unique within it. |
| FR-02 | A job title may belong to a job family of the same company. The family is optional. |
| FR-03 | A job family is descriptive only. It carries no level, no grade and no salary band. |
| FR-04 | The job title of an employee is chosen from the list of the company. It is never free text. |
| FR-05 | An employee may set a custom title, which is free text, and which is shown alongside the official job title rather than instead of it. |
| FR-06 | The job title of an employee is recorded in an append only history table, never updated in place. |
| FR-07 | The custom title is historised with the job title, in the same row. |
| FR-08 | An employee has at most one active job title at a time, and may have none. |
| FR-09 | The `job_title_id` and `custom_title` columns on the employee mirror the active row. |
| FR-10 | Deleting a job title is refused while anybody holds it. |
| FR-11 | Managing job titles and families requires `company.manage` at company scope. |

## 3. Technical Specifications & Boundaries

### Data model

```
JobFamily                  optional, purely descriptive
- id, company_id
- name                     "Engineering", "Design", "Sales"

JobTitle
- id, company_id
- job_family_id            nullable
- name                     "Senior Backend Engineer", "Product Designer"

EmployeeJobTitle
- id
- employee_id
- job_title_id             the official title, from the company list, the source of truth
- custom_title             nullable, the display name the employee goes by
- started_at
- ended_at                 nullable, null means this is the current title
```

### The append only history pattern

Same pattern as `05-locations`, where the full reasoning is written. Changing the
job title of an employee closes the current row and opens a new one. The active
row is the one where `ended_at` is null.

The custom title lives in the same row rather than on the employee, so that
changing it is also historised. Somebody who was "Growth Wizard" for a year and
then "Head of Growth" leaves both facts behind them.

### Why the job title is a list and not free text

Three reasons, and any one of them would be enough.

1. Classification. "How many engineers do we have" has to be answerable.
2. Reporting. A free text field produces "Sr. Engineer", "Senior Engineer" and
   "senior engineer" as three different roles.
3. Scoping. A future permission scope or playbook condition that ranges over job
   titles needs stable identifiers.

The custom title exists precisely so that this constraint does not feel like one
to the person it describes.

### Events published

| Event | When |
| --- | --- |
| `employee.job_title_changed` | The official job title of an employee changes. |

Carries the previous and the new title. A promotion playbook triggers on it.
A change of custom title alone does not publish an event; it is a display
preference.

### Out of scope for the first version

Named explicitly by the source document, and repeated here because these are the
things somebody will try to add.

- **Numeric levels and grades.** No "Engineer 3".
- **Competency matrices per level.**
- **Salary bands attached to a job title.** Compensation is out of scope
  everywhere.
- **Career paths.** A learning candidate, see `backlog/23-module-candidates`.
- **Hierarchy inside job families.**

All of these can be layered on top of `JobTitle` later without breaking the
model. That is the whole reason for making it a real entity now rather than a
string.

## 4. Acceptance Criteria

- [ ] AC-01. A job title can be created with a name alone and no family.
- [ ] AC-02. Two job titles of the same company cannot share a name; two job
      titles of different companies can.
- [ ] AC-03. A job family can be created and deleted, and deleting one leaves its
      job titles in place with no family.
- [ ] AC-04. Giving an employee a job title creates a history row with a start
      date and no end date.
- [ ] AC-05. Changing the job title of an employee closes the previous row and
      opens a new one, and both survive.
- [ ] AC-06. Asking an employee for their job titles returns the full list with
      periods, which is their career history.
- [ ] AC-07. A custom title is shown alongside the official job title, never in
      place of it.
- [ ] AC-08. Changing only the custom title creates a new history row and
      publishes no event.
- [ ] AC-09. The `job_title_id` and `custom_title` columns on the employee match
      the active history row.
- [ ] AC-10. Deleting a job title that somebody holds is refused.
- [ ] AC-11. A user without `company.manage` cannot create, change or delete a
      job title or a family.
- [ ] AC-12. Changing the official job title publishes
      `employee.job_title_changed` carrying the previous and the new title.

## 5. Implementation status

### Already built

| Element | Where |
| --- | --- |
| `custom_title` column on the employee, with the comment saying it is for when no official job title covers the case | `database/migrations/0000_01_01_000001_create_employees_table.php` |
| `custom_title` accepted when creating and updating an employee | `app/Actions/CreateEmployee.php`, `app/Actions/UpdateEmployeeInformation.php` |

That is all. The custom title currently lives directly on the employee, which
means it is the only title there is, and it is not historised.

### Not built yet

| Gap | Requirement |
| --- | --- |
| No `job_families` table or model | FR-02, FR-03 |
| No `job_titles` table or model | FR-01, FR-04 |
| No `employee_job_titles` table | FR-06 to FR-09 |
| No `job_title_id` on the employee | FR-09 |
| `custom_title` is not historised and stands alone rather than alongside an official title | FR-05, FR-07, AC-07 |
| No administration screen for titles or families | FR-11 |
| No events published | AC-12 |

### Migration note

The existing `custom_title` column has to become the denormalised mirror of
`EmployeeJobTitle.custom_title` rather than the source of truth. The migration
that introduces the history table should backfill one row per employee who has a
custom title, with `job_title_id` left null, so no display regresses.
