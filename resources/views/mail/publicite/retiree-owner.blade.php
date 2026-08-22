<x-mail::message>
# Publicité retirée

Bonjour {{ $ownerName }},

Votre publicité **{{ $titre }}** a été retirée par un administrateur.

@if ($motifRefus)
**Motif :** {{ $motifRefus }}
@endif

<x-mail::button :url="$publiciteUrl">
Voir ma publicité
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
