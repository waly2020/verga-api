<x-mail::message>
# Paiement confirmé

Bonjour {{ $ownerName }},

@if ($periodeTerminee)
Votre paiement pour **{{ $titre }}** a été confirmé. La période de diffusion est déjà terminée.
@else
Votre publicité **{{ $titre }}** est maintenant **en ligne**.
@endif

@if ($paiementCode)
- **Paiement** : {{ $paiementCode }}
- **Montant** : {{ $montantTotal }}
@endif
- **Période** : {{ $dateDebut }} → {{ $dateFin }}

@if ($retourUrl)
<x-mail::button :url="$retourUrl">
Voir le reçu
</x-mail::button>
@endif

<x-mail::button :url="$publiciteUrl">
Voir ma publicité
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
