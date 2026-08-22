<x-mail::message>
# Paiement reçu

Un paiement a été validé pour la commande **{{ $commandeCode }}**.

- **Client** : {{ $clientName }}
- **Montant** : {{ $montantTotal }}
- **Statut commande** : {{ $commandeStatut }}

<x-mail::button :url="$commandeUrl">
Consulter la commande
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
