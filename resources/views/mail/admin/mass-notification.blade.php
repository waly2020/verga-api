<x-mail::message>
# Bonjour,

@foreach ($lines as $line)
{{ $line }}

@endforeach

@if ($action)
<x-mail::button :url="$action['url']">
{{ $action['label'] }}
</x-mail::button>
@endif

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
