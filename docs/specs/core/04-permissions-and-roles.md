# 04. Permissions and roles

| | |
| --- | --- |
| **Identifier** | `core/04-permissions-and-roles` |
| **Status** | Partially implemented |
| **Source** | Section 4 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy`, `02-users-and-authentication`, `03-employees` |
| **Depended on by** | Every spec that has an action somebody performs |

## 1. Context / Overview

Two extremes have to be avoided. A system with three rigid roles cannot express
what a company of 150 people actually needs. A system where every user carries
eighty individually configured permissions cannot be administered by anybody.

The model sits between them. A user belongs to one company, may be linked to an
employee, holds one or more roles, and can receive targeted exceptions on top.

Two ideas carry most of the weight.

**Permissions are capabilities, not screens.** A permission is an atomic action
(`employee.view`, `leave.approve`, `workflow.activate`), named after what it
allows rather than where it appears. Screens come and go; capabilities do not.

**Every grant carries a scope.** Saying that a manager may update employees is
meaningless without saying which employees. The scope answers that, and the same
permission can be granted at different scopes to different roles.

## 2. User Stories & Requirements

### Stories

**As the owner of a company**, I can do everything inside my own company, and
nobody can take that away from me by editing a role.

**As an administrator**, I create a role, say what it is allowed to do and over
whom, and hand it to several people at once.

**As an administrator**, I duplicate an existing role rather than rebuilding a
similar one from scratch.

**As an administrator**, I see who holds each role, and I take a role back.

**As an administrator**, I am warned before I hand somebody a role that lets them
administer the company.

**As an administrator**, I cannot delete a role that somebody still holds,
because that would silently strip their access.

**As a manager**, I see and update the people who report to me, and nobody else.

**As anybody**, when I try to reach something I am not allowed to see, it behaves
as though it does not exist rather than telling me it exists and I cannot have
it.

**As a company**, sensitive writes are recorded, so a change to a role or to
compensation can be traced back to whoever made it.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | A permission is an atomic capability identified by a key of the form `entity.action`. |
| FR-02 | A role is a named set of permissions belonging to one company. |
| FR-03 | Every grant of a permission to a role carries a scope. |
| FR-04 | A user may hold several roles at once, and the scopes granted by their roles add up to the widest one. |
| FR-05 | Every company is created with a default set of roles, so there is never a company with no way of handing out access. |
| FR-06 | Default roles are editable. A company that wants something else changes them or adds its own. |
| FR-07 | The owner of a company may do everything inside it. This is not a role and cannot be granted or revoked. |
| FR-08 | A permission is evaluated in this order: explicit deny, explicit allow, role permissions, default deny. An explicit deny always wins. |
| FR-09 | A permission that targets one employee at a time must be checked against a specific employee. A permission that covers the whole company is always granted at company scope. |
| FR-10 | The target of a check is always passed in explicitly and never inferred, so a check cannot quietly answer about something other than what is being acted on. |
| FR-11 | A refused check produces a not found result, not a forbidden one. |
| FR-12 | A role that somebody still holds cannot be deleted. |
| FR-13 | A role can be duplicated, with its grants. |
| FR-14 | Individual exceptions can grant or deny one permission to one user, optionally with an expiry. |
| FR-15 | A permission can be delegated temporarily from one user to another, for a defined period. |
| FR-16 | Sensitive writes are recorded in an audit log: compensation, roles and permissions, and workflow activation. |
| FR-17 | Reads are not audited in the first version. |

## 3. Technical Specifications & Boundaries

### Data model

```
Role
- id, company_id
- name
- slug                     unique per company
- is_default               one of the roles every company starts with
- is_editable              whether it may be renamed, regranted or deleted

RolePermission
- id, role_id
- permission               a permission key
- scope                    a scope

UserRole
- id, user_id, role_id

UserPermission             individual exception
- id, user_id
- permission
- effect                   allow | deny
- scope
- expires_at               nullable

Delegation                 temporary transfer
- id
- from_user_id, to_user_id
- permission, scope
- starts_at, ends_at

AuditLog
- id
- actor_user_id, actor_type
- action
- subject_type, subject_id
- metadata                 JSON
- created_at
```

### Permissions are an enum, not a table

The source document listed a `Permission` table. The implementation made the list
an enum instead, and a role stores the value of the case it grants.

This is a deliberate divergence and it holds. The set of permissions is defined by
the code that checks them, not by data a company can edit. A permission row with
no code checking it grants nothing; a permission checked in code with no row would
be a crash. Making it an enum removes the possibility of the two drifting apart,
at the cost of a data migration whenever a value is renamed.

A permission is only added along with the feature that uses it. A permission that
nothing checks is a permission that quietly allows too much.

Current cases:

```
employee.view                 see the profile of a colleague
employee.create               add somebody to the company
employee.update               change the profile of a colleague
employee.view_private         see the private details of a colleague
employee.update_private       change the private details of a colleague
role.manage                   administer the company, its roles and who holds them
company.manage                change the settings of the company
```

Each permission declares three things in the enum itself: whether it targets one
employee, which scopes it may be granted at, and which group it is listed under
on the screen where somebody grants it.

### Scopes

The first version has `self` and `company`. Section 4.4 of the source document
also listed `direct_reports`.

`direct_reports` is deliberately absent, and the reason is written into the code:
a scope nothing can evaluate is a scope that quietly allows too much. There is no
manager relationship in the codebase yet, so a `direct_reports` grant would have
to fall back to something, and every fallback is wrong. It is added when
`09-managers` ships, not before.

The same reasoning applies to the finer scopes named in the source document
(`reporting_line`, `selected_teams`, `managed_locations`). Each waits for the
entity it ranges over.

A permission that does not target an employee has nothing to narrow down and is
therefore only ever granted at company scope. No scope is offered for it on the
screen.

### Order of evaluation

```
1. explicit deny        UserPermission with effect = deny, not expired
2. owner                the user owns the company
3. explicit allow       UserPermission with effect = allow, not expired
4. role permissions     the widest scope granted across all their roles
5. default deny
```

Ownership sits at step 2 rather than above the deny, so that an explicit deny can
still be recorded against an owner. Whether it should actually stop them is a
question worth revisiting; for now the owner check is second and an explicit deny
on an owner is the only way to constrain them.

### Default roles

Every company is created with three.

| Role | Grants |
| --- | --- |
| Administrator | Every permission at company scope. |
| People administrator | View, create and update employees, and their private details, at company scope. No role or company administration. |
| Member | View colleagues at company scope. Update themselves and their own private details at self scope. |

The source document also named Manager and IT/Workplace administrator. Manager
waits on `09-managers`, because without `direct_reports` the role would either
grant nothing useful or grant company wide access under a misleading name.
IT/Workplace administrator waits on the asset module.

Recruiter and Finance administrator are not created, since recruiting and
expenses are future modules. The model supports adding them with no structural
migration.

### Audit

The audit log covers sensitive writes only: compensation, roles and permissions,
and workflow activation. Auditing reads (`employee.compensation.viewed`) can be
added later without changing the shape of the log.

The audit log is not the same thing as the log of user actions that already
exists. The action log records what somebody did so that they can read it back on
their own settings screen. The audit log records what was done to sensitive data
so that the company can answer for it. They have different readers, different
retention expectations, and different contents.

### Out of scope for the first version

Explicitly deferred by section 4.8 of the source document.

- Active delegation. The `Delegation` table exists in the schema; the interface
  and the activation logic wait.
- Individual exceptions through `UserPermission`, beyond very occasional cases.
- Fine grained scopes.
- Auditing reads of sensitive data.
- The Recruiter and Finance administrator roles.

### On using an off the shelf package

Section 4.9 of the source document proposed evaluating `spatie/laravel-permission`
as a base. The implementation did not, and wrote roles, permissions and the check
by hand.

That decision holds. The package models a permission as a row and a check as a
question about a user alone. This product needs a permission that is an enum case
and a check that is a question about a user and a target together, with a scope.
Adopting the package would mean using it for the table structure while replacing
its entire evaluation path, which buys nothing.

## 4. Acceptance Criteria

- [x] AC-01. A new company has an Administrator, a People administrator and a
      Member role.
- [x] AC-02. The first user of a company holds the Administrator role.
- [x] AC-03. A user holding two roles that grant the same permission at different
      scopes gets the wider of the two.
- [x] AC-04. The owner of a company passes every check inside their company, even
      holding no role.
- [x] AC-05. A check against an employee of another company fails, whatever the
      roles of the user.
- [x] AC-06. A failed check produces a not found result.
- [x] AC-07. A permission that does not target an employee cannot be granted at
      self scope, and no scope is offered for it on the screen.
- [x] AC-08. Deleting a role that somebody holds is refused.
- [x] AC-09. Duplicating a role copies its name, its permissions and their
      scopes, and copies nobody who holds it.
- [x] AC-10. Granting a role to a user takes effect on their next check without
      them signing in again.
- [x] AC-11. The screen listing permissions groups them into people, sensitive
      data and administration, and warns about a role that administers the
      company.
- [ ] AC-12. An explicit deny on a user overrides an allow granted by any of
      their roles.
- [ ] AC-13. An expired individual exception stops applying.
- [ ] AC-14. A delegation applies between its start and its end, and not outside.
- [ ] AC-15. Changing the roles of a user writes an entry to the audit log.

## 5. Implementation status

This area is further along than the source document asked for. The screens exist
in full.

### Already built

| Element | Where |
| --- | --- |
| `roles`, `role_permissions`, `user_roles` tables | `database/migrations/2026_08_01_00000{1,2,3}_*.php` |
| `PermissionEnum` with 7 cases, each declaring its target, its scopes, its group and its label | `app/Enums/PermissionEnum.php` |
| `ScopeEnum` with `self` and `company` | `app/Enums/ScopeEnum.php` |
| `PermissionGroupEnum` for laying the list out | `app/Enums/PermissionGroupEnum.php` |
| The check itself, as a fluent pending check resolved against an employee or a company | `app/Permissions/PendingPermissionCheck.php`, `app/Permissions/PermissionDecision.php` |
| Owner bypass, cross company refusal, scope widening across roles, grant caching per request | `app/Permissions/`, `app/Models/User.php` |
| The three default roles, created with the company | `app/Actions/CreateDefaultRoles.php` |
| Create, update, delete, duplicate a role; grant and revoke it | `app/Actions/CreateRole.php`, `UpdateRole.php`, `DestroyRole.php`, `AssignRole.php`, `RemoveRole.php` |
| The role administration screens, with the permission matrix, scope pickers, filtering, folding, the administration warning, and the list of holders | `app/Http/Controllers/App/Settings/Administration/RoleController.php`, `DuplicateRoleController.php`, `RolePeopleController.php` |
| Every action that touches an employee or the company checks the roles of whoever asked | throughout `app/Actions/` |

FR-01 through FR-13 and FR-17 are satisfied. AC-01 through AC-11 are covered by
tests, including a dedicated `tests/Unit/Permissions` suite.

### Not built yet

| Gap | Requirement |
| --- | --- |
| No `user_permissions` table; individual exceptions do not exist, so steps 1 and 3 of the evaluation order are absent | FR-08, FR-14, AC-12, AC-13 |
| No `delegations` table | FR-15, AC-14 |
| No audit log; the existing log of user actions is a different thing | FR-16, AC-15 |
| No `direct_reports` scope | blocked on `09-managers` |
| No Manager or IT/Workplace administrator default role | blocked on `09-managers` and the asset module |
| No permissions for the compensation, relations and documents categories | blocked on those fields existing, see `03-employees` |

### Divergences from the source document to note

- **Permissions are an enum, not a table.** Adopted, reasoning above.
- **`spatie/laravel-permission` was not used.** Adopted, reasoning above.
- **`is_system` on roles became `is_default` and `is_editable`.** The source
  document named one flag; the implementation carries two, separating "shipped
  with the company" from "may be changed". That is the better split and this spec
  adopts it.
