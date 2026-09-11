---
name: views
description: Conventions for defining views. Use when the user wants to create or modify views, including Blade templates.
---

# Views

## General

- View names MUST be kebab-case, and views MUST be stored in the
  `resources/views` folder.
- Views MUST be grouped by major domains of the application (`admin`, `auth`,
  `getting-started`, `marketplace`, `settings`, `vaults`), and they MUST match
  the controller folder structure.
- View partials MUST be prefixed with an underscore, and MUST be stored in the
  same folder as the view that uses them.
- You MUST NOT put domain logic in a view.
- You MUST NOT compute data in a view. The controller builds a view model and
  hands it to the view, and the view only reads it.
- A `@php` block is for presentation and nothing else: naming a set of classes,
  aliasing a view model call into a local variable, building a route, mapping a
  state to a tone and a label. It MUST NOT query the database, call an Action,
  or decide anything the product cares about. 30 views and 23 components hold
  one, and every one of them turns a value that was already computed into what
  the screen shows. The same rule applies inside a component.
- You SHOULD create and maintain PHPDoc blocks at the top of every view file.
- You MUST create and maintain PHPDoc blocks for components.
- When passing variables to a partial using `@include`, you MUST explicitly pass
  all required variables. Relying on the parent scope is not enough, even though
  Blade makes those variables reachable, because a partial that names what it
  needs can be read on its own.

```blade
{{-- Good: the partial says what it needs. --}}
@include('app.settings.account.security._change-password', ['viewModel' => $viewModel])

{{-- Bad: it happens to work, and nothing says why. --}}
@include('app.settings.account.security._change-password')
```

  A partial that needs no variable of its own, such as one built only out of
  copy and routes, is included without a second argument.

- You MUST make sure each view is responsive.


## Example
```
<?php
/**
 * @var \App\Models\User $user
 * @var \Illuminate\Support\Collection<int, \App\Models\Post> $posts
 */
?>
```

## Accessibility

- You MUST make sure that your views are accessible following the best
  practices.
- You MUST use semantic HTML elements when possible.
- You MUST NOT use a clickable <div> or <span>. Use a <button> for actions and
  an <a> for navigation.
- Placeholder text MUST NOT be used as the only label for a form control.
- Icon-only buttons and links MUST have an explicit aria-label.
- Decorative icons and images MUST be hidden from assistive technologies using
  aria-hidden="true" or an empty alt="".
- Meaningful images MUST have concise, descriptive alt text.
- You MUST NOT add ARIA attributes when native HTML already provides the correct
  semantics.
- When ARIA is necessary, all referenced IDs, such as aria-labelledby,
  aria-describedby, and aria-controls, MUST point to existing unique elements.

## CSS

- Instead of using margin and padding between elements, you MUST use `space-x`
  or `space-y` in div to create consistent spacing between child elements.

## Blade components

- Components are anonymous. You MUST write the blade file in
  `resources/views/components`, declare what it takes with `@props([...])`, and
  document it in a `{{-- --}}` block at the top with one `@var` per prop, as 46
  of the 51 components do. A component class belongs in `app/View/Components`
  only for a layout (`Shell`, `AccountLayout`, `AdminLayout`, `GuestLayout`,
  `MarketplaceLayout`).
- Every template, a component included, MUST open with a one line `{{-- --}}`
  comment saying what the view is, before the `@var` block. The rule and its
  examples are in [code-style.md](../../rules/code-style.md).
- As soon as you notice that a piece of view is repeated in multiple places, you
  MUST create a component for it.

## Marking the sections of a screen

The `{{-- --}}` block at the top says what the whole file is. Inside the markup,
a block that draws something a person sees carries one HTML comment naming it,
on the line above it, with a blank line before that.

```blade
<!-- login form -->
<x-box>
  <x-form method="post" :action="route('auth.signIn.create')" class="space-y-4">
    ...
  </x-form>
</x-box>
```

- It MUST be an HTML comment, not a Blade one. It is there to be read in the
  markup and in the page source, beside the thing it names.
- It MUST be two to four lowercase words naming the thing on the screen:
  `<!-- logo -->`, `<!-- login form -->`, `<!-- language picker -->`,
  `<!-- link to register page -->`. Never a sentence, a capital or a full stop.
- It MUST name what the block is. It MUST NOT say what the markup does, why it
  is written that way, or how it behaves.
- One block, one comment. A part of a marked block gets its own only when it is
  a section in its own right.

Not every block gets one, and the judgement is yours. Mark what somebody looking
at the screen would point at and name: a form, a card, a list, a picker, a menu,
a set of filters, a banner, a table, an empty state. Leave alone:

- the scaffolding that only positions those: the grid, the `<main>`, the wrapper
  `div` holding the lot
- a single self-explanatory component on a line of its own, such as
  `<x-status :message="session('status')" />`
- anything the tag already names, such as a `<footer>` or a lone `<h1>`
- a component file small enough to be one block, which its opening
  `{{-- --}}` line already names
