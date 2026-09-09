# Complexity audit, 9 September 2026

## What was read

Commit `009379b`. Every file in `app/Actions` (62), `app/Http/Controllers` (27),
`app/ViewModels` (17) and `resources/views` (77). Models, jobs, enums, helpers
and tests were not part of this pass.

One question was asked of each file: could somebody who has been writing Laravel
for a year open it and know what it does without asking anybody?

The answer is mostly yes, and it is worth saying so before the list. The action
pattern, where `execute()` calls a run of named private steps and each step does
one thing, is the best decision in the codebase. `CheckoutAsset` performs six
distinct jobs and still reads top to bottom like a sentence. The controllers are
genuinely thin. Almost nothing here is tangled.

What follows is therefore not a list of messes. It is duplication that has
quietly set, a handful of clever expressions whose cleverness is invisible from
the outside, presentation values that have leaked into PHP classes, and three
different hand-rolled ways of asking somebody whether they are sure.

The bar for inclusion is deliberately low. Anything a junior would pause on is
here, including the mild things.

## How to read the severities

**Trips people up.** Somebody will get this wrong, or already could have.

**Slows people down.** Correct, but it takes a second and a third read.

**Worth tidying.** Noise, inconsistency, or a thing copied one time too many.

## Cross cutting

These belong to no single layer and are the largest findings in the document.

### 1. Nine view models declare the same three methods

*Slows people down.* Every view model under `app/ViewModels/Settings` declares
`companyName()`, `name()` and `employee()`. All nine bodies are identical, and so
are the docblocks above them, word for word.

A junior adding a tenth screen copies a ninth time and never asks why. Worse,
correcting the wording of one docblock leaves eight wrong.

A base class or a trait holding the three, taking `User` and `?Employee`, removes
about seventy lines and makes the next screen a two line job.

### 2. Three different ways to ask "are you sure?"

*Trips people up.* Destructive actions confirm in three unrelated ways across six
places.

The first swaps a button for a sentence and two more buttons, and lives in
`_two-factor-on.blade.php` (twice, once for turning two factor off and once for
regenerating recovery codes), `_api-keys.blade.php` and
`locations/_drawer.blade.php`. The second is a submit button that cancels its own
first click and rewrites its own label, on the photo delete in
`profile/index.blade.php`. The third is the `x-confirm-dialog` component added
for roles.

A junior asked to add a delete somewhere has to pick, and will pick by whichever
file they happened to open. Settle on one, and delete the other two.

### 3. The breadcrumb is copy pasted into ten screens

*Worth tidying.* The same five line `<nav>` appears verbatim in all ten top bar
screens, differing only in the last one or two entries. The `<x-slot:top-bar>`
block below it is identical in all ten.

An `<x-breadcrumb>` taking a list of pairs, and a layout that draws the top bar
itself, would take about forty lines out of the view layer and make a new screen
begin with its heading rather than with boilerplate.

### 4. Morph versus replace is the subtlest thing here, explained twice from scratch

*Trips people up.* Alpine Ajax merges responses by morphing, which keeps the
Alpine state on a node. Two places need it to replace instead, and each carries a
long comment explaining the whole idea from first principles:
`_api-keys.blade.php` needs `creating` to come back from the server, and
`toaster.blade.php` needs `x-init` to run again.

The comments are good. The problem is that the rule lives only inside them. A
junior hitting a third case has no way to know the rule exists until their
feature misbehaves in a way that looks like magic.

Write it down once, in the views skill, and have both comments point at it.

## Actions

### 5. `CheckoutAsset::refuseLoop()` can spin forever

*Trips people up.* This is the only finding in the document that can hang a
request.

`refuseLoop()` walks up the chain of equipment holding equipment and throws when
it meets the asset being handed over. It has no depth cap and no record of where
it has been. If two other assets already hold each other, say B inside C and C
inside B, the walk never meets `$this->asset` and never ends.

Nothing in the codebase creates that cycle today, because this very method
prevents it. It only takes one row written by a migration, a seeder or a console
command to make it reachable.

Keep the ids already seen in a set and throw when one repeats, or cap the walk at
a sane depth.

### 6. The same instanceof ladder is written twice in one file

*Slows people down.* `CheckoutAsset` asks what kind of thing the assignee is in
two places: `validateAssignee()` uses `match (true)` over three `instanceof`
branches to read `company_id`, and `assigneeName()` uses the same ladder to read
a display name. Both carry a `default => null` or `default => ''` branch that
cannot happen, because `AssetAssigneeTypeEnum::forModel()` has already rejected
anything else.

A junior adding a fourth kind of assignee has to find both, and the dead default
branches suggest the guard above is not trusted.

### 7. `CreateRole::validate()` and `UpdateRole::validate()` are the same fifteen lines

*Worth tidying.* Both walk `$grants`, refuse a scope the permission does not
offer, and refuse the same permission twice. The code is character for character
identical.

### 8. `CreateRole::log()` and `UpdateRole::log()` build the same string the same way

*Worth tidying.* Both flatten the grants into `permission:scope` pairs with the
same `implode`/`array_map`/closure. A third action that logs grants will make it
three.

### 9. `slug()` is written twice, numbers from two, and races

*Slows people down.* `CreateCompany::slug()` and `CreateRole::slug()` are the same
algorithm, once scoped to a company and once not.

Two things in it surprise a reader. The loop starts at `$suffix = 1` and
increments before use, so the first collision produces `-2` and `-1` never
exists. And the check is a `SELECT` followed by an `INSERT`, so two simultaneous
signups with the same company name both see the slug as free.

A shared helper, and a unique index doing the real work, fixes both.

### 10. `UpdateRole` throws "not found" to mean "not allowed"

*Trips people up.* A role with `is_editable` false raises
`ModelNotFoundException('Role not found')`. The role was found. It is not
editable.

The behaviour is deliberate and the screen does the right thing with it, but
nothing at the throw site says so. A junior debugging a 404 on a role they can
see on screen has no thread to pull.

### 11. `AttemptLogin::fail()` always throws but is typed `void`

*Trips people up.* `attempt()` reads as three statements in sequence:

```php
if ($candidate === null || ...) {
    $this->fail($candidate);
}

if (! Auth::attempt(...)) {
```

Nothing on the page says the first block ends the request. A junior editing this
reasonably assumes execution continues into the second `if` with a null
`$candidate`. PHP has a return type that says otherwise: `never`.

### 12. Two actions verify the same code two different ways

*Worth tidying.* `ConfirmTwoFactorAuthentication::verify()` and
`VerifyTwoFactorCode::verifyTimedCode()` both null check the secret and hand it
to `Google2FA::verifyKey()`. One casts the result to `bool` and the other does
not. `ConfirmTwoFactorAuthentication` could call the other action.

### 13. An XML declaration is trimmed with string arithmetic

*Slows people down.* `EnableTwoFactorAuthentication::draw()` ends with:

```php
return mb_substr($svg, (int) mb_strpos($svg, '<svg'));
```

The comment above it explains why. It still asks the reader to hold a string
offset in their head to understand a QR code. A named private method, or the
renderer configured not to emit the declaration, says the same thing out loud.

### 14. `CreateAsset` and `UpdateAsset` take sixteen constructor parameters each

*Slows people down.* Sixteen named parameters, thirteen of them optional. Calling
either means checking the signature every time, and the two lists have to be kept
in step by hand.

Nothing is wrong with it, and named arguments make the call sites readable. It is
listed because it is the point at which a junior stops reading the constructor
and starts guessing.

### 15. Three actions repeat the same "stamp the login" block

*Worth tidying.* `AttemptLogin::stamp()` and `ConsumeMagicLink::stamp()` set
`last_login_at`, save, and dispatch `CheckLastLogin` on the low queue. The bodies
are identical.

### 16. `CreateEmailSent` is written in a different voice

*Worth tidying.* Every other action opens with an imperative sentence describing
what it does to the world ("Hand a piece of equipment to somebody"). This one
opens with "Create an Email Sent object", describes a class rather than an act,
and uses "purged" where the rest of the codebase says "removed". It reads as
older code that the house style has since moved past.

## Controllers

### 17. A session key is typed out in three controllers

*Trips people up.* `'twoFactor.user.id'` appears in `LoginController`,
`MagicLinkController` and `TwoFactorChallengeController`, five times in total,
always as a literal.

A typo in one of them fails silently: the challenge screen simply redirects back
to sign in, and nothing anywhere reports a problem. A constant on the controller
that owns the challenge would make a typo a fatal error instead.

### 18. The Turnstile block is duplicated between two controllers

*Worth tidying.* `LoginController::create()` and
`RegistrationController::create()` both build `$rules`, then conditionally append
the same `cf-turnstile-response` rule behind the same config check. A third form
protected this way will copy it a third time.

### 19. `PhotoController` repeats its own guard

*Worth tidying.* `update()` and `destroy()` both open with the same three lines
reading `$request->user()->employee` and calling `abort(404)` when it is null.

### 20. One query parameter gets an enum, the one beside it gets a ternary

*Slows people down.* `LocationController::index()` reads three inputs. `scope`
goes through `LocationScopeEnum::fromSegment()`. `q` is trimmed. `sort` is
sanitised inline:

```php
sort: $request->query('sort') === 'place' ? 'place' : 'name',
```

The two allowed values live in that expression and nowhere else, and `'name'` and
`'place'` reappear as bare strings in four places in `LocationsViewModel`. A
junior cannot find out what the valid sorts are without reading both files.

### 21. Two empty catch blocks

*Slows people down.* `MagicLinkController` swallows `ModelNotFoundException`
twice, once with an empty body:

```php
try {
    new CreateMagicLink(email: $validated['email'])->execute();
} catch (ModelNotFoundException) {
}
```

This is correct and the comment above says why: an unknown address has to get the
same screen as a known one. An empty catch is still the shape of a bug, and
somebody will eventually "fix" it. A one line body saying nothing happens on
purpose is cheaper than the comment.

### 22. The base controller is an empty class holding a comment

*Worth tidying.* `app/Http/Controllers/Controller.php` is an abstract class whose
entire body is `//`. It is a Laravel skeleton leftover. Either give it something
(the `authorize()` helper that four controllers each declare privately would fit)
or accept the placeholder and drop the `//`.

## View models

### 23. `RolesViewModel` is the largest file in the project

*Slows people down.* 468 lines and sixteen public methods, serving two screens
(the list and one role) that share only the roles query. Half its methods answer
nothing on the list screen and return empty arrays there.

Splitting it into a `RolesViewModel` for the list and a `RoleViewModel` for the
one being read would let each say what it is for.

### 24. Presentation values are computed in PHP classes

*Trips people up.* `RolesViewModel` returns `'hue' => 30` (a raw oklch hue),
`'width' => '9px'`, and `'tone' => 'partial'`. `LocationsViewModel` returns hues
too, and `SettingsViewModel` returns a hue on every row.

A junior asked to change a colour looks in the stylesheet, then in the template,
and finds it in a class under `app/`. The rule the project already follows
everywhere else, that a view model returns values and a view decides how they
look, is broken here.

`tone` is fine, because it names a state. `hue` and `width` are CSS.

### 25. An empty string means "leave this out of the URL"

*Slows people down.* `LocationsViewModel::url()` builds a query string like this:

```php
$query = array_filter([
    'q' => $this->search,
    'sort' => $this->sort === 'name' ? '' : $this->sort,
    ...$overrides,
]);
```

The default sort is omitted from the link by setting it to the empty string and
letting `array_filter` drop it. `sortToggle()` does the same thing with
`['sort' => $byName ? 'place' : '']`.

It works, and there is nothing in either expression that says what the empty
string is for. A junior adding a fourth parameter will pass `null` and get the
same result by accident, or pass `'0'` and lose it without understanding why.

### 26. Three view models read `old()`

*Slows people down.* `RolesViewModel`, `LocationsViewModel` and `ProfileViewModel`
call `old()` to repopulate forms after a failed save. It works, and it keeps the
controllers thin.

It also means a view model is not a plain object built from what the controller
handed it: it reaches into request state on its own. A test that constructs one
directly gets different answers depending on whether a session happens to exist.

Worth a sentence in the view models skill either way, so it reads as a decision
rather than as something that crept in.

### 27. The locale fallback is written twice

*Worth tidying.* `SettingsViewModel::preferencesValue()` and
`PreferencesViewModel::locale()` both take `app()->getLocale()`, check it against
the keys of `config('officelife.locales')`, and fall back to `config('app.locale')`.

### 28. `config('officelife.locales')` is reached into from six files

*Slows people down.* Nine reads across six files, several of them
`config('officelife.locales')[$code]['label']`. Nothing checks that `$code` is a
key or that the entry has a `label`, and nothing types the shape of an entry.

A small class with `all()`, `label(string $code)` and `isSupported(string $code)`
would put the shape in one place.

## Views

### 29. A view model method is called four times to read four keys

*Trips people up.* `profile/index.blade.php` writes
`$viewModel->details()['first_name']`, then `['last_name']`, then
`['display_name']`, then `['work_email']`. `emergencyContact()` is called three
times the same way.

Each call rebuilds the array and re-reads `old()` for every field. Seven calls
where two would do.

The project's own rules allow a `@php` block that aliases a view model call into a
local variable. This is exactly that case, and the file next door
(`locations/index.blade.php`) already does it.

### 30. The product's navigation lives in a `@php` block, with CSS in strings

*Slows people down.* `components/top-bar.blade.php` declares the six sections of
the application and the four personal links as PHP arrays inside the template.
Each section carries its icon as a string of inline CSS:

```php
'glyph' => 'width:20px;height:20px;border-radius:999px;background:oklch(0.78 0.14 250)',
```

Two things follow. The shape of the product is defined in a template rather than
anywhere a reader would look for it. And the icons are unreadable, unsearchable
and outside every Tailwind and theme mechanism the rest of the codebase uses.

The same file then writes the tile markup twice, ten near identical lines each,
once as an `<a>` for the sections that have a screen and once as a `<span>` for
the ones that do not.

### 31. `top-bar` depends on state declared two files away

*Trips people up.* The component reads and writes `menuOpen`, which is declared in
`layouts/top-bar.blade.php` on a div wrapping the whole page. Nothing in the
component says so. Its docblock lists three props, none of which is `menuOpen`.

Using `<x-top-bar>` anywhere else produces a button that silently does nothing.

### 32. An Alpine component is built as a PHP heredoc

*Slows people down.* `locations/index.blade.php` assembles forty lines of
JavaScript into a `<<<JS` string in a `@php` block, interpolating three PHP values
into it, and hands the result to `x-data`.

The comment explains the reason honestly: Blade compiles neither `@js` nor
`{!! !!}` inside the attribute of a component tag. The reason is real and the
workaround is the largest block of JavaScript in the view layer, with no syntax
highlighting, no formatting and no way to lint it.

Worth revisiting: a `<script type="application/json">` block for the data and a
small named Alpine component in `resources/js` would put the JavaScript where
JavaScript goes.

### 33. Raw HTML from the database is printed in a template

*Slows people down.* `_email-sent-row.blade.php` ends with:

```blade
<div ...>{!! $emailSent->body !!}</div>
```

It is safe. `CreateEmailSent` runs the body through Purify before it is stored.
Nothing in this file says that, and a reader auditing for XSS has to find the
action that wrote the row to satisfy themselves.

One line of comment naming `CreateEmailSent` closes it.

### 34. A template calls a global JavaScript function

*Slows people down.* `profile/index.blade.php` calls
`window.oversizedFiles($event.target.files, 5120)`. The function is defined in
`resources/js/app.js`, and nothing in the template points there. The `5120` is
also the upload limit, written a third time here after
`PhotoController` (`max:5120`) and `UpdateEmployeePhoto`
(`MAX_SIZE_IN_BYTES`), with no shared source.

### 35. A regex splits a name inside a template

*Worth tidying.* `avatar-initials.blade.php` runs
`preg_split('/\s+/', trim($name))` through a collection chain to take two
initials. It is presentation, so it belongs in a component, but a regex is the
one thing in a Blade file a junior will not touch.

### 36. The same icon is inlined twice in one file

*Worth tidying.* `_api-keys.blade.php` writes the key SVG twice, once for the list
rows and once for the empty state, differing only in size. `x-settings-icon`
already exists for exactly this and is used by six other templates.

### 37. Every form sends `_method`, including the GET ones

*Slows people down.* `components/form.blade.php` always emits
`<input type="hidden" name="_method" value="{{ $method }}">`. A form with
`method="get"` therefore puts `_method=get` in the query string of every search.

Harmless, and visible in the address bar of the locations search, which is where
somebody will eventually notice and wonder what it does.

### 38. The language picker falls back to the first entry

*Worth tidying.* `language-picker.blade.php` does
`collect($locales)->firstWhere('code', $current) ?? $locales[0]`. That assumes
`$locales` is non empty and numerically indexed from zero. Both are true today
and neither is stated.

### 39. A six method Alpine object is declared inside an HTML attribute

*Slows people down.* `roles/_permissions.blade.php` puts a component with two
pieces of state and six methods into the `x-data` attribute, spread over thirteen
lines. It is the filtering behaviour of the permission matrix, and it is not
reachable by any tooling.

### 40. `welcome.blade.php` is 223 lines with no view model

*Worth tidying.* The landing page is the longest template in the project and is
rendered by a closure in `routes/web.php` rather than by a controller. It is
marketing copy, so nothing depends on it, but it is the first file a new
contributor opens and it does not follow any of the conventions the rest of the
codebase does.

## What to do first

Three of the forty are worth doing before the rest.

1. **Cap the walk in `CheckoutAsset::refuseLoop()`** (finding 5). It is the only
   one that can hang a request, and it is a five line change.

2. **Pull the three repeated methods out of the nine view models** (finding 1).
   It removes the most duplicated code in the project and makes the tenth screen
   cheaper than the ninth.

3. **Settle on one confirmation for destructive actions** (finding 2). Three
   mechanisms across six places is the thing most likely to produce a fourth.

Everything else can wait for the file it lives in to be opened for another
reason.
