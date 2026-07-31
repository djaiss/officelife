{{--
  An inline link, underlined the way the rest of the running text is.
--}}
<a {{
  $attributes->class([
    'inline underline underline-offset-2',
    'decoration-hairline-strong',
    'transition-colors duration-150',
    'hover:text-brand hover:decoration-brand',
  ])
}}>{{ $slot }}</a>
