---
description: Adds a new model to the Laravel application. Use when the user wants to create a new Eloquent model, including its migration, factory, and seeder. Triggers on model creation, migration generation, and related tasks.
---

# Instructions

## Migration

- If no migration already exists, create a migration for the given fields.
- If a migration exists, read it to understand the database schema changes.
- Comment each field of the migration, except `id` and timestamps.

## Default values

- Many models have default values for some fields, and these values are meant to be translated.
- In those cases, store both `<field_name>` and `<field_name>_translation_key` in the database, and fall back to the translation key via `__()` when the value is null.

## Model

- Create the model in `app/Models/`, extending the Eloquent `Model` class.
- Add a class-level PHPDoc block listing every column as an `@property`, including `id`, `created_at` and `updated_at`.
- Use `HasFactory` with a `/** @use HasFactory<XFactory> */` docblock above the trait.
- Set the table explicitly with `protected $table`.
- Declare mass-assignable fields in `protected $fillable` typed as `list<string>`.
- Define casts in a `protected function casts(): array` method, not a property.
- Cast sensitive string fields to `encrypted`; cast booleans to `boolean`, integers to `integer`, and dates to `datetime`.
- Most models belong to a company — add the `company()` `BelongsTo` relationship.
- Type-hint every relationship and document it with a generic docblock (e.g. `@return BelongsTo<Vault, $this>`).
- Expose computed values through accessors using `Attribute::make()` with an `@return Attribute<string, never>` docblock.
- For translatable names, store both `name` and `name_translation_key`, and fall back to the translated key via `__()` when the value is null.
- Add a short docblock to every relationship, accessor and method.

## Factory

- Add a matching factory in `database/factories/`, extending `Factory` with an `@extends Factory<Model>` docblock.
- Set `protected $model` and return defaults from `definition(): array`.
- Populate fields with fake data and reference related models via their factories (e.g. `Vault::factory()`).

## Usage

- Use Model::query() instead of direct static calls

```
// GOOD
Member::query()->firstWhere('id', 42);

// BAD
Member::firstWhere('id', 42);
```

- Avoid mass assignment when possible:

```
// PREFERRED
$member = new Member();
$member->name = $request->input('name');
$member->email = $request->input('email');

// AVOID
$member->forceFill([
    'name' => $request->input('name'),
    'email' => $request->input('email'),
]);

// NEVER DO
$member->forceFill($request->all());
```

- Eager load relationships to avoid N+1 queries (`$users = User::query()->with(['posts'])->get();`)
- Use chunking for large datasets:
```
User::query()->chunk(100, function (Collection $users) {
    foreach ($users as $user) {
        // Process user
    }
});
```
- Utilize database indexes for frequently queried columns.
- Use query caching for expensive, frequently run queries.
