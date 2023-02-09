<div {{ $attributes->merge(['class' => 'py-3 px-6 text-lg '. $type]) }}>
  {!! $message ?? $slot !!}
</div>
