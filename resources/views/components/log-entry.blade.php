{{--
  One line of the log: who acted, what they did and how long ago. It reads the
  same in the box on the profile and on the screen that holds every entry, so
  both draw it from here.

  The raw name of the action is shown beside the sentence, in a monospaced face,
  because it is what somebody quotes when they ask us what happened.

  @var \App\Models\Log $log
--}}
@props([
  'log',
])

<div class="flex items-center gap-3 border-b border-hairline-soft px-4 py-3 last:border-b-0">
  <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0 text-placeholder" aria-hidden="true">
    <path d="M1.5 8h3L6 5l2 6 2-4 1.4 1h3.1"></path>
  </svg>

  <div class="min-w-0">
    <p class="flex flex-wrap items-baseline gap-x-2 text-sm">
      <span class="font-semibold text-ink">{{ $log->author }}</span>

      <span class="text-hairline-strong" aria-hidden="true">|</span>

      <span class="font-mono text-xs text-muted">{{ $log->action }}</span>
    </p>

    <p class="mt-0.5 text-sm text-body">{{ $log->description }}</p>
  </div>

  <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->toDayDateTimeString() }}" class="ml-auto shrink-0 font-mono text-xs text-muted-soft">
    {{ $log->created_at->diffForHumans() }}
  </time>
</div>
