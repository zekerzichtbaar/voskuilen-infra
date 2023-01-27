<a {{ $attributes->merge(['class' => 'button'. (!empty($type) ? ' button-'. $type : '')]) }}>
    {!! $message ?? $slot !!}
</a>