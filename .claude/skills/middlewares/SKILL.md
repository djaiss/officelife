---
name: middlewares
description: Conventions for defining middlewares. Use when the user wants to create or modify middlewares, including route groups, middleware, and naming conventions.
---

# Middlewares

- You MUST use middlewares instead of checking permissions in controllers or actions.

```
class CheckCatalog
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = (int) $request->route()->parameter('collection');

        try {
            $catalog = $request->user()->account->catalogs()->findOrFail($id);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        $request->route()->setParameter('collection', $catalog);
        $request->attributes->set('catalog', $catalog);

        return $next($request);
    }
}
```
