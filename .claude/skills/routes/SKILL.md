---
name: routes
description: Conventions for defining routes. Use when the user wants to create or modify routes, including route groups, middleware, and naming conventions.
---

# Routes

- You MUST NOT use `Route::resource()` or `Route::apiResource()`. Instead, define each route individually, and name them explicitly.
- Add comments only when necessary.
- Major route groups must have a comment.
- You MUST add names to routes, and follow the naming convention: `<domain>.<resource>.<action>` (e.g. `account.users.index`).
- You MUST add validations to route parameters, and use the `where()` method to define regex patterns for parameters (e.g. `->where('id', '[0-9]+')`).
- Do not use route prefixes.
