# 14. Assets: catalogue and serialised inventory

| | |
| --- | --- |
| **Identifier** | `modules/assets/14-catalogue-and-inventory` |
| **Status** | Implemented, backend only |
| **Module** | Assets |
| **Level** | 1, the only asset scope committed to the first version |
| **Source** | Sections 7.1.1, 7.1.2, 7.1.3 and 7.1.8 of the monolithic specification |
| **Depends on** | `03-employees`, `04-permissions-and-roles`, `05-locations`, `11-occurrences`, `13-module-system` |
| **Depended on by** | `15-quantitative-inventory`, `16-software-licences`, `17-lifecycle-operations`, `18-self-service`, `19-playbook-integration` |

## 1. Context / Overview

The stated goal for this module is eventual feature parity with Snipe-IT. That is
ambitious and openly so, which is exactly what `core/13-module-system` says
modules are for: the core stays thin, the ambition lives in the modules.

The ambition is structured in three levels. **This spec is level 1, and level 1
is the only asset scope committed to the first version.** Levels 2 and 3 are the
trajectory towards parity, with no timetable.

### The structuring decision

Do not model the whole inventory as one generic `Equipment` or `InventoryItem`.
The families have life cycles too different to share a data model.

| Family | Nature |
| --- | --- |
| **Asset** | Serialised, individual, traceable, comes back. Checked out and checked in. |
| **Accessory** | Counted by quantity, may come back, no individual serial number tracked. |
| **Consumable** | Counted, handed out, does not come back. |
| **Component** | Installed inside an asset, never assigned to an employee directly. |
| **Licence** | Seats attributable to an employee or an asset, with expiry and renewal. |

The centre of the module is not a generic object. It is this graph:

```
AssetModel  →  Asset  →  AssetAssignment  →  Employee | Location | Asset
```

This spec covers that graph. The other families are `15-quantitative-inventory` and
`16-software-licences`.

`AssetModel` is the concept most often missing from a first attempt. Every
physical asset belongs to a model, and the model carries what they have in common:
manufacturer, category, expected lifetime. Without it, the manufacturer of forty
identical laptops is typed forty times.

## 2. User Stories & Requirements

### Stories

**As an IT administrator**, I record the equipment the company owns, each item
individually, with its serial number and its own tag.

**As an IT administrator**, I define once that "Apple MacBook Pro 14 inch M4 Pro"
is a laptop made by Apple, and every one we own refers to it.

**As an IT administrator**, I hand a laptop to somebody, recording the state it
was in and when I expect it back.

**As an IT administrator**, I take a laptop back, recording the state it came
back in, and it returns to stock.

**As an IT administrator**, I assign equipment to an office rather than a person,
because a meeting room display belongs to the room.

**As an IT administrator**, I say what state a piece of equipment is in, ready to
deploy, awaiting repair, lost, retired, separately from who currently holds it.

**As an IT administrator**, I look at an asset and see everybody who has had it,
in what state, and when it was due back.

**As an IT administrator**, I look at a colleague and see everything assigned to
them.

**As an IT administrator**, an item that is out with somebody cannot be handed to
somebody else by mistake.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | Every catalogue object and every asset belongs to exactly one company. |
| FR-02 | A category groups assets and carries the rules that apply to the whole family: whether acceptance is required, the text to accept, whether a checkout sends an email. |
| FR-03 | A manufacturer is who makes the equipment. A supplier is who sells it. They are separate entities. |
| FR-04 | An asset model belongs to a manufacturer and a category, and carries the attributes shared by every asset of that model. |
| FR-05 | Every asset belongs to exactly one asset model. |
| FR-06 | An asset has an asset tag, the internal identifier of the company, unique within it, and separately a serial number, which comes from the manufacturer. |
| FR-07 | An asset status describes the operational state and has a system type: deployable, pending, undeployable, archived. |
| FR-08 | Being assigned is not a status. It is derived from an active assignment. |
| FR-08b | An asset holding an active assignment reads as Deployed everywhere it is displayed or counted, without that being stored on the asset. |
| FR-09 | A company may add its own statuses on top of the system ones. |
| FR-10 | An asset has a default location and a current location. |
| FR-11 | Assignment is recorded in an append only history table, never updated in place. |
| FR-12 | An asset may be assigned to an employee, to a location, or to another asset. |
| FR-13 | An asset has at most one active assignment at a time. |
| FR-14 | Checkout is a business operation, not a field update. It validates that the asset is deployable and unassigned, creates the assignment, updates the location, publishes an event, requests acceptance when the category requires it, and writes to the audit log. |
| FR-15 | Checkin is a business operation. It closes the active assignment, records the returned condition, updates the status, publishes an event, and writes to the audit log. |
| FR-16 | Checkout records the expected return date, the condition at checkout and free text notes. Checkin records the condition at checkin, the location it returned to, and notes. |
| FR-17 | Checking out an asset that is not deployable, or already assigned, is refused. |
| FR-18 | The history is readable from both ends: from the asset and from whoever held it. |
| FR-19 | An asset can be archived, keeping its history. |
| FR-20 | Managing the catalogue and assets requires the permissions declared by the module. |
| FR-21 | *(Level 2)* A storage location is a place inside an office: a floor, a room, a cupboard, a shelf, a cabinet, a locker. |
| FR-22 | *(Level 2)* A storage location belongs to exactly one office and may have a parent storage location, forming a tree of any depth including none. |
| FR-23 | *(Level 2)* The parent chain of a storage location may not contain a cycle. |
| FR-24 | *(Level 2)* A storage location can be archived, keeping every record of what was in it. |
| FR-25 | *(Level 2)* An asset in stock records the exact storage location it sits in. An asset checked out to somebody records none. |
| FR-26 | *(Level 2)* Checking an asset in records where it was put back, at storage location precision. |
| FR-27 | *(Level 2)* An asset may be assigned to a storage location, which is distinct from being stored in one: an assigned asset is not available, a stored one is. |

## 3. Technical Specifications & Boundaries

### The catalogue

Reference objects, defined once per company and reused throughout the module,
the same way teams and job titles are. See `06-teams` and `07-job-titles`.

```
AssetCategory
- id, company_id
- name                      "Laptops", "Phones", "Displays", "Security badges"
- type
- requires_acceptance       whether the holder must accept the terms
- eula_text                 the terms to accept
- send_checkout_email

Manufacturer
- id, company_id
- name                      "Apple", "Dell", "Lenovo"
- website_url, support_url, support_email, support_phone
- notes

AssetModel
- id, company_id
- manufacturer_id, asset_category_id
- name                      "Apple MacBook Pro 14-inch M4 Pro"
- model_number
- image_path
- useful_life_months
- is_requestable
- notes

AssetStatus
- id, company_id            null for a status every company gets
- key                       what the code recognises a system status by, null for a company one
- name
- type                      deployable | pending | undeployable | archived
- color
- is_system
```

`AssetCategory.type` is one of the five families of section 7.1.1: `asset`,
`accessory`, `consumable`, `component`, `licence`. Only `asset` can be recorded
against today, and the other four are declared now so that a company laying out
its catalogue does not have to migrate it when the rest arrives. A model may not
be filed under a category of a family nothing is built for.

`AssetStatus.key` is what lets the code recognise the handful of statuses it
branches on by name. Today that is `lost` and nothing else, and the list is meant
to stay short. It is null for every status a company adds.

The five statuses every company gets, inserted once for the installation as
shared rows with no company of their own:

| key | name | type |
| --- | --- | --- |
| `ready_to_deploy` | Ready to deploy | `deployable` |
| `pending` | Pending | `pending` |
| `awaiting_repair` | Awaiting repair | `undeployable` |
| `lost` | Lost | `undeployable` |
| `retired` | Retired | `archived` |

### Storage locations (level 2)

An office answers where an asset is to within a building. That is enough to hand
equipment out and get it back, which is why level 1 stops there. It is not enough
to find a thing.

`StorageLocation` is a place inside an office: a floor, a room, a store cupboard,
a shelf, a network cabinet, a locker. It is a self referencing tree, so a company
records as much or as little depth as it keeps.

```
Montréal Office
└── 3rd floor
    ├── IT store
    │   ├── Shelf A
    │   └── Shelf B
    ├── Network room
    │   └── Cabinet 02
    └── Office 3-142
```

```
StorageLocation
- id, company_id
- location_id              the office it is inside, required
- parent_storage_location_id   nullable, FK to StorageLocation
- name                     "IT store", "Shelf A", "Cabinet 02"
- type                     floor | room | storage | shelf | cabinet | locker | other
- description              nullable
- archived_at              nullable, null while it is in use
```

Three decisions inside that, each of which could reasonably have gone the other
way.

**It is a separate entity, not `Location` with a parent.** Making offices and
shelves one self referencing tree, the way `06-teams` does for teams and
departments, is the obvious move and it is wrong here. A `Location` carries a
country, a city and a time zone, and employees attach to it through
`EmployeeLocation`. A shelf has none of those and no employee is attached to one.
Merging them means every query about where somebody works has to exclude the
furniture.

**It is called `StorageLocation`, not `AssetLocation`.** `EmployeeLocation` in
`05-locations` is the history of which office an employee is attached to.
`AssetLocation` would read as the same construct for assets, which is exactly
what this is not.

**It uses `archived_at`, not `is_active`.** Matching `locations`, where the same
question was already answered. A store cupboard that stops being used still has
to answer what was in it.

The parent chain may not contain a cycle, checked in the application on every
write, the same way `06-teams` checks team parents.

### Where an asset is

Two columns rather than one, because they answer different questions.

```
Asset
- default_location_id           the office it belongs to
- current_location_id           the office it is in now
- current_storage_location_id   nullable, level 2, the exact spot inside it
```

An asset checked out to somebody has no meaningful storage location and the
column is null. An asset in stock has one, and that is what makes an audit worth
running.

`AssetAssignment` gains `storage_location` as a fourth assignee type at level 2,
alongside employee, location and asset. A display bolted to the wall of the
Renoir meeting room is assigned to that room, not stored in it, and the
distinction is real: an assigned asset is not available, a stored one is.

`AssetStatus` follows the same shape as the lifecycle status in
`08-employee-lifecycle-status`: a fixed system list with a `type` that drives
behaviour, plus whatever the company adds. The `type` is what the code branches
on. The name is what people read. That is why a company can add "Awaiting repair"
without the code learning about it: it declares itself as `undeployable` and
behaves accordingly.

### Why AssetStatus is a table and PermissionEnum is not

The two sit next to each other in the codebase and are modelled opposite ways, so
the reason is worth stating.

A permission is defined by the code that checks it. A permission row nothing
checks grants nothing, and a permission checked in code with no row would crash.
There is no version of it that a company can meaningfully edit, which is why
`04-permissions-and-roles` makes it an enum.

A status is different, and the split runs through the middle of it. The `type`
is code: four closed values, and the code branches on them. The row is company
data: a label a company puts on its own equipment, over one of those four types.
"In transit", "Awaiting wipe" and "Pending disposal" are real statuses that no
product should have to ship in advance, and they behave correctly without the
code learning about them because each declares its type.

The `key` column is the seam between the two. It is set on the handful of system
rows the code has to recognise by name, which today is `lost` and nothing else,
and null on everything a company adds. It is deliberately small: a status needing
a key is a status the code branches on, and that list should stay short.

### Deployed is shown, not stored

FR-08 says being assigned is not a status. That is a statement about the
database, and on its own it produces a bad answer to a fair question: somebody
looking at a list of equipment expects to read "Deployed", and should not have to
know that it is computed.

So the module exposes a display status, derived on read: an asset with an active
assignment reads as **Deployed**, and every other asset reads as the name of its
`AssetStatus`. Lists, counts, exports and screens all read that one value.

It is derived rather than stored because `AssetAssignment` already answers the
question definitively. A `deployed` row in `AssetStatus` would be a second source
of truth for the same fact, and the two can disagree: somebody sets an asset back
to "Ready to deploy" while the assignment is still open, and the inventory starts
lying while the checkout guard, which reads the assignment, keeps working. It
would also force checkin to remember which status to restore, since an asset
checked out from "Pending" should not come back as "Ready to deploy".

Two fields on `AssetModel` are deferred to later levels and named here so the
column order does not have to change: `depreciation_method_id` (level 3, see
`18-self-service`) and `fieldset_id` (level 2, see
`17-lifecycle-operations`).

### The asset

```
Asset
- id, company_id
- asset_model_id
- asset_tag                 internal identifier, unique per company, "OL-LAPTOP-0042"
- serial_number             from the manufacturer
- name
- status_id
- default_location_id       the office it lives in when nobody has it
- current_location_id       the office it is in now
- current_storage_location_id  nullable, level 2, the exact spot inside that office
- supplier_id               nullable, see 17
- purchase_date, purchase_cost, order_number
- warranty_expires_at, end_of_life_at
- is_byod                   owned by the employee, not the company
- is_requestable
- notes
- created_at, updated_at, archived_at
```

Two identifiers, deliberately. The asset tag is what the company writes on the
label and controls. The serial number is what the manufacturer stamped on it and
nobody controls. Conflating them means either trusting a manufacturer to be
unique across vendors, or being unable to label a machine whose serial number is
unreadable.

### Assignment

```
AssetAssignment
- id, asset_id
- assignee_type             employee | location | asset | storage_location (level 2)
- assignee_id
- assigned_by_user_id
- assigned_at
- expected_return_at        nullable
- returned_at               nullable, null means the assignment is active
- returned_to_location_id   nullable
- checkout_notes, checkin_notes
- condition_at_checkout, condition_at_checkin
- overdue_notified_at       when it was flagged late, so it is flagged once
```

The two conditions are a closed list, `new`, `good`, `fair`, `poor`, `damaged`,
rather than free text, because "what state did it come back in" has to be
answerable across a fleet rather than one item at a time.

`overdue_notified_at` exists so that the daily check flags an assignment once per
crossing rather than every day the condition holds. Without it, equipment four
months late would say so a hundred and twenty times.

Same append only pattern as `05-locations`, where the full reasoning is written.

A single `employee_id` column on the asset would answer "who has it now" and
nothing else. This table answers four questions: who has it, who had it before,
in what condition each time, and when it was supposed to come back.

The assignee is polymorphic across three types, the same technique
`11-occurrences` uses for subjects. A display assigned to a meeting room, a
docking station assigned to a laptop, and a laptop assigned to a person are the
same operation against different targets.

Equipment holding equipment may not close on itself. The chain is walked upward
on every checkout, and meeting the asset being handed over anywhere in it is
refused, the same application level check `06-teams` uses for team parents.

### Checkout and checkin as operations

These are the two operations that make the module more than a spreadsheet, and
they are specified as sequences rather than as updates.

**CheckoutAsset**

1. Validate that the status type is `deployable`.
2. Validate that no assignment is active.
3. Create the `AssetAssignment` with the condition and the expected return date.
4. Update `current_location_id`.
5. Publish `asset.checked_out`.
6. Request acceptance if the category requires it.
7. Write to the audit log.

Step 4 takes the office as a parameter rather than working it out. An employee
has no office to read it from yet: `05-locations` has the company side built and
the `EmployeeLocation` side unbuilt. Whoever hands the equipment over says where
it is going, and a checkout that says nothing leaves the equipment where it was.
It defaults from the employee once that half of `05-locations` ships, which is an
addition rather than a change.

Step 6 does nothing at this level. Acceptance is level 3, in `18-self-service`.
The category flag is stored and read by nothing yet, which is recorded in the
action rather than silently left out.

Step 7 writes to the log of user actions for now. The audit log of
`04-permissions-and-roles` does not exist, and its first real consumer is
compensation and permission changes rather than this module, so building it here
would design it around the wrong caller.

**CheckinAsset**

1. Close the active `AssetAssignment`, setting `returned_at` and the condition.
2. Record the location it returned to.
3. Update the status.
4. Publish `asset.checked_in`.
5. Write to the audit log.

Step 3 happens only when a status is passed in, which is how something returned
damaged goes to Awaiting repair. Nothing is set automatically and there is
nothing to put back, because checkout never changed the status.

Steps 1 and 2 of checkout are the ones people skip. Without them an asset can be
handed to two people at once, or handed out while it is in for repair, and the
inventory quietly stops describing reality.

### Permissions declared by this module

Per `13-module-system`, a module declares its own permissions.

| Permission | |
| --- | --- |
| `asset.view` | See the inventory. |
| `asset.manage` | Create, change and archive assets and the catalogue. |
| `asset.checkout` | Hand equipment out and take it back. |

`asset.view` targets an asset rather than an employee, so the scopes of
`04-permissions-and-roles` do not apply to it directly. It is granted at company
scope only, until there is a reason for anything narrower.

The IT/Workplace administrator role named in the source document is created when
this module ships, holding all three.

### Events published

| Event | When |
| --- | --- |
| `asset.checked_out` | An asset is assigned. |
| `asset.checked_in` | An assignment is closed. |
| `asset.return_overdue` | An expected return date passes with the assignment still open. |
| `asset.reported_lost` | An asset moves to a status meaning lost. |

The rest of the module catalogue is declared by the specs that own those
features. See `19-playbook-integration` for the full list.

### Out of scope for level 1

Everything below is named so that it is clear it was considered and deferred, not
forgotten.

- Storage locations, specified above and deliberately held at level 2. Level 1
  records the office an asset is in and nothing finer. The `Asset` column and the
  fourth assignee type are named above so that adding them later is an addition
  rather than a restructuring.
- Suppliers, accessories, consumables, components. Level 2, see
  `15-quantitative-inventory` and `17-lifecycle-operations`.
- Software licences. Level 2, see `16-software-licences`.
- Maintenance, audits, attachments, custom fields. Level 2, see
  `17-lifecycle-operations`.
- Labels, QR codes, barcodes, bulk import and export, bulk actions. Level 2.
- Requests, reservations, acceptance with signatures, depreciation. Level 3, see
  `18-self-service`.
- Depreciation and financial reporting. Level 3.

## 4. Acceptance Criteria

- [x] AC-01. A category, a manufacturer and an asset model can be created, and an
      asset requires a model.
- [x] AC-02. Two assets of the same company cannot share an asset tag; two assets
      of different companies can.
- [x] AC-03. An asset can be created with no serial number.
- [x] AC-04. Every company has the system asset statuses, and a company can add
      its own declaring one of the four types.
- [x] AC-05. An asset with an active assignment reads as Deployed while the
      `AssetStatus` stored against it is unchanged, and reads as that status
      again once the assignment is closed.
- [x] AC-06. Checking out a deployable, unassigned asset creates an assignment
      and updates the current location.
- [x] AC-07. Checking out an asset that is already assigned is refused.
- [x] AC-08. Checking out an asset whose status type is not deployable is
      refused.
- [x] AC-09. Checking in closes the assignment, records the returned condition
      and the location, and leaves the row in place.
- [x] AC-10. An asset can be assigned to a location and to another asset, not
      only to an employee.
- [x] AC-11. Asking an asset for its assignments returns every holder with the
      conditions and dates. Asking an employee returns everything they hold and
      have held.
- [x] AC-12. An assignment whose expected return date has passed while still open
      publishes `asset.return_overdue` once.
- [x] AC-13. Checkout publishes `asset.checked_out`; checkin publishes
      `asset.checked_in`.
- [x] AC-14. Checkout and checkin each write an audit log entry naming who did
      it. *(Met by the log of user actions. The audit log of
      `04-permissions-and-roles` does not exist yet.)*
- [x] AC-15. A user without `asset.checkout` cannot hand equipment out.
- [x] AC-16. Archiving an asset keeps its assignment history readable.
- [x] AC-17a. Every permission of this module denies while the module is
      disabled, the owner of the company included.
- [ ] AC-17b. Every screen of this module is unavailable while the module is
      disabled. *(There are no screens yet.)*
- [ ] AC-18. *(Level 2)* A storage location can be created with a name, a type
      and an office, and with no parent.
- [ ] AC-19. *(Level 2)* A storage location can be nested several levels deep,
      and a parent that would create a cycle is refused.
- [ ] AC-20. *(Level 2)* Archiving a storage location keeps the record of what
      was stored in it and removes it from the list of places to put things.
- [ ] AC-21. *(Level 2)* Checking an asset in records the storage location it was
      put back into, and checking it out clears it.
- [ ] AC-22. *(Level 2)* Asking a storage location returns everything currently
      in it, and asking one with children returns what is in the whole subtree.
- [ ] AC-23. *(Level 2)* An asset assigned to a storage location is unavailable;
      an asset merely stored in one is available.

## 5. Implementation status

Built, apart from the screens. Every requirement of level 1 is satisfied and
every acceptance criterion from AC-01 to AC-16 passes, with the two annotations
noted against AC-14 and AC-17.

### Already built

| Element | Where |
| --- | --- |
| `manufacturers`, `asset_categories`, `asset_models`, `asset_statuses`, `assets`, `asset_assignments` | `database/migrations/2026_08_03_0000{02..07}_*.php` |
| The five statuses every company gets, inserted once for the installation | `2026_08_03_000005_create_asset_statuses_table.php` |
| `Manufacturer`, `AssetCategory`, `AssetModel`, `AssetStatus`, `Asset`, `AssetAssignment` and their factories | `app/Models/`, `database/factories/` |
| `AssetCategoryTypeEnum`, `AssetStatusTypeEnum`, `AssetAssigneeTypeEnum`, `AssetConditionEnum` | `app/Enums/` |
| The derived display status, FR-08b | `Asset::displayStatus()` |
| Twelve catalogue actions, create, change and delete for each of the four | `app/Actions/` |
| Five asset actions: create, change, archive, restore, delete | `app/Actions/` |
| Checkout and checkin, with every validation of section 3 | `app/Actions/CheckoutAsset.php`, `CheckinAsset.php` |
| The history read from both ends | `Asset::assignments()`, `Employee::assetAssignments()`, `Location::assetAssignments()` |
| The three permissions, and the module gate that denies them while the module is off | `app/Enums/PermissionEnum.php`, `app/Permissions/PendingPermissionCheck.php` |
| The IT administrator role, and the grants added to Administrator and Member | `app/Actions/CreateDefaultRoles.php`, plus a migration for companies that already exist |
| The daily overdue check | `app/Jobs/CheckOverdueAssetReturns.php`, scheduled in `routes/console.php` |
| Around 140 tests across models, actions, enums and the command | `tests/Unit/` |

The three things this spec said had to exist first were built alongside it:
`13-module-system` at the depth described there, `11-occurrences` as a log
nothing consumes yet, and the company half of `05-locations` which was already
there.

### Not built

| Gap | Why |
| --- | --- |
| Every screen, controller, route and view model | Deliberately out of scope. The data model and the actions come first. |
| The audit log proper | `04-permissions-and-roles`. Its first real consumer is compensation and permission changes, so building it here would design it around the wrong caller. AC-14 is met by the log of user actions in the meantime. |
| Acceptance on checkout, step 6 | Level 3, `18-self-service`. The category flag is stored and read by nothing. |
| The office an employee works from, as a default for checkout | The `EmployeeLocation` half of `05-locations`. The office is passed in explicitly until then. |
| Suppliers, storage locations, and the rest of levels 2 and 3 | As listed under **Out of scope for level 1**. |
