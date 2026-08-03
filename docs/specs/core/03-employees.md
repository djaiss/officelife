# 03. Employees

| | |
| --- | --- |
| **Identifier** | `core/03-employees` |
| **Status** | Partially implemented |
| **Source** | Sections 2.2, 2.3, 2.10 and 4.5 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy` |
| **Depended on by** | `04-permissions-and-roles`, `05-locations`, `06-teams`, `07-job-titles`, `08-employee-lifecycle-status`, `09-managers`, `10-employment-types`, `12-playbooks`, and every module |

## 1. Context / Overview

The employee is the central object of the application. Most of what the product
does hangs off the life cycle of an employee and their relationships: their team,
their manager, their office, their equipment. That is true whether or not the
employee has an account to sign in with.

The record itself is deliberately small. Everything about an employee that
changes over time (their office, their team, their job title, their status, their
manager, their employment type) lives in its own history table, each specified in
its own file. What is left here is identity, contact details, and a set of
denormalised columns that mirror the currently active row of each of those
histories.

A small company must be able to create an employee without first modelling legal
entities, business units or cost centres. Complex structure may exist later as an
option. It must never dominate the first experience.

This spec also settles how the fields of an employee are categorised by
sensitivity, because visibility does not follow a single `employee.view`
permission. Who is allowed to see what is `04-permissions-and-roles`; which
category each field falls into is here.

## 2. User Stories & Requirements

### Stories

**As a People administrator**, I add somebody to the company with nothing more
than a first name and a last name, and I fill in the rest whenever I have it.

**As a People administrator**, I prepare an employee before their first day, so
that everything is ready when they arrive.

**As an employee**, I keep my own profile up to date: my display name, my photo,
my personal contact details and my emergency contact.

**As an employee**, I know that my home address and my emergency contact are not
visible to every colleague simply because they can look me up in the directory.

**As a People administrator**, I keep the record of somebody who has left, with
their history intact, without them appearing in the directory or being counted as
active.

**As anybody in the company**, I look up a colleague and find their name, photo,
role, team and work contact details.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | An employee belongs to exactly one company. |
| FR-02 | An employee can be created with a first name and a last name alone. Every other field is optional. |
| FR-03 | An employee exists independently of any account. Creating an employee never creates a user. |
| FR-04 | An employee can have an employee number, unique within the company, for payroll and HR systems that are outside the product. |
| FR-05 | An employee has a display name that overrides how their name is shown, without replacing their legal first and last name. |
| FR-06 | An employee has a photo, stored at several sizes, with a fallback when there is none. |
| FR-07 | An employee has a work email, unique within the company. |
| FR-08 | An employee has a country and a time zone of their own, independent of any office, because a remote employee has no office and still needs both. |
| FR-09 | An employee attached to an office may inherit country and time zone from it by default; the value stays overridable on the employee. |
| FR-10 | Every field of an employee belongs to exactly one sensitivity category: public, professional, private, compensation, relations or documents. |
| FR-11 | The private fields are readable only with `employee.view_private` covering that employee, and writable only with `employee.update_private`. |
| FR-12 | The compensation, relations and documents categories exist in the model with no fields and no interface in the first version. |
| FR-13 | Deleting an employee is a soft delete. History is kept after somebody leaves. |
| FR-14 | The denormalised columns (office, team, job title, status, manager, employment type) always mirror the currently active row of the matching history table. They are a read shortcut and never the source of truth. |
| FR-15 | An employee records when they last updated their own information. |

## 3. Technical Specifications & Boundaries

### Data model

Grouped by the sensitivity categories of section 4.5.

```
Employee

  public
  - id
  - company_id                          FK, required
  - employee_number                     nullable, unique per company
  - first_name, last_name               required
  - display_name                        nullable
  - photo_path                          nullable
  - work_email                          nullable, unique per company

  professional
  - location_id                         denormalised, current       (05)
  - team_id                             denormalised, primary       (06)
  - job_title_id                        denormalised, current       (07)
  - custom_title                        denormalised, current       (07)
  - status_id                           denormalised, current       (08)
  - manager_employee_id                 denormalised, primary       (09)
  - employment_type_id                  denormalised, current       (10)
  - country, timezone                   independent of the office
  - hired_at                            actual or expected start date
  - departed_at                         nullable

  private
  - personal_email                      nullable
  - personal_phone                      nullable
  - date_of_birth                       nullable
  - emergency_contact_name              nullable
  - emergency_contact_phone             nullable
  - emergency_contact_relationship      nullable
  - home_address                        nullable

  compensation
  - reserved category, no field in the first version

  standard
  - last_saved_at                       when the employee last updated themselves
  - created_at, updated_at
  - deleted_at                          soft delete
```

### The denormalised columns

Six columns on the employee duplicate information that lives authoritatively in a
history table. Each is written in the same transaction as the history row it
mirrors, and never on its own.

The rule to apply everywhere: **read from the denormalised column, write through
the history table.** Any code path that sets `team_id` without closing the
previous `EmployeeTeam` row and opening a new one is a bug, not a shortcut.

This is a deliberate trade of consistency risk against query cost. The directory,
every filter and every list would otherwise need six joins with a
`WHERE ended_at IS NULL` on each.

### Sensitivity categories

| Category | Contains | Permission |
| --- | --- | --- |
| `public` | Name, photo, job title, team, work contact details | `employee.view` |
| `professional` | Start date, manager, status, employment type | `employee.view` |
| `private` | Home address, personal phone, date of birth, emergency contact | `employee.view_private` |
| `compensation` | Salary, bonus, history | `employee.view_compensation` |
| `relations` | HR notes, discipline, complaints | `employee.view_relations` |
| `documents` | Contract, identity documents | `employee.view_documents` |

The last three have no fields and no interface in the first version. The
categories and their permissions are declared now anyway, so that adding the
fields later does not require reworking every authorisation check.

### Name resolution

An employee has up to three ways of being named, and the order is fixed.

1. `display_name` when set.
2. Otherwise `first_name` and `last_name`.

Pronouns, if handled at all, go in `display_name` as free text rather than a
dedicated field. That is a deliberately low investment decision and can be
revisited.

### Events published

| Event | When |
| --- | --- |
| `employee.created` | An employee record is created. |
| `employee.updated` | Any field of an employee changes. |

The interesting employee events (`employee.arrived`, `employee.departed`) are not
published from here. They are published from status transitions, and are
specified in `08-employee-lifecycle-status`. This matters: an employee record
being created is not an arrival.

### Out of scope

- **Everything that changes over time.** Office, team, job title, status,
  manager, employment type each have their own spec.
- **Compensation.** The category is reserved and no field is modelled.
- **HR notes, discipline and complaints.** Legally sensitive and deliberately
  deferred. The category is reserved.
- **Documents.** Contracts and identity documents. Category reserved.
- **Custom fields on the employee.** The asset module gets custom fields
  (`17-lifecycle-operations`); the employee does not, in the first
  version.
- **Importing employees in bulk.**
- **The organisation chart.** It is built from `06-teams` and `09-managers`.

## 4. Acceptance Criteria

- [x] AC-01. An employee can be created with a first name and a last name and
      nothing else.
- [x] AC-02. Creating an employee does not create a user.
- [x] AC-03. Two employees of the same company cannot share an employee number or
      a work email; two employees of different companies can.
- [x] AC-04. An employee can upload, replace and delete their photo, and the
      photo is stored at each declared size.
- [x] AC-05. An employee updating their own information records when they did.
- [x] AC-06. A user without `employee.view_private` covering an employee sees
      that employee in the directory with no private field on the screen.
- [x] AC-07. A user without `employee.update_private` covering an employee cannot
      change any private field, whatever they post.
- [ ] AC-08. An employee has a country and a time zone even with no office
      attached.
- [ ] AC-09. Attaching an employee to an office proposes the country and time
      zone of that office and lets the employee keep their own.
- [ ] AC-10. Deleting an employee soft deletes them and keeps every history row
      attached to them.
- [ ] AC-11. Each of the six denormalised columns matches the row of its history
      table where `ended_at` is null, after every transition.
- [ ] AC-12. Creating an employee publishes `employee.created`.

## 5. Implementation status

### Already built

| Element | Where |
| --- | --- |
| `employees` table with identity, contact, private fields, `country`, `timezone`, `hired_at`, `departed_at`, `custom_title` and `last_saved_at` | `database/migrations/0000_01_01_000001_create_employees_table.php` |
| Uniqueness of employee number and work email within a company | same migration |
| `Employee` model with `company` and `user` relations, the `name` and `fullName` accessors, `isEmployed()`, and photo sizing | `app/Models/Employee.php` |
| Creating an employee, authorised and logged | `app/Actions/CreateEmployee.php` |
| Updating employee information | `app/Actions/UpdateEmployeeInformation.php` |
| Updating the emergency contact separately from the rest | `app/Actions/UpdateEmergencyContact.php` |
| Uploading, resizing and deleting the photo | `app/Actions/UpdateEmployeePhoto.php`, `DestroyEmployeePhoto.php`, `ResizeImage.php` |
| Private fields kept off the screen for anybody without the permission | `app/Permissions/`, the profile view models |
| The profile screen where somebody edits their own details | `app/Http/Controllers/App/Settings/Account/Profile/` |

FR-01 through FR-07 and FR-11 are satisfied. FR-08 is satisfied at the column
level. AC-01 through AC-07 are covered by tests.

### Not built yet

| Gap | Requirement |
| --- | --- |
| None of the six denormalised columns exists, because none of the six entities exists | FR-14, AC-11 |
| No soft delete on `employees` | FR-13, AC-10 |
| No directory screen; an employee is only reachable through their own profile | Several stories |
| No inheritance of country and time zone from an office | FR-09, AC-09 |
| The `compensation`, `relations` and `documents` permissions are not in `PermissionEnum`; only `employee.view_private` and `employee.update_private` are | FR-12 |
| No events published | AC-12 |

### Note on the sensitivity model

The implementation currently has two of the six categories wired: public and
professional collapse into `employee.view`, and private is separately gated. That
matches the first version. The three reserved categories are absent from
`PermissionEnum` entirely, which is consistent with the comment in that enum
saying a permission is only added along with the feature that uses it. This spec
records the intended full list without asking for the permissions to be added
before their fields exist.
