{{--
  The shell of every screen somebody sees once they are signed in: a sidebar on
  the left that stays put, and the screen itself on the right under a bar that
  says where you are.

  @var string|null $title
  @var \Illuminate\View\ComponentSlot $sidebar
  @var \Illuminate\View\ComponentSlot|null $breadcrumb
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[264px_minmax(0,1fr)]">
      {{ $sidebar }}

      <main class="min-w-0">
        <header class="sticky top-0 z-5 flex h-13 items-center gap-3.5 border-b border-hairline bg-page/90 px-7 backdrop-blur-md">
          @isset($breadcrumb)
            {{ $breadcrumb }}
          @endisset

          <span class="ml-auto text-sm text-muted">{{ auth()->user()?->email }}</span>
        </header>

        <div class="mx-auto max-w-240 space-y-8.5 px-7 pt-7.5 pb-17.5">
          {{ $slot }}
        </div>
      </main>
    </div>

    <x-toaster />
  </body>
</html>
