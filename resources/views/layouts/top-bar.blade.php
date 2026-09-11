{{-- The shell of a screen that carries its navigation across the top rather than down the side. --}}
{{--
  @var string|null $title
  @var string $companyName
  @var string $name
  @var \App\Models\Employee|null $employee
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div
      x-data="{ menuOpen: false }"
      @keydown.escape.window="menuOpen = false"
      @keydown.g.window="if (! /input|textarea/i.test($event.target.tagName)) { $event.preventDefault(); menuOpen = ! menuOpen }"
      class="min-h-screen pb-22.5"
    >
      <div class="mx-auto max-w-265 px-4 sm:px-8">
        <x-top-bar :company-name="$companyName" :name="$name" :employee="$employee" />

        {{ $slot }}
      </div>
    </div>

    <x-toaster />
  </body>
</html>
