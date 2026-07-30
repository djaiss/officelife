---
name: controllers
description: Conventions for writing thin HTTP controllers that validate input and delegate to Actions. Use when creating or editing controllers in app/Http/Controllers (web, API, or marketing).
---

# Controllers

## Folder convention

- Controllers are split into logical categories.
- `App` controllers are for the logged in app, `Api` controllers are for the JSON API.
- Within these folders, controllers are grouped by major domains of the application, like `Account`, `Operate`, `Grow`,...

## Rules

- Check the other controllers in the project for reference, and try to follow the same structure and conventions.
- Controllers should be as thin as possible, and actions should contain the business logic.
- Controllers should be testable.

## Things to do

- You MUST only use these methods: `index`, `new`, `create`, `show`, `edit`, `update`, `destroy`
- You MUST use as fewer parameters as possible:
  - For `index`, use `$request` only.
  - For `new` and `create`, use `$request` only.
  - For `show`, `edit`, `update`, and `destroy`, use `$request` and request route attributes set in the middlewares to keep, if possible, only the `$request` parameter.
- You MUST not add private methods to a controller, even if code is repeated multiple times.
- You MUST not put domain logic in the controller - use Action when you need to execute a domain logic.
- You MUST use Form Requests for create and update operations, or whenever validation or authorization is non-trivial. Prefer inline validation for simple endpoints.
- You MUST use `$request->attributes->get('vault')` to get values from middlewares
- You MUST use `$request->user()` — never `Auth::user()`
- You SHOULD read route parameters with `$request->route()->parameter('gender')`
- Validate the request data, but do not sanitize it. In the validation, do not check if an object exists by checking if the id exists in the database - this is done in Actions. Make sure the validation rules match the fields of the model, as defined in the migration (ie length of a given string).
- Instantiate the Action and call `->execute()`, passing `user: $request->user()` and the validated data with named arguments.
- Prepare data for the view in a ViewModel, and pass it to the view.
- Type-hint the return of every method (`View`, `RedirectResponse`, `JsonResponse`, `AnonymousResourceCollection`, `Response`).

### If it's a web controller

- Return views, not JSON.
- Always pass validated data to the action.
- Do not compact data to a view. Instead, pass an array with keys that represent what the data is, like `['journals' => $journals]` instead of `compact('journals')`.
- Wrap scoped `findOrFail()` lookups in a try/catch on `ModelNotFoundException` and `abort(404)`.
- Redirect with `to_route(...)->with('status', __('Changes saved'))` after a mutation.

### If it's an API controller

- Return an API Resource (e.g. `GenderResource`), never raw arrays/JSON.
- Set the status code explicitly: `200` for show/update, `201` for create via `->response()->setStatusCode(...)`, and `response()->noContent(204)` for destroy.
- For `index`, return `Resource::collection($paginated)` and paginate, clamping `per_page` to `config('app.maximum_items_per_page')`.
- Let scoped `findOrFail()` throw — no try/catch (the framework returns 404).
