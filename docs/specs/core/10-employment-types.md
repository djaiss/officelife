# 10. Employment types

| | |
| --- | --- |
| **Identifier** | `core/10-employment-types` |
| **Status** | Specified |
| **Source** | Section 2.9 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy`, `03-employees`, `04-permissions-and-roles` |
| **Depended on by** | `12-playbooks`, `backlog/20-pillar-operate` (PTO eligibility) |

## 1. Context / Overview

The employment type is treated exactly like the lifecycle status in
`08-employee-lifecycle-status`: a fixed system list that every company gets, plus
whatever the company adds on top.

It gets that treatment rather than being a free text field for one reason. The
employment type conditions business rules. Who is eligible for paid leave, which
onboarding playbook runs, whether somebody appears in a headcount that excludes
contractors. A rule cannot be written against a string somebody typed.

Four system types, provided to every company and not modifiable:

| Type | |
| --- | --- |
| `full_time` | |
| `part_time` | |
| `contractor` | |
| `intern` | |

A company may add its own, for instance a working student arrangement or a
fixed term contract type that matters locally.

Like everything else about an employee that changes over time, the employment
type is historised. Somebody moves from contractor to full time, or from full
time to part time, and both facts are kept.

## 2. User Stories & Requirements

### Stories

**As a People administrator**, I record whether somebody is full time, part time,
a contractor or an intern, without inventing the vocabulary myself.

**As an administrator**, I add the employment type my country or my company
actually uses, when none of the four fits.

**As a People administrator**, I convert a contractor into a full time employee
and the record shows both periods.

**As anybody building a playbook**, I can make a step depend on the employment
type, so that contractor onboarding differs from employee onboarding.

**As anybody counting people**, I can count full time employees separately from
contractors.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | Four system employment types exist for every company: full time, part time, contractor, intern. |
| FR-02 | System types cannot be renamed or deleted by a company. |
| FR-03 | A company may create additional types of its own. |
| FR-04 | A custom type carries no semantic link to any system type. |
| FR-05 | An employee has at most one employment type at a time, and may have none. |
| FR-06 | The employment type is recorded in an append only history table, never updated in place. |
| FR-07 | Every change closes the current row and opens a new one, in one transaction. |
| FR-08 | The `employment_type_id` column on the employee mirrors the active row. |
| FR-09 | Deleting a custom type is refused while anybody holds it. |
| FR-10 | Changing the employment type of an employee requires `employee.update` covering that employee. Managing the list requires `company.manage`. |
| FR-11 | The employment type is available as a condition on playbook triggers. |

## 3. Technical Specifications & Boundaries

### Data model

```
EmploymentType
- id
- company_id               null for a system type, set for a company one
- key                      full_time | part_time | contractor | intern, null for custom
- name
- is_system                boolean
- created_at, updated_at

EmployeeEmploymentType
- id
- employee_id
- employment_type_id
- started_at
- ended_at                 nullable, null means this is the current type
```

The shape is identical to `EmployeeStatus` in `08-employee-lifecycle-status`.
That is intentional, not accidental duplication: they are two different lists of
values with different meanings, and merging them into one polymorphic
classification table would make every query harder to read in exchange for one
fewer table.

### System types shared or copied

Two ways to give every company the same four types. Either one shared row with a
null `company_id`, or four rows copied into each company at creation.

This spec chooses the shared row, matching the source document, which puts
`company_id` as nullable with null meaning a system type. It means the four rows
exist once, cannot drift between companies, and cannot be edited by anybody.
The cost is that every query filtering by company has to accept a null
`company_id` as well, which is a single scope on the model.

### The append only history pattern

Same pattern as `05-locations`, where the full reasoning is written.

### Events published

| Event | When |
| --- | --- |
| `employee.employment_type_changed` | The employment type of an employee changes. |

Carries the previous and the new type. Converting a contractor to a full time
employee is one of the transitions worth a playbook.

### Out of scope

- **Contract documents.** The `documents` category in `03-employees` is reserved
  and has no fields.
- **Working hours and part time percentages.** An employee marked part time does
  not carry the fraction. That belongs with time tracking, in
  `backlog/20-pillar-operate`.
- **Leave entitlement rules per type.** The type is what a PTO module would base
  eligibility on. The rules themselves belong to that module.
- **Contract end dates.** A fixed term arrangement ends by the employee moving to
  `departed` in `08-employee-lifecycle-status`. There is no separate end of
  contract date here.
- **Country specific contract types shipped by default.** A company adds what it
  needs. The product does not ship a catalogue of national employment law.

## 4. Acceptance Criteria

- [ ] AC-01. Every company has the four system types available with no
      configuration.
- [ ] AC-02. A system type cannot be renamed or deleted.
- [ ] AC-03. A company can add a type of its own, and it is not visible to any
      other company.
- [ ] AC-04. Deleting a custom type that somebody holds is refused.
- [ ] AC-05. Setting the employment type of an employee creates a history row
      with a start date and no end date.
- [ ] AC-06. Changing it closes the previous row and opens a new one, and both
      survive.
- [ ] AC-07. An employee never has two active employment type rows.
- [ ] AC-08. The `employment_type_id` on the employee matches the active row.
- [ ] AC-09. Asking an employee for their employment types returns the full list
      with periods.
- [ ] AC-10. A user without `employee.update` covering an employee cannot change
      their employment type.
- [ ] AC-11. A user without `company.manage` cannot add or delete a custom type.
- [ ] AC-12. Changing the employment type publishes
      `employee.employment_type_changed` carrying both types.
- [ ] AC-13. A playbook trigger can be conditioned on the employment type.

## 5. Implementation status

Nothing in this spec exists. There is no `employment_types` table, no
`employee_employment_types` table and no `employment_type_id` on the employee.

This is the least blocking of the six history bearing specs. Nothing else in the
core waits on it. It matters for playbook conditions and for a future PTO module,
both of which are downstream.

### Suggested build order

1. The two tables, the four system types, `employment_type_id` on the employee,
   and the action that changes it.
2. Custom types and their administration screen, which can share the screen used
   for lifecycle statuses in `08-employee-lifecycle-status`, since the two lists
   are administered the same way.
3. The event, once `11-occurrences` exists.
