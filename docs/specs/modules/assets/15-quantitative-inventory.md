# 15. Assets: quantitative inventory

| | |
| --- | --- |
| **Identifier** | `modules/assets/15-quantitative-inventory` |
| **Status** | Specified |
| **Module** | Assets |
| **Level** | 2, not committed to the first version |
| **Source** | Section 7.1.4 of the monolithic specification |
| **Depends on** | `14-catalogue-and-inventory` |
| **Depended on by** | `19-playbook-integration` |

## 1. Context / Overview

Three families of stock that are counted rather than individually tracked. No
serial number is followed for any of them, and that single fact is what separates
them from the assets in `14-catalogue-and-inventory`.

| Family | Comes back | Assigned to |
| --- | --- | --- |
| **Accessory** | Yes | An employee. A mouse, a keyboard, a bag. |
| **Consumable** | No | An employee. Cartridges, batteries. |
| **Component** | Not really | An asset, never an employee directly. Memory, a drive. |

Trying to fold these into the `Asset` table is the mistake this spec exists to
prevent. An asset answers "where is item 0042". A consumable answers "how many
are left". Those are different questions and they want different tables.

The three share one mechanic worth building once: a minimum quantity, an
available quantity, and an event when the second drops below the first.

## 2. User Stories & Requirements

### Stories

**As an IT administrator**, I record that we have forty USB C hubs without
recording forty individual items.

**As an IT administrator**, I hand a hub to somebody and the available count
drops by one.

**As an IT administrator**, I take a hub back and the count goes up again.

**As an IT administrator**, I hand out a toner cartridge and it never comes back,
and I do not want the system asking me when it is due.

**As an IT administrator**, I record that a memory module is installed inside a
particular laptop, not that somebody is carrying it around.

**As an IT administrator**, I am told when stock drops below the level I set, so
that I reorder before somebody arrives to nothing.

**As an IT administrator**, I look at a colleague and see the accessories they
hold and the consumables they have been given.

### Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | An accessory, a consumable and a component each belong to one company and one category. |
| FR-02 | Each holds a total quantity and a minimum quantity. |
| FR-03 | The available quantity is derived from the total minus what is currently out, not stored independently. |
| FR-04 | An accessory can be assigned to an employee and returned. |
| FR-05 | A consumable is distributed to an employee and never returned. |
| FR-06 | A component is installed in an asset and uninstalled from it. It is never assigned to an employee. |
| FR-07 | Assignment, distribution and installation are each recorded in their own append only table. |
| FR-08 | Handing out more than the available quantity is refused. |
| FR-09 | When the available quantity of an accessory or a component drops below its minimum, or when the remaining quantity of a consumable does, an event is published once per crossing. |
| FR-10 | Stock can be adjusted up or down, with the adjustment recorded. |
| FR-11 | The history is readable from both ends: from the item and from the employee or asset. |

## 3. Technical Specifications & Boundaries

### Data model

```
Accessory
- id, company_id
- asset_category_id, manufacturer_id, supplier_id
- name, model_number, image_path
- total_quantity, minimum_quantity
- purchase_date, purchase_cost
- notes

AccessoryAssignment
- id, accessory_id
- employee_id
- quantity
- assigned_by_user_id
- assigned_at
- returned_at              nullable, null means still out

Consumable
- id, company_id
- asset_category_id, manufacturer_id, supplier_id
- name, model_number
- total_quantity, minimum_quantity
- purchase_date, purchase_cost
- notes

ConsumableDistribution
- id, consumable_id
- employee_id
- quantity
- distributed_by_user_id
- distributed_at
                           no return column: it does not come back

Component
- id, company_id
- asset_category_id, manufacturer_id, supplier_id
- name, serial_number      nullable, the batch rather than the unit
- total_quantity, minimum_quantity
- purchase_date, purchase_cost

ComponentInstallation
- id, component_id
- asset_id                 installed into an asset, never an employee
- quantity
- installed_by_user_id
- installed_at
- removed_at               nullable
```

### Derived availability

`available_quantity` is computed, not stored:

```
accessory   total_quantity - sum(quantity of assignments where returned_at is null)
consumable  total_quantity - sum(quantity of every distribution)
component   total_quantity - sum(quantity of installations where removed_at is null)
```

Storing it as a column and keeping it in step with the movement tables is the
classic way to end up with a count nobody believes. It is computed, and if it
becomes a performance problem it is cached, not duplicated.

The consumable case is the one that looks wrong and is not. A distribution is
never reversed, so the total only ever goes down until somebody buys more, which
is a stock adjustment.

### Stock adjustments

Buying more, writing off a broken batch, or correcting a miscount all change
`total_quantity`. Each is recorded so that a count that changes without a
movement can be explained.

```
StockAdjustment
- id
- adjustable_type          accessory | consumable | component
- adjustable_id
- quantity_change          positive or negative
- reason
- adjusted_by_user_id
- adjusted_at
```

### Low stock events

An event fires once per crossing of the threshold, not on every movement below
it. Handing out the tenth of ten hubs when the minimum is five fires nothing;
handing out the sixth fires once; handing out the seventh fires nothing more.

Implemented by comparing availability before and after each movement, rather than
by a scheduled check, so the event carries the movement that caused it.

| Event | When |
| --- | --- |
| `accessory.stock_low` | Availability crosses below the minimum. |
| `consumable.stock_low` | Same. |
| `component.stock_low` | Same. |
| `accessory.assigned` / `accessory.returned` | An accessory goes out or comes back. |
| `consumable.distributed` | A consumable is handed out. |
| `component.installed` / `component.removed` | A component goes into or out of an asset. |

### Why three tables and not one

An honest counter argument exists: the three shapes are similar enough that one
`StockItem` table with a `type` column would work.

It is rejected for one reason. The movement tables genuinely differ. An accessory
movement has a return date, a consumable movement cannot have one, and a component
movement points at an asset rather than an employee. Unifying the item tables
while keeping three movement tables buys almost nothing, and unifying the movement
tables means a nullable return date on a row where returning is meaningless, plus
a nullable employee and a nullable asset on every row.

### Out of scope

- **Serial numbers per unit.** By definition. An accessory whose individual units
  need tracking is an asset, and should be modelled as one.
- **Reordering and purchase orders.** A finance candidate, see
  `backlog/23-module-candidates`.
- **Stock per location.** Quantities are company wide in the first version of
  this level. A company with three offices holding separate stock will want this,
  and it is a real gap, deliberately deferred. When it is closed, it hangs off
  the storage locations in `14-catalogue-and-inventory` rather than off offices,
  since "forty hubs in the Montréal IT store, shelf B" is the answer somebody
  actually needs.
- **Kits and bundles.** No "onboarding pack" grouping a hub, a keyboard and a
  bag.

## 4. Acceptance Criteria

- [ ] AC-01. An accessory, a consumable and a component can each be created with
      a name, a category and a quantity.
- [ ] AC-02. Availability is total minus what is out, for each of the three, and
      is never stored as a column.
- [ ] AC-03. Assigning an accessory reduces availability; returning it restores
      it.
- [ ] AC-04. Distributing a consumable reduces availability permanently, and
      there is no way to return one.
- [ ] AC-05. Installing a component into an asset reduces availability; removing
      it restores it.
- [ ] AC-06. A component cannot be assigned to an employee.
- [ ] AC-07. Handing out more than is available is refused.
- [ ] AC-08. Crossing below the minimum publishes the matching low stock event
      exactly once, and further movements below it publish nothing.
- [ ] AC-09. Rising back above the minimum and crossing down again publishes it a
      second time.
- [ ] AC-10. A stock adjustment changes the total and is recorded with its
      reason and its author.
- [ ] AC-11. Asking an employee returns the accessories they hold and the
      consumables they have received.
- [ ] AC-12. Asking an asset returns the components installed in it, past and
      present.

## 5. Implementation status

Nothing in this spec exists. It is level 2 and therefore not committed to the
first version.

It depends on `14-catalogue-and-inventory` for categories, manufacturers and suppliers, and
should not be started before that ships.
