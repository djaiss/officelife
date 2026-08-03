# 05. Locations

| | |
| --- | --- |
| **Identifier** | `core/05-locations` |
| **Status** | Partially implemented |
| **Source** | Section 2.4 of the monolithic specification |
| **Depends on** | `01-company-and-tenancy`, `03-employees`, `04-permissions-and-roles` |
| **Depended on by** | `14-catalogue-and-inventory`, and anything that asks where somebody works |

## 1. Context / Overview

The structuring decision of this spec is one sentence: **a location is an object
owned by the company, not an attribute of an employee.**

The company defines and owns the list of its offices, with their address, country,
city and time zone, exactly as it defines its teams. An employee references an
existing office through a nullable foreign key. An address is never retyped on
each employee record, and the question "how many people are attached to the Lisbon
office" is a query rather than a text search.

```
Company
└── has many Locations       "Paris HQ", "Lisbon office", "Austin office"
└── has many Employees
    └── attached to a Location, optionally
```

This has to cover four shapes of company without forcing an unsuitable structure
on any of them.

| Situation | Offices | Active attachment | Employee country |
| --- | --- | --- | --- |
| Fully remote | none | none | always filled in |
| One office, one country | one | for those in the office, none for remote people | filled in for everybody |
| Several offices, same country | two or more | whichever office they are attached to | filled in for everybody |
| Several offices, several countries | two or more | whichever office they are attached to | filled in for everybody |

The country and the time zone of an employee are therefore carried by the
employee, not only inherited from an office. A remote employee has no office at
all and still needs both, for onboarding playbooks and for scheduling one on
ones. An employee attached to an office may take the country and time zone of
that office as a default, and the value stays overridable.

## 2. User Stories & Requirements

### Stories

**As an administrator**, I add an office by giving nothing more than its name,
its city and its country, and I fill in the address and the time zone later.

**As an administrator**, I mark one office as the head office, and the badge
moves off whichever office had it.

**As an administrator**, I close an office without erasing it, keeping everything
recorded about it and everyone who ever worked there.

**As an administrator**, I reopen an office I closed.

**As an administrator of a company with many offices**, I search them by name,
city or country, sort them, and read at a glance how many offices, countries and
time zones the company keeps.

**As a People administrator**, I move somebody from one office to another and the
previous attachment is closed rather than overwritten.

**As anybody**, I can ask an employee which offices they have worked from and
when, and ask an office who has been attached to it and for how long.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | A location belongs to exactly one company. |
| FR-02 | A location has a name, unique within the company. Country, city, address and time zone are optional. |
| FR-03 | Exactly one location of a company may be the head office. Promoting one demotes whichever held it. |
| FR-04 | A location can be archived and reopened. Archiving keeps every record attached to it. |
| FR-05 | An archived location cannot receive new attachments. |
| FR-06 | The attachment of an employee to a location is recorded in an append only history table, never updated in place. |
| FR-07 | An employee has at most one active attachment at a time. A remote employee simply has no active row. |
| FR-08 | Moving an employee closes the current row with an end date and opens a new one with a start date, in one transaction. |
| FR-09 | The history is readable from both ends: from the employee, and from the location. |
| FR-10 | The `location_id` on the employee mirrors the row where the end date is null. |
| FR-11 | Attaching an employee to a location proposes the country and time zone of that location as defaults, and both stay overridable on the employee. |
| FR-12 | Managing locations requires `company.manage` at company scope. |

## 3. Technical Specifications & Boundaries

### Data model

```
Location
- id, company_id
- name                 required, unique per company
- country              nullable, ISO 3166-1 alpha-2
- city                 nullable
- address              nullable
- timezone             nullable, falls back to the company time zone
- archived_at          nullable, null while the office is open
- is_primary           whether this is the head office
- created_at, updated_at

EmployeeLocation
- id
- employee_id, location_id
- started_at
- ended_at             nullable, null means this is the current attachment
```

### The append only history pattern

This is the first of six specs that use the same pattern (`05` to `10`). The full
reasoning is written here once and referred to from the others.

A relationship that changes over time is never updated in place. Changing it
closes the current row by setting `ended_at` and inserts a new row with
`started_at`. The active row is the one where `ended_at` is null.

Three things fall out of it for free.

1. **Nothing is lost.** A change of office does not erase where somebody used to
   work.
2. **The model is the same for the present and the past.** There is no separate
   history table shadowing a current state table, and therefore no chance of the
   two disagreeing.
3. **The question can be asked from either end.** From the employee: which
   offices have I been attached to, and when. From the office: who has been here,
   and for how long.

The cost is that every read of the current value needs a `WHERE ended_at IS NULL`
clause, which is why the current value is also denormalised onto the employee.
See `03-employees` for the rule governing those columns.

### Head office

The source document proposed a `primary_location_id` column on the company. The
implementation put an `is_primary` flag on the location instead.

This spec adopts the implementation. The company already points at the location
table through nothing, and the location already points at the company; adding a
reverse foreign key would make the two tables mutually dependent for no gain.
Uniqueness of the flag is enforced in the application, not by a partial index,
because the constraint is per company rather than global.

### Archiving

Archiving is not deleting and not soft deleting. An archived office still exists,
still appears in history, and still answers "who worked here". It is excluded
from the list of places somebody can be attached to, and shown separately in the
interface.

Deleting a location outright is possible only while no employee has ever been
attached to it.

### Events published

| Event | When |
| --- | --- |
| `location.created` | An office is added. |
| `location.archived` | An office is closed. |
| `location.reopened` | An archived office is reopened. |
| `employee.location_changed` | An employee is attached to a different office, or detached. |

`employee.location_changed` carries both the previous and the new location in its
payload, so a playbook can react to a move between two specific offices.

### Out of scope

- **Anything inside an office.** Floors, rooms, cupboards, shelves and cabinets
  are not offices and do not belong in this table. The asset module defines a
  `StorageLocation` tree hanging off an office for exactly that, in
  `14-catalogue-and-inventory`. A `Location` carries a country and a time zone
  and has employees attached to it; a shelf has neither.
- **Desk booking, meeting rooms, parking.** Facilities candidates, listed in
  `backlog/23-module-candidates`.
- **Physical access and badges.** Same.
- **Capacity or headcount limits per office.**
- **Opening hours and public holidays per office.** The time zone is recorded;
  the calendar is not.
- **Addresses as structured data.** The address is one free text field. Splitting
  it into street, postcode and region buys nothing until something parses it.

## 4. Acceptance Criteria

- [x] AC-01. An office can be created with a name, a city and a country alone.
- [x] AC-02. Two offices of the same company cannot share a name; two offices of
      different companies can.
- [x] AC-03. Promoting an office to head office removes the flag from whichever
      office held it.
- [x] AC-04. Archiving an office keeps it readable and moves it out of the list
      of open offices.
- [x] AC-05. An archived office can be reopened.
- [x] AC-06. The list of offices can be searched by name, city or country, and
      sorted by office or by city.
- [x] AC-07. The list shows how many offices, countries and time zones the
      company keeps.
- [x] AC-08. A user without `company.manage` cannot create, change or archive an
      office.
- [ ] AC-09. Attaching an employee to an office creates a history row with a
      start date and no end date.
- [ ] AC-10. Moving an employee to another office closes the previous row and
      opens a new one, and both rows survive.
- [ ] AC-11. An employee with no active row is treated as remote everywhere,
      including in the directory.
- [ ] AC-12. The `location_id` on the employee matches the active history row
      after every move.
- [ ] AC-13. An archived office is not offered when attaching an employee.
- [ ] AC-14. Attaching an employee proposes the country and time zone of the
      office and lets the employee keep their own.
- [ ] AC-15. Moving an employee publishes `employee.location_changed` carrying
      the previous and the new office.

## 5. Implementation status

The office side of this spec is complete, including a richer interface than the
source document asked for. The employee side does not exist at all.

### Already built

| Element | Where |
| --- | --- |
| `locations` table with name, country, city, address and time zone, unique per company | `database/migrations/2026_08_01_000004_create_locations_table.php` |
| `archived_at` and `is_primary` | `database/migrations/2026_08_01_235021_add_archiving_to_locations_table.php` |
| `Location` model with `company` and `isArchived()` | `app/Models/Location.php` |
| Create, update, delete, archive and restore an office | `app/Actions/CreateLocation.php`, `UpdateLocation.php`, `DestroyLocation.php`, `ArchiveLocation.php`, `RestoreLocation.php` |
| The screen listing offices, with search by name, city or country, sorting, three tabs for open, archived and all, a slide over edit panel, a head office badge, and a creation dialog asking only for name, city and country | `app/Http/Controllers/App/Settings/Administration/LocationController.php`, `LocationArchiveController.php`, `app/ViewModels/Settings/Administration/LocationsViewModel.php` |
| Counts of offices, countries and time zones | same view model |
| `LocationScopeEnum` for the three tabs | `app/Enums/LocationScopeEnum.php` |

FR-01 through FR-05 and FR-12 are satisfied. AC-01 through AC-08 are covered by
tests.

### Not built yet

| Gap | Requirement |
| --- | --- |
| No `employee_locations` table | FR-06 to FR-10, AC-09 to AC-13 |
| No `location_id` on the employee | FR-10, AC-12 |
| No action attaching an employee to an office | FR-08 |
| No inheritance of country and time zone | FR-11, AC-14 |
| No events published | AC-15 |
