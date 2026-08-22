<x-mail::message>
# Publicité refusée

Bonjour {{ $ownerName }},

Votre publicité **{{ $titre }}** a été refusée.

@if ($motifRefus)
**Motif :** {{ $motifRefus }}
@endif

Vous pouvez corriger votre demande et la soumettre à nouveau.

<x-mail::button :url="$publiciteUrl">
Voir ma publicité
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
