---
name: views
description: Conventions for defining views. Use when the user wants to create or modify views, including Blade templates and view composers.
---

# Views

- View names MUST be kebab-case, and views MUST be stored in the `resources/views` folder.
- Views MUST be grouped by major domains of the application, like `account`, `operate`, `grow`, and they MUST match the controller folder structure.
- View partials MUST be prefixed with an underscore, and MUST be stored in the same folder as the view that uses them.
- You MUST NOT put domain logic in a view.
- You MUST NOT compute data in a view. You MUST compute data in a viewmodel, that is passed to the controller, and pass it to the view.
- You SHOULD create and maintain PHPDoc blocks at the top of every view file.
- You MUST create and maintain PHPDoc blocks for components.

## Example
```
<?php
/**
 * @var \App\Models\User $user
 * @var \Illuminate\Support\Collection<int, \App\Models\Post> $posts
 */
?>
```

## Blade components

- You MUST write a component class for every component, in a subfolder when needed (for instance, all the buttons are in `resources/views/components`).
- As soon as you notice that a piece of view is repeated in multiple places, you MUST create a component for it.
