<x-mail::message>
# Paiement confirmé

Bonjour {{ $clientName }},

Votre paiement **{{ $paiementCode }}** de **{{ $montantTotal }}** pour la commande **{{ $commandeCode }}** a été validé.

Statut de la commande : **{{ $commandeStatut }}**

<x-mail::button :url="$retourUrl">
Voir le reçu
</x-mail::button>

<x-mail::button :url="$commandeUrl">
Suivre ma commande
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
