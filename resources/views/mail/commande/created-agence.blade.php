<x-mail::message>
# Nouvelle commande

Une commande vient d'être passée sur votre agence **{{ $agenceName }}**.

- **Code** : {{ $commandeCode }}
- **Client** : {{ $clientName }}
- **Offre** : {{ $offreTitre }}
- **Montant du paiement en cours** : {{ $montantTotal }}

<x-mail::button :url="$commandeUrl">
Voir la commande
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
