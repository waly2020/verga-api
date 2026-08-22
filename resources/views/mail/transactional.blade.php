<x-mail::message>
# {{ $greeting }}

@foreach ($lines as $line)
{{ $line }}

@endforeach

@if ($action)
<x-mail::button :url="$action['url']">
{{ $action['label'] }}
</x-mail::button>
@endif

@if ($salutation)
{{ $salutation }}
@else
Cordialement,<br>
{{ config('app.name') }}
@endif
</x-mail::message>
