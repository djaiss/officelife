# 17. Assets: lifecycle operations and extensibility

| | |
| --- | --- |
| **Identifier** | `modules/assets/17-lifecycle-operations` |
| **Status** | Specified |
| **Module** | Assets |
| **Level** | 2, not committed to the first version |
| **Source** | Sections 7.1.2, 7.1.3 and 7.1.6 of the monolithic specification |
| **Depends on** | `14-catalogue-and-inventory` |
| **Depended on by** | `18-self-service`, `19-playbook-integration` |

## 1. Context / Overview

Level 1 records what the company owns and who has it. This spec covers what
happens to a piece of equipment between being bought and being retired, plus the
extensibility that makes the module usable across categories that have nothing in
common.

Five things, grouped here because they share a level and a shape rather than a
theme.

**Suppliers.** Who sold us the thing, as distinct from who made it.

**Maintenance.** Repairs, upgrades, inspections, cleaning. A machine in for repair
is not lost and not deployable, and the record of why matters.

**Audits.** Checking that the asset exists and is where the system claims. The
inventory is a claim about the physical world and the claim decays.

**Attachments.** Invoices, photographs, repair reports.

**Custom fields.** An IMEI on a phone, a MAC address on a laptop, a BIOS version
on a workstation. The attributes vary too much between categories to be
anticipated as fixed columns.

## 2. User Stories & Requirements

### Stories

**As an IT administrator**, I record who we buy from, separately from who
manufactures, because the support number differs.

**As an IT administrator**, I send a laptop for repair and record what was wrong,
what it cost and how long it was away.

**As an IT administrator**, I run an inventory check and record which items I
actually laid eyes on and which I could not find.

**As an IT administrator**, I attach the invoice to the asset so that the
warranty claim does not depend on somebody's inbox.

**As an IT administrator**, I record the IMEI on phones without that field
appearing on displays and keyboards.

**As an IT administrator**, sensitive identifiers I record are not readable to
everybody who can see the inventory.

**As an IT administrator**, I import the inventory I already keep in a
spreadsheet rather than typing three hundred rows.

**As an IT administrator**, I print labels with a code I can scan, so that an
audit is a walk around the office rather than an afternoon.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | A supplier belongs to one company and is distinct from a manufacturer. |
| FR-02 | An asset, an accessory, a consumable, a component and a licence may each reference a supplier. |
| FR-03 | A maintenance record belongs to one asset and has a type: maintenance, repair, upgrade, inspection, cleaning. |
| FR-04 | A maintenance record holds a supplier, a start date, an expected and an actual completion date, a cost and notes. |
| FR-05 | An asset under maintenance is not deployable. |
| FR-06 | An audit records that a given asset was verified, by whom, when, and whether it was found. |
| FR-07 | An audit records where it was found, at storage location precision, which may differ from where it was expected. |
| FR-08 | An asset carries a next audit date, and publishes an event when that date passes. |
| FR-09 | An attachment belongs to an asset and holds a file, a name and a type. |
| FR-10 | A custom field has a type, a name and validation rules, and may be marked encrypted. |
| FR-11 | Custom fields are grouped into fieldsets, and a fieldset is attached to an asset model. |
| FR-12 | An encrypted custom field is stored encrypted and readable only with `asset.manage`. |
| FR-13 | Assets and catalogue objects can be imported from and exported to CSV. |
| FR-14 | An asset can produce a label carrying a scannable code resolving to its record. |
| FR-15 | Checkout, checkin and label printing can be performed on a selection of assets at once. |

## 3. Technical Specifications & Boundaries

### Data model

```
Supplier
- id, company_id
- name                     who sells, distinct from who manufactures
- contact_name, email, phone, website_url, address
- notes

AssetMaintenance
- id, asset_id
- type                     maintenance | repair | upgrade | inspection | cleaning
- supplier_id              nullable
- title, notes
- started_at
- expected_completion_at   nullable
- completed_at             nullable
- cost                     nullable
- created_by_user_id

AssetAudit
- id, asset_id
- audited_by_user_id
- audited_at
- was_found                boolean
- expected_location_id     the office the system said it would be in
- expected_storage_location_id  nullable, the exact spot it claimed
- found_location_id        nullable, the office it actually was in
- found_storage_location_id     nullable, the exact spot it actually was in
- notes

AssetAttachment
- id, asset_id
- file_path, original_name, mime_type, size
- type                     invoice | photo | repair_report | other
- uploaded_by_user_id
- uploaded_at

CustomField
- id, company_id
- name                     "IMEI", "MAC address"
- type                     text | number | date | boolean | list
- options                  JSON, for the list type
- is_encrypted
- is_required
- help_text

CustomFieldset
- id, company_id
- name                     "Phone attributes"

CustomFieldsetField
- id, custom_fieldset_id, custom_field_id
- position

AssetCustomFieldValue
- id, asset_id, custom_field_id
- value                    encrypted when the field is
```

### Manufacturer and supplier

Two entities because they answer different questions. Apple makes the laptop; a
reseller sold it and holds the invoice. The support number for a hardware fault is
the manufacturer's; the number for a billing dispute is the supplier's. A company
that buys direct has the same organisation in both tables, which costs one row.

### Maintenance and status

An asset going in for maintenance moves to a status whose type is `undeployable`,
which prevents checkout through the rule already in `14-catalogue-and-inventory`.
It returns to a deployable status when the maintenance completes.

The status change is a consequence of the maintenance record, written in the same
transaction. Nothing lets an asset be under repair and deployable at once.

### Audits

An audit is a statement that somebody physically verified an asset at a moment in
time. It is not a correction: if the asset was found somewhere unexpected, the
audit records both the expected and the found location, and updating the current
location is a separate deliberate act.

This is where storage locations earn their place. An audit comparing "expected:
Montréal Office" against "found: Montréal Office" says almost nothing, since the
building is where somebody is standing while auditing. An audit comparing
"expected: IT store, shelf A" against "found: office 3-142" is the actual finding.
The storage location is specified in `14-catalogue-and-inventory` and this is the
feature that justifies it.

That separation matters. An audit that silently rewrites the inventory destroys
the evidence that the inventory was wrong, which is the only thing an audit
produces.

`asset.audit_due` fires from a scheduled check against the next audit date, which
makes it the one event in the module not caused by somebody doing something.

### Custom fields

Fields are defined once per company, grouped into fieldsets, and a fieldset is
attached to an asset model. Every asset of that model then carries those fields.

The alternative, attaching fields directly to categories, was considered and is
worse: two models in the same category often differ in exactly the attributes
worth recording.

`is_encrypted` is recommended for anything that identifies a device on a network
or unlocks it. The encryption is at rest, and reading the value requires
`asset.manage` rather than `asset.view`. A module that stores MAC addresses and
BIOS passwords in plain text next to a list of who holds each machine is a
liability.

### Labels and codes

A label carries the asset tag as text and as a scannable code, resolving to the
record of that asset. The tag is already unique per company, so nothing new is
generated.

Import and export cover assets and every catalogue object. Import matches on the
asset tag, updating rather than duplicating when it already exists, and reports
per row rather than failing the whole file.

### Events published

| Event | When |
| --- | --- |
| `asset.maintenance_started` | An asset goes in for work. |
| `asset.maintenance_completed` | It comes back. |
| `asset.audit_due` | The next audit date passes. |
| `asset.audited` | An audit is recorded. |
| `asset.not_found` | An audit records that an asset could not be found. |

`asset.warranty_expiring`, `asset.warranty_expired`,
`asset.end_of_life_approaching` and `asset.end_of_life_reached` are published from
the same scheduled check, against the dates already on the asset in
`14-catalogue-and-inventory`.

### Out of scope

- **Automated discovery.** Nothing scans a network or reads a device. Every value
  here is recorded by a person or imported.
- **Maintenance scheduling and preventive plans.** A maintenance record is
  created when work happens. There is no recurring maintenance schedule.
- **Warranty claims.** The dates are recorded and warned about. Claiming is done
  with the manufacturer.
- **Custom fields anywhere but on assets.** Not on employees, not on accessories.
- **Optical character recognition on invoices.**

## 4. Acceptance Criteria

- [ ] AC-01. A supplier can be created and referenced by an asset, and is
      distinct from the manufacturer of that asset.
- [ ] AC-02. Recording maintenance moves the asset to an undeployable status and
      checkout is refused while it is there.
- [ ] AC-03. Completing maintenance returns the asset to a deployable status.
- [ ] AC-04. An audit records who verified the asset, when, whether it was found
      and where.
- [ ] AC-05. An audit finding an asset in an unexpected location does not change
      the current location on its own, at office or at storage location
      precision.
- [ ] AC-06. An asset whose next audit date has passed publishes
      `asset.audit_due`.
- [ ] AC-07. An attachment can be uploaded, listed and deleted, and its file is
      not publicly reachable.
- [ ] AC-08. A custom field can be created, added to a fieldset, and the fieldset
      attached to an asset model.
- [ ] AC-09. Every asset of a model carrying a fieldset shows those fields, and
      assets of other models do not.
- [ ] AC-10. An encrypted custom field is unreadable in the database and is
      hidden from a user holding only `asset.view`.
- [ ] AC-11. A required custom field blocks saving an asset when empty.
- [ ] AC-12. Importing a CSV creates assets, updates those whose tag already
      exists, and reports errors per row without failing the whole file.
- [ ] AC-13. Exporting produces a file that reimports without change.
- [ ] AC-14. A label renders with a scannable code resolving to the asset.
- [ ] AC-15. Checking out a selection of assets to one employee creates one
      assignment each.

## 5. Implementation status

Nothing in this spec exists. It is level 2 and therefore not committed to the
first version.

The two foreign keys it introduces into level 1 (`supplier_id` on the asset,
`fieldset_id` on the asset model) are named in `14-catalogue-and-inventory` so
that adding them later is a column addition rather than a restructuring.
