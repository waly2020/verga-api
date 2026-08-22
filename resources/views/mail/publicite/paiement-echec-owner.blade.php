<x-mail::message>
# Paiement échoué

Bonjour {{ $ownerName }},

Le paiement **{{ $paiementCode }}** de **{{ $montantTotal }}** pour la publicité **{{ $titre }}** n'a pas abouti.

Vous pouvez réessayer depuis votre espace.

<x-mail::button :url="$publiciteUrl">
Réessayer le paiement
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
